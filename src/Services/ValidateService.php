<?php

namespace Akbarjimi\ExcelImporter\Services;

use Akbarjimi\ExcelImporter\Models\ExcelSheet;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;

final class ValidateService
{
    public function apply(array $payload, ExcelSheet $sheet): array
    {
        $rules = Config::get('excel-importer-sheets.'.$sheet->name.'.validation', []);
        if (empty($rules)) {
            if (Config::get('excel-importer.strict_validation', false)) {
                throw new \RuntimeException("No validation rules loaded for this sheet.");
            }
            return [];
        }

        $validator = Validator::make($payload, $rules);

        return $validator->fails() ? $validator->errors()->toArray() : [];
    }
}
