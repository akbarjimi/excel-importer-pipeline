<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Listeners;

use Akbarjimi\ExcelImporter\Concerns\LogsImportActivity;
use Akbarjimi\ExcelImporter\Enums\LogLevel;
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
        private readonly ExcelFileRepository   $fileRepo,
        private readonly ExcelSheetRepository  $sheetRepo,
    )
    {
    }

    public function viaQueue(): string
    {
        return config('excel-importer.queue', 'default');
    }

    public function tags(): array
    {
        return ['excel-registered', "file:{$this->event?->excelFileId}"];
    }

    public function handle(ExcelFileRegistered $event): void
    {
        $file = $this->fileRepo->findFile($event->excelFileId);

        if ($file === null) {
            $this->importLog(LogLevel::WARNING, 'excel-importer::file_not_found', ['file_id' => $event->excelFileId]);
            return;
        }

        if ($this->sheetRepo->existsForFile($file->id)) {
            $this->importLog(LogLevel::INFO, 'excel-importer::sheets_already_discovered', ['file_id' => $file->id]);
            FileSheetsScanCompleted::dispatch($file->id);
            return;
        }

        $this->fileRepo->markAsReading($file->id);

        try {
            $sheets = $this->discovery->discover($file);

            if (empty($sheets)) {
                $this->importLog(LogLevel::WARNING, 'excel-importer::no_sheets_found', ['file_id' => $file->id]);
                $this->fileRepo->markAsFailed($file->id, 'No sheets discovered');
                return;
            }

            $this->sheetRepo->bulkCreate($file->id, $sheets);
            $this->importLog(LogLevel::INFO, 'excel-importer::sheet_discovery_completed', [
                'file_id' => $file->id,
                'count' => count($sheets),
            ]);

            FileSheetsScanCompleted::dispatch($file->id);
        } catch (Throwable $e) {
            $this->fileRepo->markAsFailed($file->id, $e->getMessage());
            $this->importLog(LogLevel::ERROR, 'excel-importer::sheet_discovery_failed', [
                'file_id' => $file->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(ExcelFileRegistered $event, Throwable $e): void
    {
        $this->fileRepo->markAsFailed($event->excelFileId, $e->getMessage());
        $this->importLog(LogLevel::CRITICAL, 'excel-importer::listener_failed_after_retries', [
            'file_id' => $event->excelFileId,
            'error' => $e->getMessage(),
        ]);
    }
}