<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Contracts;

use Akbarjimi\ExcelImporter\Models\ExcelSheet;

interface RowExtractorInterface
{
    public function extract(ExcelSheet $sheet): int;
}