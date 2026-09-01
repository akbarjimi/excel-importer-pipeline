<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Exceptions;

use RuntimeException;

final class EmptySheetException extends RuntimeException
{
    public static function forFile(int $fileId): self
    {
        return new self(sprintf('No sheets discovered for Excel file ID [%d].', $fileId));
    }
}