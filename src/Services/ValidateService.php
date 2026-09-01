<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Services;

use Akbarjimi\ExcelImporter\Models\ExcelSheet;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Facades\Validator;

final class ValidateService
{
    public function __construct(private Config $config) {}

    public function apply(array $payload, ExcelSheet $sheet): array
    {
        $rules = $this->config->get("excel-importer-sheets.{$sheet->name}.validation", []);

        if (empty($rules)) {
            if ($this->config->get('excel-importer.strict_validation', false)) {
                throw new \RuntimeException("No validation rules loaded for sheet [{$sheet->name}].");
            }
            return [];
        }

        $validator = Validator::make($payload, $rules);

        return $validator->fails() ? $validator->errors()->toArray() : [];
    }
}