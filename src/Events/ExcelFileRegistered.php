<?php

namespace Akbarjimi\ExcelImporter\Events;

use Illuminate\Foundation\Events\Dispatchable;

final readonly class ExcelFileRegistered
{
    use Dispatchable;

    public function __construct(public int $fileId)
    {
    }
}
