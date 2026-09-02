<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Drivers;

use Akbarjimi\ExcelImporter\Contracts\ExcelReaderDriver;
use Akbarjimi\ExcelImporter\DTOs\SheetInfo;
use PhpOffice\PhpSpreadsheet\IOFactory;

final class MaatwebsiteDriver implements ExcelReaderDriver
{
    public function readRows(string $filePath, int $sheetIndex, callable $callback): void
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getSheet($sheetIndex);

        foreach ($sheet->getRowIterator() as $rowNumber => $row) {
            $cells = [];
            foreach ($row->getCellIterator() as $cell) {
                $cells[] = $cell->getFormattedValue();
            }
            $callback($cells, $rowNumber - 1); // 0-based
        }

        $spreadsheet->disconnect();
    }

    public function listSheets(string $filePath): array
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $worksheetsInfo = $reader->listWorksheetInfo($filePath);

        return array_values(array_map(
            static fn(array $info, int $index): SheetInfo => SheetInfo::fromPhpSpreadsheet($info, $index),
            $worksheetsInfo,
            array_keys($worksheetsInfo),
        ));
    }
}