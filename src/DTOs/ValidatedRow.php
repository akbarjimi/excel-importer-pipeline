<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\DTOs;

final readonly class ValidatedRow
{
    public function __construct(
        public int   $rowIndex,
        public array $data,
    ) {
    }
}