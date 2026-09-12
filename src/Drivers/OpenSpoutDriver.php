<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Drivers;

use Akbarjimi\ExcelImporter\Contracts\ExcelReaderDriver;
use Akbarjimi\ExcelImporter\DTOs\RowData;
use Akbarjimi\ExcelImporter\DTOs\SheetInfo;
use OpenSpout\Reader\XLSX\Reader;

final class OpenSpoutDriver implements ExcelReaderDriver
{
    public function readRows(string $filePath, int $sheetIndex, callable $callback): void
    {
        if (! is_file($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        $reader = new Reader;
        $reader->open($filePath);

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                if ($sheet->getIndex() !== $sheetIndex) {
                    continue;
                }

                foreach ($sheet->getRowIterator() as $rowNumber => $row) {
                    $callback(new RowData(
                        cells: $this->normalizeRow($row->toArray()),
                        rowNumber: $rowNumber - 1,
                    ));
                }

                return;
            }

            throw new \RuntimeException("Sheet index {$sheetIndex} not found in [{$filePath}].");
        } finally {
            $reader->close();
        }
    }

    public function listSheets(string $filePath): array
    {
        if (! is_file($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        $reader = new Reader;
        $reader->open($filePath);

        try {
            $sheets = [];
            foreach ($reader->getSheetIterator() as $index => $sheet) {
                $sheets[] = new SheetInfo(
                    name: $sheet->getName(),
                    index: $index,
                    totalRows: $sheet->getRowCount(),
                    totalColumns: 0,
                    raw: ['name' => $sheet->getName()],
                );
            }

            return $sheets;
        } finally {
            $reader->close();
        }
    }

    /**
     * Normalize cell values so all drivers produce identical payloads.
     * Critical for cross-driver parity and row hashing.
     */
    private function normalizeRow(array $cells): array
    {
        return array_map(
            static fn ($value) => $value instanceof \DateTimeInterface
                ? $value->format('Y-m-d H:i:s')
                : $value,
            $cells,
        );
    }
}