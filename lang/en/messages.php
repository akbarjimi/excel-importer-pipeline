<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Import Pipeline Messages
    |--------------------------------------------------------------------------
    |
    | All user-facing and log strings produced by the import pipeline.
    | Publish this file and provide a translation to support other locales:
    |
    |     php artisan vendor:publish --tag=excel-importer-lang
    |
    */

    'file_not_found'        => 'Import file not found on disk [:disk] at path [:path].',
    'sheet_limit_exceeded'  => 'Import rejected: the workbook contains :count sheets, which exceeds the configured limit of :limit.',
    'file_missing_on_retry' => 'Import file [:file_id] no longer exists; skipping listener.',
    'sheets_already_exist'  => 'Sheets for file [:file_id] were already persisted; re-emitting scan completed event.',
    'discovery_failed'      => 'Sheet discovery failed for file [:file_id]: :message',
    'extraction_failed'     => 'Batch extraction failed for file [:file_id]: :message',
];
