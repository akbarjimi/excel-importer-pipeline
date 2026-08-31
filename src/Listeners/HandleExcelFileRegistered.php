<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Listeners;

use Akbarjimi\ExcelImporter\Concerns\LogsImportActivity;
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
    use LogsImportActivity;

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

    /**
     * Tags for Horizon / Pulse observability.
     */
    public function tags(): array
    {
        return ['excel-registered', "file:{$this->event?->excelFileId}"];
    }

    public function handle(ExcelFileRegistered $event): void
    {
        $file = $this->fileRepo->findFile($event->excelFileId);

        if ($file === null) {
            $this->importLog('warning', 'File not found for discovery', ['file_id' => $event->excelFileId]);
            return;
        }

        if ($this->sheetRepo->existsForFile($file->id)) {
            $this->importLog('info', 'Sheets already discovered, skipping', ['file_id' => $file->id]);
            FileSheetsScanCompleted::dispatch($file->id);
            return;
        }

        $this->fileRepo->markAsReading($file->id);

        try {
            $sheets = $this->discovery->discover($file);
            $this->sheetRepo->bulkCreate($file->id, $sheets);

            FileSheetsScanCompleted::dispatch($file->id);

            $this->importLog('info', 'Sheet discovery completed', [
                'file_id'   => $file->id,
                'sheet_count' => count($sheets),
            ]);
        } catch (Throwable $e) {
            $this->fileRepo->markAsFailed($file->id, $e->getMessage());
            $this->importLog('error', 'Sheet discovery failed', [
                'file_id' => $file->id,
                'error'   => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(ExcelFileRegistered $event, Throwable $e): void
    {
        $this->fileRepo->markAsFailed($event->excelFileId, $e->getMessage());
        $this->importLog('critical', 'Listener failed after retries', [
            'file_id' => $event->excelFileId,
            'error'   => $e->getMessage(),
        ]);
    }
}