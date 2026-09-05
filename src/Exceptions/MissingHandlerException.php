<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Exceptions;

final class MissingHandlerException extends ImportException
{
    public static function make(): self
    {
        return new self(trans('excel-importer::handler_missing'));
    }
}