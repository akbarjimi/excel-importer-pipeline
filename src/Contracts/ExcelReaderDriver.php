<?php

namespace Akbarjimi\ExcelImporter\Contracts;

interface ExcelReaderDriver
{
    public function readRows(string $filePath, int $sheetIndex, callable $callback): void;

    public function listSheets(string $filePath): array;
}