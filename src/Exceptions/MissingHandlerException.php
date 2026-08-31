<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Exceptions;

use RuntimeException;

final class MissingHandlerException extends RuntimeException
{
    public static function make(): self
    {
        return new self('No handler set for this import. Call withHandler() before dispatch().');
    }
}