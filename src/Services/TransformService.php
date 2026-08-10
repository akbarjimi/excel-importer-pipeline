<?php

namespace Akbarjimi\ExcelImporter\Services;

use Akbarjimi\ExcelImporter\Models\ExcelSheet;
use Illuminate\Support\Facades\Config;

final class TransformService
{
    /**
     * Apply transformers to a row's content
     */
    public function apply(array $row, ExcelSheet $sheet): array
    {
        $transformers = Config::get('excel-importer-sheets.' . $sheet->name . '.transformers', []);
        foreach ($row as $column => $value) {
            if (isset($transformers[$column])) {
                $row[$column] = call_user_func($transformers[$column], $value);
            }
        }

        return $row;
    }
}
