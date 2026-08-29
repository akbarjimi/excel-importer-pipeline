<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Listeners;

use Akbarjimi\ExcelImporter\Concerns\LogsImportActivity;
use Akbarjimi\ExcelImporter\Contracts\ImportHandler;
use Akbarjimi\ExcelImporter\Events\FileProcessingCompleted;
use Akbarjimi\ExcelImporter\Repositories\ExcelFileRepository;
use Akbarjimi\ExcelImporter\Repositories\ExcelRowRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

final class InvokeImportConsumer implements ShouldQueue
{
    use InteractsWithQueue;
    use LogsImportActivity;

    public function __construct(
        private readonly ExcelFileRepository $fileRepo,
        private readonly ExcelRowRepository $rowRepo,
    ) {}

    public function handle(FileProcessingCompleted $event): void
    {
        $handlerClass = $this->fileRepo->getHandler($event->fileId);
        if (!$handlerClass || !class_exists($handlerClass)) {
            $this->importLog('warning', 'No handler found for file', ['file_id' => $event->fileId]);
            return;
        }

        /** @var ImportHandler $handler */
        $handler = app($handlerClass);

        $rows = $this->rowRepo->getValidatedRowsForFile($event->fileId);

        $handler->handle($event->fileId, $rows);

        $this->logActivity('handler_invoked', ['file_id' => $event->fileId]);
    }
}