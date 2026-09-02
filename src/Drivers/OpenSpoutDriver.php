<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Drivers;

use Akbarjimi\ExcelImporter\Contracts\ExcelReaderDriver;
use Akbarjimi\ExcelImporter\DTOs\SheetInfo;
use Akbarjimi\ExcelImporter\Exceptions\SheetNotFoundException;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Common\Exception\IOException;

final class OpenSpoutDriver implements ExcelReaderDriver
{
    public function readRows(string $filePath, int $sheetIndex, callable $callback): void
    {
        if (!is_file($filePath)) {
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
                    $callback($this->normaliseRow($row->toArray()), $rowNumber - 1);
                }

                return;
            }

            throw new SheetNotFoundException("Sheet index [{$sheetIndex}] does not exist in [{$filePath}].");
        } finally {
            $reader->close();
        }
    }

    public function listSheets(string $filePath): array
    {
        if (!is_file($filePath)) {
            throw new \InvalidArgumentException("Excel file not found at [{$filePath}].");
        }

        $reader = new Reader();
        $reader->open($filePath);

        $sheets = [];
        foreach ($reader->getSheetIterator() as $index => $sheet) {
            $sheets[] = new SheetInfo(
                name: $sheet->getName(),
                index: $index,
                totalRows: $sheet->getRowCount(),
                totalColumns: 0, // OpenSpout doesn't provide column count easily
                raw: ['name' => $sheet->getName()]
            );
        }

        $reader->close();

        return $sheets;
    }

    private function normaliseRow(array $cells): array
    {
        return array_map(function ($value) {
            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y-m-d H:i:s');
            }
            return $value;
        }, $cells);
    }
}