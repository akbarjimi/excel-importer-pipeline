<?php

namespace Akbarjimi\ExcelImporter\Listeners;

use Akbarjimi\ExcelImporter\Events\SheetDiscovered;
use Akbarjimi\ExcelImporter\Events\SheetsDiscovered;
use Akbarjimi\ExcelImporter\Repositories\ExcelSheetRepository;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Queue\InteractsWithQueue;

final class HandleSheetsDiscovered implements ShouldQueueAfterCommit
{
    use InteractsWithQueue;

    public function __construct(
        private readonly ExcelSheetRepository $sheetRepo,
    ) {}

    public function handle(SheetsDiscovered $event): void
    {
        foreach ($this->sheetRepo->getByFileId($event->fileId) as $sheet) {
            event(new SheetDiscovered($sheet->id));
        }
    }
}
