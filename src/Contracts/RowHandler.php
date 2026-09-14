<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Contracts;

use Akbarjimi\ExcelImporter\DTOs\RowData;

interface RowHandler
{
    public function handle(RowData $row): void;
}
