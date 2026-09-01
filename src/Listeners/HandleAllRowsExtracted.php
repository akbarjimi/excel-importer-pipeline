<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Listeners;

use Akbarjimi\ExcelImporter\Concerns\LogsImportActivity;
use Akbarjimi\ExcelImporter\Enums\LogLevel;
use Akbarjimi\ExcelImporter\Events\AllRowsExtracted;
use Akbarjimi\ExcelImporter\Events\FileProcessingCompleted;
use Akbarjimi\ExcelImporter\Jobs\ProcessChunkJob;
use Akbarjimi\ExcelImporter\Repositories\ExcelFileRepository;
use Akbarjimi\ExcelImporter\Services\ChunkerService;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Bus;
use Throwable;

final class HandleAllRowsExtracted implements ShouldQueueAfterCommit
{
    use InteractsWithQueue;
    use LogsImportActivity;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        private readonly ChunkerService      $chunker,
        private readonly ExcelFileRepository $fileRepo,
    )
    {
    }

    public function viaQueue(): string
    {
        return config('excel-importer.queue', 'default');
    }

    public function tags(): array
    {
        return ['excel-chunking', "file:{$this->event?->fileId}"];
    }

    public function handle(AllRowsExtracted $event): void
    {
        $file = $this->fileRepo->findFile($event->fileId, ['excelSheets']);

        if ($file === null) {
            $this->importLog(LogLevel::WARNING, 'excel-importer::file_not_found', ['file_id' => $event->fileId]);
            return;
        }

        $chunks = $this->chunker->createChunksForFile($file);

        if ($chunks->isEmpty()) {
            $this->importLog(LogLevel::WARNING, 'excel-importer::no_chunks_created', ['file_id' => $file->id]);
            $this->fileRepo->markAsCompleted($file->id);
            FileProcessingCompleted::dispatch($file->id);
            return;
        }

        $fileId = $file->id;
        $this->fileRepo->markAsProcessing($fileId);

        $jobs = $chunks->map(fn($chunk) => new ProcessChunkJob($chunk->id))->all();

        Bus::batch($jobs)
            ->name("excel-process:{$fileId}")
            ->onQueue(config('excel-importer.queue', 'default'))
            ->allowFailures(false)
            ->then(function (Batch $batch) use ($fileId) {
                $this->fileRepo->markAsCompleted($fileId);
                FileProcessingCompleted::dispatch($fileId);
                $this->importLog(LogLevel::INFO, 'excel-importer::processing_batch_completed', [
                    'file_id' => $fileId,
                    'batch_id' => $batch->id,
                ]);
            })
            ->catch(function (Batch $batch, Throwable $e) use ($fileId) {
                $this->fileRepo->markAsFailed($fileId, $e->getMessage());
                $this->importLog(LogLevel::CRITICAL, 'excel-importer::processing_batch_failed', [
                    'file_id' => $fileId,
                    'error' => $e->getMessage(),
                ]);
            })
            ->finally(function (Batch $batch) use ($fileId) {
                $this->fileRepo->recordBatchId($fileId, $batch->id);
            })
            ->dispatch();

        $this->importLog(LogLevel::INFO, 'excel-importer::chunk_jobs_batched', [
            'file_id' => $fileId,
            'count' => count($jobs),
        ]);
    }

    public function failed(AllRowsExtracted $event, Throwable $e): void
    {
        $this->fileRepo->markAsFailed($event->fileId, $e->getMessage());
        $this->importLog(LogLevel::CRITICAL, 'excel-importer::handle_all_rows_extracted_failed', [
            'file_id' => $event->fileId,
            'error' => $e->getMessage(),
        ]);
    }
}