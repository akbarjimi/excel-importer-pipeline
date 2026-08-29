<?php

final class InvokeImportConsumer implements ShouldQueue
{
    use LogsImportActivity;

    public function __construct(
            private ExcelFileRepository $fileRepo,
            private ExcelRowRepository $rowRepo,
    ) {}

    public function handle(FileProcessingCompleted $event): void
    {
        $handlerClass = $this->fileRepo->getHandler($event->fileId);
        if (!$handlerClass || !class_exists($handlerClass)) {
            return;
        }

        /** @var ImportHandler $handler */
        $handler = app($handlerClass);

        $rows = $this->rowRepo->getValidatedRowsForFile($event->fileId);

        $handler->handle($event->fileId, $rows);

        $this->logActivity('handler_invoked', ['file_id' => $event->fileId]);
    }
}