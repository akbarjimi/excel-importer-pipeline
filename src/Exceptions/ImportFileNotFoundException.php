<?php

namespace Akbarjimi\ExcelImporter\Exceptions;

use RuntimeException;

class ImportFileNotFoundException extends RuntimeException
{
    public static function make(string $disk, string $path): self
    {
        return new self("Excel import source [{$path}] was not found on disk [{$disk}].");
    }
}
