<?php

namespace Akbarjimi\ExcelImporter\Drivers;

use Akbarjimi\ExcelImporter\Contracts\ExcelReaderDriver;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Row;

class MaatwebsiteDriver implements ExcelReaderDriver, OnEachRow, WithChunkReading, WithStartRow
{
    private $callback;
    private int $chunkSize;

    public function __construct()
    {
        $this->chunkSize = config('excel-importer.chunk_size', 1000);
    }

    public function readRows(string $filePath, int $sheetIndex, callable $callback): void
    {
        $this->callback = $callback;

        Excel::import($this, $filePath, null, \Maatwebsite\Excel\Excel::XLSX);
    }

    public function onRow(Row $row): void
    {
        if (!$this->callback) {
            return;
        }

        // Extract raw data with all preservation flags
        $rowData = $row->toArray(null, true, true, false);
        $rowIndex = $row->getIndex();

        call_user_func($this->callback, $rowData, $rowIndex);
    }

    public function chunkSize(): int
    {
        return $this->chunkSize;
    }

    public function startRow(): int
    {
        return 1; // Skip header row
    }
}
