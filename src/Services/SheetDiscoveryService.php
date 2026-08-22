<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Services;

use Akbarjimi\ExcelImporter\DTOs\SheetInfo;
use Akbarjimi\ExcelImporter\Models\ExcelFile;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

final readonly class SheetDiscoveryService
{
    /**
     * Read only the workbook's structural metadata (sheet names + dimensions).
     * This never materialises cell data, so it stays cheap even for very large files.
     *
     * @return list<SheetInfo>
     */
    public function discover(ExcelFile $file): array
    {
        $disk = Storage::disk($file->disk);

        // PhpSpreadsheet requires a local, seekable path. A local disk exposes one
        // directly; a remote disk (s3, gcs, ...) must be streamed to a temp file first.
        $localPath = $this->resolveLocalPath($disk, $file->path);
        $isTemp = $localPath !== $disk->path($file->path);

        try {
            $reader = IOFactory::createReaderForFile($localPath);

            $worksheetsInfo = $reader->listWorksheetInfo($localPath);

            return array_values(array_map(
                static fn(array $info, int $index): SheetInfo => SheetInfo::fromPhpSpreadsheet($info, $index),
                $worksheetsInfo,
                array_keys($worksheetsInfo),
            ));
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
        $base = tempnam(sys_get_temp_dir(), 'excel_')
            ?: throw new RuntimeException('Unable to allocate a temporary file.');
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
