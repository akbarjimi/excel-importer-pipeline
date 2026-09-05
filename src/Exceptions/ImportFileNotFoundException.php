<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Exceptions;

final class ImportFileNotFoundException extends ImportException
{
    public static function make(string $disk, string $path): self
    {
        return new self(trans('excel-importer::file_not_found', ['disk' => $disk, 'path' => $path]));
    }
}