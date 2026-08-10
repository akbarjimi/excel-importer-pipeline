<?php

namespace Akbarjimi\ExcelImporter\Concerns;

trait LogsImportActivity
{
    protected function importLog(string $level, string $message, array $context = []): void
    {
        if (!config('excel-importer.logging.enabled', true)) {
            return;
        }

        Log::channel(config('excel-importer.logging.channels', ['stack']))
            ->$level($message, $context);
    }
}
