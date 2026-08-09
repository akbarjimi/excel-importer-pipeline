<?php

namespace Akbarjimi\ExcelImporter\Events;

use Illuminate\Foundation\Events\Dispatchable;

final readonly class SheetDiscovered
{
    use Dispatchable;

    public function __construct(public int $sheetId)
    {
    }
}