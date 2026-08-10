<?php

namespace Akbarjimi\ExcelImporter\Contracts;

interface ExcelReaderDriver
{
    /**
     * Read rows from an Excel file and invoke the callback for each row.
     *
     * @param string $filePath Absolute path to the Excel file
     * @param int $sheetIndex Zero-based sheet index
     * @param callable(array $row, int $rowIndex): void $callback
     * @return void
     */
    public function readRows(string $filePath, int $sheetIndex, callable $callback): void;
}
