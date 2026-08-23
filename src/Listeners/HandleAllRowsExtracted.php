<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Listeners;

use Akbarjimi\ExcelImporter\Concerns\LogsImportActivity;
use Akbarjimi\ExcelImporter\Events\AllRowsExtracted;
use Akbarjimi\ExcelImporter\Events\FileProcessingCompleted;
use Akbarjimi\ExcelImporter\Jobs\ProcessChunkJob;
use Akbarjimi\ExcelImporter\Repositories\ExcelFileRepository;
use Akbarjimi\ExcelImporter\Services\ChunkerService;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Support\Facades\Bus;
use Throwable;

final class HandleAllRowsExtracted implements ShouldQueueAfterCommit
{
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


    /**
     * Handle the event: partition the extracted rows and dispatch processing jobs.
     */
    public function handle(AllRowsExtracted $event): void
    {
        $file = $this->fileRepo->findFile($event->fileId, ['excelSheets']);

        if ($file === null) {
            $this->importLog('warning', trans('excel-importer::messages.file_missing_on_retry', [
                'file_id' => $event->fileId,
            ]));
            return;
        }

        // Create all chunks transactionally
        $chunks = $this->chunker->createChunksForFile($file);

        if ($chunks->isEmpty()) {
            $this->importLog('warning', trans('excel-importer::messages.no_chunks_created', [
                'file_id' => $file->id,
            ]));

            return;
        }

        $fileId = $file->id;
        $this->fileRepo->markAsProcessing($fileId);

        $jobs = $chunks->map(
            static fn(object $chunk): ProcessChunkJob => new ProcessChunkJob($chunk->id)
        )->all();
        $batch = Bus::batch($jobs)
            ->then(static function (Batch $batch) use ($fileId): void {
                app(ExcelFileRepository::class)->markAsCompleted($fileId);
                FileProcessingCompleted::dispatch($fileId);
            })
            ->catch(static function (Batch $batch, Throwable $e) use ($fileId): void {
                app(ExcelFileRepository::class)->markAsFailed($fileId); // Log detailed error from the batch failure, satisfying audit requirements.
                app(ExcelFileRepository::class)->logBatchFailure($fileId, $e);
            })
            ->name("excel-import-chunks:{$file->getKey()}")
            ->dispatch();

        $this->importLog('info', trans('excel-importer::messages.chunk_jobs_batched', [
                'file_id' => $fileId,
                'count' => $jobs->count(),
            ]
        ));

    }

    /**
     * Clean up state if the listener itself fails after all retries are exhausted.
     *
     * @param AllRowsExtracted $event
     * @param Throwable $e
     * @return void
     */
    public function failed(AllRowsExtracted $event, Throwable $e): void
    {
        $this->importLog('error', trans('excel-importer::messages.extraction_failed', [
                'file_id' => $event->fileId,
                'message' => $e->getMessage(),
            ]
        ), ['exception' => $e]);

        $this->fileRepo->markAsFailed($event->fileId);
    }
}
