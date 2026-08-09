<?php

namespace Akbarjimi\ExcelImporter\Events;

use Illuminate\Foundation\Events\Dispatchable;

final readonly class SheetsDiscovered
{
    use Dispatchable;

    public function __construct(public int $fileId)
    {
    }
}
