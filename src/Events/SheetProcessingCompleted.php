<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Events;

final class SheetProcessingCompleted
{
    public function __construct(public readonly int $sheetId) {}
}