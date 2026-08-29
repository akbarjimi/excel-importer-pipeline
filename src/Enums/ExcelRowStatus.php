<?php

namespace Akbarjimi\ExcelImporter\Enums;

enum ExcelRowStatus: string
{
    case PENDING = 'pending';                               // Awaiting validation/processing
    case VALIDATING = 'validating';                         // In validation phase
    case FAILED_VALIDATION = 'failed_validation';           // Didn't pass validation
    case TRANSFORMING = 'transforming';                     // Being transformed or normalized
    case FAILED_TRANSFORMATION = 'failed_transformation';   // Transformation error
    case PROCESSED = 'processed';                           // Row handled successfully
    case MAPPED = 'mapped';

    case VALIDATED = 'validated';
}
