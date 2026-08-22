<?php

namespace Akbarjimi\ExcelImporter\Listeners;

use Akbarjimi\ExcelImporter\Events\AllRowsExtracted;
use Akbarjimi\ExcelImporter\Events\FileSheetsScanCompleted;
use Akbarjimi\ExcelImporter\Jobs\ExtractSheetRowsJob;
use Akbarjimi\ExcelImporter\Repositories\ExcelFileRepository;
use Akbarjimi\ExcelImporter\Repositories\ExcelSheetRepository;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

final class HandleFileSheetsScanCompleted implements ShouldQueueAfterCommit
{
    use InteractsWithQueue;
    use LogsImportActivity;


    public function __construct(
        private readonly ExcelSheetRepository $sheetRepo,
        private readonly ExcelFileRepository $fileRepo,
    ) {}

    public function handle(FileSheetsScanCompleted $event): void
    {
        $sheets = $this->sheetRepo->getByFileId($event->fileId);

        if ($sheets->count() > config('excel-importer.max_sheets', 50)) {
            $this->fileRepo->markFailed($event->fileId);
            $this->importLog('warning', 'Excel import rejected: sheet count exceeds limit', [
                'file_id' => $event->fileId,
                'sheet_count' => $sheets->count(),
            ]);

            return;
        }

        $fileId = $event->fileId;

        $jobs = $sheets->map(
            fn ($sheet) => new ExtractSheetRowsJob($sheet->id)
        )->all();

        Bus::batch($jobs)
            ->then(function (Batch $batch) use ($fileId) {
                app(ExcelFileRepository::class)->markAsRowsExtracted($fileId);
                AllRowsExtracted::dispatch($fileId);
            })
            ->catch(function (Batch $batch, Throwable $e) use ($fileId) {
                app(ExcelFileRepository::class)->markAsFailed($fileId);
            })
            ->name("excel-import:{$fileId}")
            ->dispatch();
    }
}
