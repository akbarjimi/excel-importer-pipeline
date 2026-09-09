<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Contracts;

use Akbarjimi\ExcelImporter\DTOs\SheetInfo;
use Akbarjimi\ExcelImporter\Models\ExcelFile;

interface SheetDiscoveryInterface
{
    /**
     * @return list<SheetInfo>
     */
    public function discover(ExcelFile $file): array;
}