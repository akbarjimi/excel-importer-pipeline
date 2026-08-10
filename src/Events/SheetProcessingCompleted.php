<?php

namespace Akbarjimi\ExcelImporter\Events;

final readonly class SheetProcessingCompleted
{
    public function __construct(
        public int $sheetId,
    ) {}
}
