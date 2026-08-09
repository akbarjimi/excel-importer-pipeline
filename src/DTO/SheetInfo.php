<?php

namespace Akbarjimi\ExcelImporter\DTOs;

final readonly class SheetInfo
{
    public function __construct(
        public string $name,
        public int    $index,
        public int    $totalRows,
        public int    $totalColumns,
        public array  $raw = [],
    )
    {
    }

    public static function fromPhpSpreadsheet(array $info, int $index): self
    {
        return new self(
            name: $info['worksheetName'] ?? "Sheet{$index}",
            index: $index,
            totalRows: (int)($info['totalRows'] ?? 0),
            totalColumns: (int)($info['totalColumns'] ?? 0),
            raw: $info,
        );
    }
}
