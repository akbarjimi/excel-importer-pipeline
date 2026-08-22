<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Listeners;

use Akbarjimi\ExcelImporter\Events\ExcelFileRegistered;
use Akbarjimi\ExcelImporter\Events\FileSheetsScanCompleted;
use Akbarjimi\ExcelImporter\Repositories\ExcelFileRepository;
use Akbarjimi\ExcelImporter\Repositories\ExcelSheetRepository;
use Akbarjimi\ExcelImporter\Services\SheetDiscoveryService;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

final class HandleExcelFileRegistered implements ShouldQueueAfterCommit
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        private readonly SheetDiscoveryService $discovery,
        private readonly ExcelFileRepository $fileRepo,
        private readonly ExcelSheetRepository $sheetRepo,
    ) {}

    public function viaQueue(): string
    {
        return config('excel-importer.queue', 'default');
    }

    public function handle(ExcelFileRegistered $event): void
    {
        $file = $this->fileRepo->findFile($event->excelFileId);

        if ($file === null) {
            return;
        }

        if ($this->sheetRepo->existsForFile($file->id)) {
            event(new FileSheetsScanCompleted($file->id));

            return;
        }

        $this->fileRepo->markAsReading($file->id);

        $sheets = $this->discovery->discover($file);

        $this->sheetRepo->bulkCreate($file->id, $sheets);

        event(new FileSheetsScanCompleted($file->id));
    }

    public function failed(ExcelFileRegistered $event, Throwable $e): void
    {
        $this->fileRepo->markAsFailed($event->excelFileId, $e->getMessage());
    }
}
