<?php

namespace Akbarjimi\ExcelImporter\Contracts;

use Akbarjimi\ExcelImporter\DTOs\SheetInfo;

interface ExcelReaderDriver
{
    public function readRows(string $filePath, int $sheetIndex, callable $callback): void;
    // callback signature: fn(RowData $row): void

    /**
     * @return list<SheetInfo>
     */
    public function listSheets(string $filePath): array;
}
