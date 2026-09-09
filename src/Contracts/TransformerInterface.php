<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Contracts;

use Akbarjimi\ExcelImporter\Models\ExcelSheet;

interface TransformerInterface
{
    public function transform(array $mappedRow, ExcelSheet $sheet): array;
}
