<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Listeners;

use Akbarjimi\ExcelImporter\Concerns\LogsImportActivity;
use Akbarjimi\ExcelImporter\Enums\LogLevel;
use Akbarjimi\ExcelImporter\Events\AllRowsExtracted;
use Akbarjimi\ExcelImporter\Events\FileSheetsScanCompleted;
use Akbarjimi\ExcelImporter\Jobs\ExtractSheetRowsJob;
use Akbarjimi\ExcelImporter\Repositories\ExcelFileRepository;
use Akbarjimi\ExcelImporter\Repositories\ExcelSheetRepository;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Bus;
use Throwable;

final class HandleFileSheetsScanCompleted implements ShouldQueueAfterCommit
{
    use InteractsWithQueue;
    use LogsImportActivity;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        private readonly ExcelSheetRepository $sheetRepo,
        private readonly ExcelFileRepository $fileRepo,
    ) {}

    public function viaQueue(): string
    {
        return config('excel-importer.queue', 'default');
    }

    public function tags(): array
    {
        return ['excel-sheets-scan', "file:{$this->event?->fileId}"];
    }

    public function handle(FileSheetsScanCompleted $event): void
    {
        $sheets = $this->sheetRepo->getByFileId($event->fileId);

        $limit = (int) config('excel-importer.max_sheets', 50);
        if ($sheets->count() > $limit) {
            $this->fileRepo->markAsFailed($event->fileId, trans('excel-importer::sheet_limit_exceeded', [
                'count' => $sheets->count(),
                'limit' => $limit,
            ]));

            $this->importLog(LogLevel::WARNING, 'excel-importer::sheet_limit_exceeded', [
                'file_id' => $event->fileId,
                'count'   => $sheets->count(),
                'limit'   => $limit,
            ]);

            return;
        }

        $fileId = $event->fileId;
        $jobs = $sheets->map(fn($sheet) => new ExtractSheetRowsJob($sheet->id))->all();

        if (empty($jobs)) {
            $this->fileRepo->markAsRowsExtracted($fileId);
            AllRowsExtracted::dispatch($fileId);
            $this->importLog(LogLevel::INFO, 'excel-importer::no_sheets_to_extract', ['file_id' => $fileId]);
            return;
        }

        Bus::batch($jobs)
            ->name("excel-extract:{$fileId}")
            ->onQueue(config('excel-importer.queue', 'default'))
            ->allowFailures(false)
            ->then(function (Batch $batch) use ($fileId) {
                $this->fileRepo->markAsRowsExtracted($fileId);
                AllRowsExtracted::dispatch($fileId);
                $this->importLog(LogLevel::INFO, 'excel-importer::extraction_batch_completed', [
                    'file_id' => $fileId,
                    'batch_id' => $batch->id,
                ]);
            })
            ->catch(function (Batch $batch, Throwable $e) use ($fileId) {
                $this->fileRepo->markAsFailed($fileId, $e->getMessage());
                $this->importLog(LogLevel::CRITICAL, 'excel-importer::extraction_batch_failed', [
                    'file_id' => $fileId,
                    'error'   => $e->getMessage(),
                ]);
            })
            ->finally(function (Batch $batch) use ($fileId) {
                $this->fileRepo->recordBatchId($fileId, $batch->id);
            })
            ->dispatch();
    }
}