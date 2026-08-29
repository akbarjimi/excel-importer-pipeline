<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Contracts;

use Akbarjimi\ExcelImporter\DTOs\ValidatedRow;

interface ImportHandler
{
    /**
     * @param int $fileId The ID of the imported file.
     * @param iterable<ValidatedRow> $rows Stream of validated rows.
     */
    public function handle(int $fileId, iterable $rows): void;
}