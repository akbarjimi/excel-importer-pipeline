<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Concerns;

use Illuminate\Support\Facades\Log;

trait LogsImportActivity
{
    /**
     * Write a structured log entry to the package channel.
     *
     * @param  array<string, mixed>  $context
     */
    protected function importLog(string $level, string $message, array $context = []): void
    {
        Log::channel(config('excel-importer.logging.channels', config('logging.default', 'stack')))
            ->log($level, $message, array_merge(['package' => 'excel-importer'], $context));
    }

}
