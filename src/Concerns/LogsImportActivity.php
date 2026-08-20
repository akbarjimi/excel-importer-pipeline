<?php

namespace Akbarjimi\ExcelImporter\Concerns;

use Illuminate\Support\Facades\Log;

trait LogsImportActivity
{
    protected function importLog(string $level, string $message, array $context = []): void
    {
        if (! config('excel-importer.logging.enabled', true)) {
            return;
        }

        $channels = (array) config('excel-importer.logging.channels', ['stack']);

        Log::stack($channels)->$level($message, $context);
    }

}
