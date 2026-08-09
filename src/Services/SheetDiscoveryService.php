<?php

namespace Akbarjimi\ExcelImporter\Services;

use Akbarjimi\ExcelImporter\DTOs\SheetInfo;
use Akbarjimi\ExcelImporter\Models\ExcelFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

final readonly class SheetDiscoveryService
{
    public function discover(ExcelFile $file): array
    {
        $absolutePath = Storage::disk($file->disk)->path($file->path);

        $reader = IOFactory::createReaderForFile($absolutePath);

        $worksheetsInfo = $reader->listWorksheetInfo($absolutePath);

        return array_values(array_map(
            static fn(array $info, int $index): SheetInfo => SheetInfo::fromPhpSpreadsheet($info, $index),
            $worksheetsInfo,
            array_keys($worksheetsInfo),
        ));
    }
}