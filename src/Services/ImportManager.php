<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Services;

use Akbarjimi\ExcelImporter\Events\ExcelFileRegistered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Entry point of the import pipeline.
 *
 * Registers an already-stored spreadsheet and hands it over to the
 * asynchronous pipeline by raising {@see ExcelFileRegistered}. No file
 * content is read here: this method is deliberately cheap enough to be
 * called synchronously from an HTTP controller.
 */
final  class ImportManager
{
    public function import(string $path, ?string $disk = null): PendingImport
    {
        $disk ??= config('excel-importer.default_disk', config('filesystems.default'));
        return new PendingImport($path, $disk);
    }
}