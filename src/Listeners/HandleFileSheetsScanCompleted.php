<?php

namespace Akbarjimi\ExcelImporter\Listeners;

use Akbarjimi\ExcelImporter\Events\SheetReadyForExtraction;
use Akbarjimi\ExcelImporter\Events\FileSheetsScanCompleted;
use Akbarjimi\ExcelImporter\Repositories\ExcelSheetRepository;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Queue\InteractsWithQueue;

final class HandleFileSheetsScanCompleted implements ShouldQueueAfterCommit
{
    use InteractsWithQueue;

    public function __construct(
        private readonly ExcelSheetRepository $sheetRepo,
    ) {}

    public function handle(FileSheetsScanCompleted $event): void
    {
        $sheets = $this->sheetRepo->getByFileId($event->fileId);

        if ($sheets->count() > config('excel-importer.max_sheets', 50)) {
            // mark file as failed, log warning
            return;
        }

        foreach ($sheets as $sheet) {
            event(new SheetReadyForExtraction($sheet->id));
        }
    }
}
