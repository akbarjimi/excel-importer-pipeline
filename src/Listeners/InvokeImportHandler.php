<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Listeners;

use Akbarjimi\ExcelImporter\Concerns\LogsImportActivity;
use Akbarjimi\ExcelImporter\Contracts\ImportHandler;
use Akbarjimi\ExcelImporter\Enums\LogLevel;
use Akbarjimi\ExcelImporter\Events\FileProcessingCompleted;
use Akbarjimi\ExcelImporter\Repositories\ExcelFileRepository;
use Akbarjimi\ExcelImporter\Repositories\ExcelRowRepository;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Queue\InteractsWithQueue;

final class InvokeImportHandler implements ShouldQueueAfterCommit
{
    use InteractsWithQueue;
    use LogsImportActivity;

    public function __construct(
        private readonly ExcelFileRepository $fileRepo,
        private readonly ExcelRowRepository $rowRepo,
    ) {
    }

    public function viaQueue(): string
    {
        return config('excel-importer.queue', 'default');
    }

    public function tags(): array
    {
        return ['excel-handler', "file:{$this->event?->fileId}"];
    }

    public function handle(FileProcessingCompleted $event): void
    {
        $handlerClass = $this->fileRepo->getHandler($event->fileId);

        if (!$handlerClass || !class_exists($handlerClass)) {
            $this->importLog(LogLevel::WARNING, "No handler found for file {$event->fileId}.");
            return;
        }

        /** @var ImportHandler $handler */
        $handler = app($handlerClass);

        $rows = $this->rowRepo->getValidatedRowsForFile($event->fileId);

        $handler->handle($event->fileId, $rows);

        $this->importLog(LogLevel::INFO, "Handler invoked for file {$event->fileId}.");
    }
}