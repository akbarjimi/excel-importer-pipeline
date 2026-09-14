<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Contracts;

use Akbarjimi\ExcelImporter\DTOs\SheetInfo;

interface ExcelReaderDriver
{
    /**
     * Read rows from a sheet and dispatch each as a RowData to the handler.
     *
     * Implementations MUST invoke $handler->handle(new RowData(...)) for every row.
     */
    public function readRows(string $filePath, int $sheetIndex, RowHandler $handler): void;

    /**
     * @return list<SheetInfo>
     */
    public function listSheets(string $filePath): array;
}
