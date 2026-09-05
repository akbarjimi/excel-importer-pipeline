<?php

declare(strict_types=1);

return [
    'file' => [
        'file_deleted' => 'File :file_id has been deleted. Skipping further processing.',
        'not_found' => 'Excel file [:path] not found on disk [:disk].',
        'handler_missing' => 'No handler set for import. Call withHandler() before dispatch().',
        'processing_completed' => 'File processing completed for file :file_id.',
        'sheet_limit_exceeded' => 'File contains :count sheets, which exceeds the maximum of :limit.',
        'no_sheets_to_extract' => 'No sheets found for file :file_id – marking as extracted.',
        'no_chunks_created' => 'No chunks created for file :file_id – marking as completed.',
        'handler_not_found' => 'No handler found for file :file_id.',
        'handler_invoked' => 'Handler invoked for file :file_id.',
        'processing_batch_completed' => 'Processing batch completed for file :file_id.',
        'processing_batch_failed' => 'Processing batch failed for file :file_id. Error: :error.',
        'handle_all_rows_extracted_failed' => 'HandleAllRowsExtracted listener failed for file :file_id. Error: :error.',

    ],
    'sheet' => [
        'discovery_started' => 'Starting sheet discovery for file :file_id.',
        'discovery_completed' => 'Sheets discovered for file :file_id. Count: :count.',
        'discovery_failed' => 'Sheet discovery failed for file :file_id. Error: :error.',
        'already_discovered' => 'Sheets already exist for file :file_id. Skipping discovery.',
        'extraction_success' => 'Extracted :rows rows from sheet :sheet_id.',
        'extraction_failed' => 'Extraction failed for sheet :sheet_id. Error: :error.',
        'extraction_batch_completed' => 'All sheets extracted for file :file_id.',
        'extraction_batch_failed' => 'Extraction batch failed for file :file_id. Error: :error.',
        'processing_completed' => 'Sheet :sheet_id processing completed.',
        'processing_completed_fallback' => 'Sheet :sheet_id processing completed (fallback).',
    ],
    'chunk' => [
        'created' => 'Created :chunk_cnt chunks (size :chunk_sz) for file :file_id.',
        'processed' => 'Chunk :chunk_id processed with :rows rows.',
        'processing_failed' => 'Chunk :chunk_id failed for file :file_id. Error: :error.',
        'processing_started' => 'Processing chunk :chunk_id for file :file_id.',
    ],
    'listener' => [
        'failed_after_retries' => 'Listener failed after retries for file :file_id. Error: :error.',
    ],
];
