<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Services;

use Akbarjimi\ExcelImporter\Models\ExcelSheet;
use Illuminate\Contracts\Config\Repository as Config;

final class TransformService
{
    public function __construct(private Config $config) {}

    public function apply(array $row, ExcelSheet $sheet): array
    {
        $transformers = $this->config->get("excel-importer-sheets.{$sheet->name}.transformers", []);
        foreach ($row as $column => $value) {
            if (isset($transformers[$column]) && is_callable($transformers[$column])) {
                $row[$column] = $transformers[$column]($value);
            }
        }

        return $row;
    }
}