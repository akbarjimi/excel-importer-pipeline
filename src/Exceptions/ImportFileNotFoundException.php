<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Exceptions;

use RuntimeException;

final class ImportFileNotFoundException extends RuntimeException
{
    public static function make(string $disk, string $path): self
    {
        return new self(trans('excel-importer::file_not_found', ['disk' => $disk, 'path' => $path]));
    }
}