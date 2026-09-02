<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Exceptions\File;

use Akbarjimi\ExcelImporter\Exceptions\ImportException;

final class HandlerMissingException extends ImportException
{
    public static function make(): self
    {
        return new self(trans('excel-importer::file.handler_missing'));
    }
}