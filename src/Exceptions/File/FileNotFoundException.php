<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Exceptions\File;

use Akbarjimi\ExcelImporter\Exceptions\ImportException;

final class FileNotFoundException extends ImportException
{
    public static function make(string $disk, string $path): self
    {
        return new self(trans('excel-importer::file.not_found', ['disk' => $disk, 'path' => $path]));
    }
}