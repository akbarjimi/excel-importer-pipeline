<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Enums;

enum ExcelSheetStatus: string
{
    case PENDING = 'pending';               // Discovered but not yet processed
    case EXTRACTING = 'extracting';         // Row extraction in progress
    case EXTRACTED = 'extracted';           // All rows extracted
    case CHUNKS_DISPATCHED = 'chunks_dispatched'; // Chunks sent to queue
    case COMPLETED = 'completed';           // All chunks processed successfully
    case FAILED = 'failed';                 // Failed at any stage
}