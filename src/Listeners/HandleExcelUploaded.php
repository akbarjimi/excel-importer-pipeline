<?php

namespace Akbarjimi\ExcelImporter\Listeners;

use Akbarjimi\ExcelImporter\Events\ExcelUploaded;
use Akbarjimi\ExcelImporter\Events\SheetsDiscovered;
use Akbarjimi\ExcelImporter\Repositories\ExcelFileRepository;
use Akbarjimi\ExcelImporter\Repositories\ExcelSheetRepository;
use Akbarjimi\ExcelImporter\Services\SheetDiscoveryService;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Queue\InteractsWithQueue;

final class HandleExcelUploaded implements ShouldQueueAfterCommit
{
    use InteractsWithQueue;

    public function __construct(
        private readonly SheetDiscoveryService $discovery,
        private readonly ExcelFileRepository   $fileRepo,
        private readonly ExcelSheetRepository  $sheetRepo,
    )
    {
    }

    public function handle(ExcelUploaded $event): void
    {
        $file = $this->fileRepo->findOrFail($event->fileId);

        $sheets = $this->discovery->discover($file);

        $this->sheetRepo->bulkCreate($file->id, $sheets);

        event(new SheetsDiscovered($file->id));
    }
}
