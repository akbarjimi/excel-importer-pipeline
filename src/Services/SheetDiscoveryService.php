<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Services;

use Akbarjimi\ExcelImporter\Contracts\ExcelReaderDriver;
use Akbarjimi\ExcelImporter\DTOs\SheetInfo;
use Akbarjimi\ExcelImporter\Models\ExcelFile;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use RuntimeException;


final class SheetDiscoveryService
{

    public function __construct(
        private FilesystemFactory $storage,
        private ExcelReaderDriver $driver,
    )
    {
    }

    /**
     * @return list<SheetInfo>
     */
    public function discover(ExcelFile $file): array
    {
        $disk = $this->storage->disk($file->disk);

        $localPath = $this->resolveLocalPath($disk, $file->path);
        $isTemp = $localPath !== $disk->path($file->path);

        try {
            return $this->driver->listSheets($localPath);
        } finally {
            if ($isTemp && is_file($localPath)) {
                @unlink($localPath);
            }
        }
    }

    private function resolveLocalPath(Filesystem $disk, string $path): string
    {
        $direct = $disk->path($path);

        if (is_file($direct)) {
            return $direct;
        }

        // Remote disk: stream down to a temp file, preserving the extension so
        // IOFactory::createReaderForFile() can still detect the correct reader.
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $base = tempnam(sys_get_temp_dir(), 'excel_') ?: throw new RuntimeException('Unable to allocate temp file.');
        $temp = $extension !== '' ? "{$base}.{$extension}" : $base;

        if ($temp !== $base) {
            rename($base, $temp);
        }

        $source = $disk->readStream($path);
        if ($source === null) {
            @unlink($temp);
            throw new RuntimeException("Unable to open a read stream for [{$path}].");
        }

        $dest = fopen($temp, 'wb');
        stream_copy_to_stream($source, $dest);
        fclose($dest);
        fclose($source);

        return $temp;
    }
}