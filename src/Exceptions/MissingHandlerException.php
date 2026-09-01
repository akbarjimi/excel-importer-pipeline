<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Exceptions;

use RuntimeException;

final class MissingHandlerException extends RuntimeException
{
    public static function make(): self
    {
        return new self(trans('excel-importer::handler_missing'));

    }
}