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
        private readonly ChunkerService $chunker,
        private readonly ExcelFileRepository $fileRepo,
    ) {
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
        if (!$file || $file->trashed()) {
            $this->importLog(LogLevel::WARNING, "File {$event->fileId} has been deleted. Skipping further processing.");
            return;
        }

        $chunks = $this->chunker->createChunksForFile($file);

        if ($chunks->isEmpty()) {
            $this->importLog(LogLevel::WARNING, "No chunks created for file {$file->id} – marking as completed.");
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
                $this->importLog(LogLevel::INFO, "Processing batch completed for file {$fileId}.", [
                    'batch_id' => $batch->id,
                ]);
            })
            ->catch(function (Batch $batch, Throwable $e) use ($fileId) {
                $this->fileRepo->markAsFailed($fileId, $e->getMessage());
                $this->importLog(LogLevel::CRITICAL, "Processing batch failed for file {$fileId}. Error: {$e->getMessage()}", [
                    'error' => $e->getMessage(),
                ]);
            })
            ->finally(function (Batch $batch) use ($fileId) {
                $this->fileRepo->recordBatchId($fileId, $batch->id);
            })
            ->dispatch();

        $this->importLog(LogLevel::INFO, "Chunk jobs batched for file {$fileId}. Count: " . count($jobs), [
            'count' => count($jobs),
        ]);
    }

    public function failed(AllRowsExtracted $event, Throwable $e): void
    {
        $this->fileRepo->markAsFailed($event->fileId, $e->getMessage());
        $this->importLog(LogLevel::CRITICAL, "HandleAllRowsExtracted listener failed for file {$event->fileId}. Error: {$e->getMessage()}", [
            'error' => $e->getMessage(),
        ]);
    }
}