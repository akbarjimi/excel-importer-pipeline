<?php

namespace Akbarjimi\ExcelImporter\Listeners;

use Akbarjimi\ExcelImporter\Events\ExcelFileRegistered;
use Akbarjimi\ExcelImporter\Events\FileSheetsScanCompleted;
use Akbarjimi\ExcelImporter\Repositories\ExcelFileRepository;
use Akbarjimi\ExcelImporter\Repositories\ExcelSheetRepository;
use Akbarjimi\ExcelImporter\Services\SheetDiscoveryService;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Queue\InteractsWithQueue;

final class HandleExcelFileRegistered implements ShouldQueueAfterCommit
{
    use InteractsWithQueue;

    public function __construct(
        private readonly SheetDiscoveryService $discovery,
        private readonly ExcelFileRepository   $fileRepo,
        private readonly ExcelSheetRepository  $sheetRepo,
    )
    {
    }

    public function handle(ExcelFileRegistered $event): void
    {
        $file = $this->fileRepo->findFile($event->fileId, ['excelSheets']);

        $sheets = $this->discovery->discover($file);

        $this->sheetRepo->bulkCreate($file->id, $sheets);

        event(new FileSheetsScanCompleted($file->id));
    }
}
