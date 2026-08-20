<?php

namespace Akbarjimi\ExcelImporter\Drivers;

use Akbarjimi\ExcelImporter\Contracts\ExcelReaderDriver;
use Akbarjimi\ExcelImporter\Exceptions\SheetNotFoundException;
use OpenSpout\Reader\XLSX\Reader;

class OpenSpoutDriver implements ExcelReaderDriver
{
    public function readRows(string $filePath, int $sheetIndex, callable $callback): void
    {
        if (! is_file($filePath)) {
            throw new \InvalidArgumentException("Excel file not found at [{$filePath}].");
        }

        $reader = new Reader();
        $reader->open($filePath);

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                if ($sheet->getIndex() !== $sheetIndex) {
                    continue;
                }

                foreach ($sheet->getRowIterator() as $rowNumber => $row) {
                    // OpenSpout row numbers are 1-based; normalise to 0-based
                    $callback($this->normaliseRow($row->toArray()), $rowNumber - 1);
                }

                return;
            }

            throw new SheetNotFoundException(
                "Sheet index [{$sheetIndex}] does not exist in [{$filePath}]."
            );
        } finally {
            $reader->close();
        }
    }

    /**
     * Normalise cell values so all drivers produce identical payloads
     * (critical for cross-driver parity and row hashing).
     */
    protected function normaliseRow(array $cells): array
    {
        return array_map(function ($value) {
            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y-m-d H:i:s');
            }

            return $value;
        }, $cells);
    }
}
