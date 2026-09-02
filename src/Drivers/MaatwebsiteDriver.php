<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Drivers;

use Akbarjimi\ExcelImporter\Contracts\ExcelReaderDriver;
use Akbarjimi\ExcelImporter\DTOs\SheetInfo;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Row;
use PhpOffice\PhpSpreadsheet\IOFactory;

final class MaatwebsiteDriver implements ExcelReaderDriver, OnEachRow, WithChunkReading, WithStartRow
{
    private $callback = null;
    private int $chunkSize;

    public function __construct()
    {
        $this->chunkSize = (int) config('excel-importer.chunk_size', 1000);
    }

    public function readRows(string $filePath, int $sheetIndex, callable $callback): void
    {
        $this->callback = $callback;

        // Maatwebsite doesn't support selecting sheet index directly via import,
        // but we can use a custom import class with sheet selection.
        // For simplicity, we use the reader directly via PhpSpreadsheet.
        $reader = IOFactory::createReaderForFile($filePath);
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getSheet($sheetIndex);

        foreach ($sheet->getRowIterator() as $rowNumber => $row) {
            $cells = [];
            foreach ($row->getCellIterator() as $cell) {
                $cells[] = $cell->getFormattedValue();
            }
            $callback($cells, $rowNumber - 1); // 0-based index
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

    public function onRow(Row $row): void
    {
        // Not used anymore – we use direct PhpSpreadsheet in readRows.
        // Kept for compatibility.
    }

    public function chunkSize(): int
    {
        return $this->chunkSize;
    }

    public function startRow(): int
    {
        return 1; // Skip header row (if needed)
    }
}