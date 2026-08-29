<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Services;

use Akbarjimi\ExcelImporter\Enums\ExcelFileStatus;
use Akbarjimi\ExcelImporter\Events\ExcelFileRegistered;
use Akbarjimi\ExcelImporter\Exceptions\ImportFileNotFoundException;
use Akbarjimi\ExcelImporter\Models\ExcelFile;
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
class ImportManager
{
    /**
     * Register a spreadsheet that exists on a filesystem disk.
     *
     * @param string $path Path relative to the disk root, e.g. "imports/orders.xlsx".
     * @param string|null $disk Filesystem disk name (local, s3, ...); defaults to the package config.
     *
     * @throws ImportFileNotFoundException When the file cannot be found on the given disk.
     */
    public function import(string $path, ?string $disk = null, ?string $handler = null): ExcelFile
    {
        $disk ??= config('excel-importer.default_disk', config('filesystems.default'));

        $storage = Storage::disk($disk);

        if (!$storage->exists($path)) {
            throw ImportFileNotFoundException::make($disk, $path);
        }

        return DB::transaction(function () use ($storage, $path, $disk): ExcelFile {
            $file = ExcelFile::query()->create([
                'file_name' => basename($path),
                'path' => $path,
                'disk' => $disk,
                'size' => $storage->size($path),
                'status' => ExcelFileStatus::PENDING,
                'meta' => ['handler' => $handler],
            ]);

            // Dispatching inside the transaction is safe because the event
            // implements ShouldDispatchAfterCommit: listeners (and therefore
            // queued jobs) only run once this row is committed and visible
            // to queue workers.
            ExcelFileRegistered::dispatch($file->id);

            return $file;
        });
    }
}
