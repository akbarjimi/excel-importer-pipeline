<?php

namespace Akbarjimi\ExcelImporter\Enums;

enum ExcelChunkStatus: string
{
    case PENDING = 'pending';               // File uploaded, no action yet
    case READING = 'reading';               // File being parsed (sheets & rows)
    case ROWS_EXTRACTED = 'rows_extracted'; // All rows read from Excel
    case PROCESSING = 'processing';         // Chunks dispatched or in progress
    case COMPLETED = 'completed';           // All chunks processed successfully
    case FAILED = 'failed';                 // General failure at any stage
}
