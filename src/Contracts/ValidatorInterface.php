<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Contracts;

use Akbarjimi\ExcelImporter\Models\ExcelSheet;

interface ValidatorInterface
{
    public function apply(array $payload, ExcelSheet $sheet): array;
}
