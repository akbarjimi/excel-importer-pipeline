<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Enums;

enum ExcelFileStatus: string
{
    case PENDING = 'pending';
    case READING = 'reading';
    case ROWS_EXTRACTED = 'rows_extracted';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    /**
     * Define allowed transitions.
     */
    public function canTransitionTo(self $new): bool
    {
        return match ($this) {
            self::PENDING => in_array($new, [self::READING, self::FAILED]),
            self::READING => in_array($new, [self::ROWS_EXTRACTED, self::FAILED]),
            self::ROWS_EXTRACTED => in_array($new, [self::PROCESSING, self::FAILED]),
            self::PROCESSING => in_array($new, [self::COMPLETED, self::FAILED]),
            self::COMPLETED, self::FAILED => false,
        };
    }
}