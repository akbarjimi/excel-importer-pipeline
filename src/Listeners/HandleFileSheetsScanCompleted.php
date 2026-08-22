<?php

declare(strict_types=1);


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
        private readonly ExcelFileRepository  $fileRepo,
    )
    {
    }

    public function viaQueue(): string
    {
        return config('excel-importer.queue', 'default');
    }

    public function handle(FileSheetsScanCompleted $event): void
    {
        $sheets = $this->sheetRepo->getByFileId($event->fileId);

        $limit = config('excel-importer.max_sheets', 50);

        if ($sheets->count() > $limit) {
            $this->fileRepo->markAsFailed($event->fileId);

            $message = trans('excel-importer::messages.sheet_limit_exceeded', [
                'count' => $sheets->count(),
                'limit' => $limit,
            ]);

            $this->importLog('warning', $message, ['excel_file_id' => $event->fileId,]);

            return;
        }

        $fileId = $event->fileId;

        $jobs = $sheets->map(
            static fn(object $sheet): ExtractSheetRowsJob => new ExtractSheetRowsJob($sheet->id)
        )->all();

        if ($jobs->isEmpty()) {
            $this->fileRepo->markAsRowsExtracted($event->fileId);
            AllRowsExtracted::dispatch($fileId);
        }

        Bus::batch($jobs)
            ->then(static function (Batch $batch) use ($fileId) {
                app(ExcelFileRepository::class)->markAsRowsExtracted($fileId);
                AllRowsExtracted::dispatch($fileId);
            })
            ->catch(static function (Batch $batch, Throwable $e) use ($fileId) {
                app(ExcelFileRepository::class)->markAsFailed($fileId);
            })
            ->name("excel-import:{$fileId}")
            ->dispatch();
    }
}
