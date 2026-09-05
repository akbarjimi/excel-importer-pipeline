<?php

return [
    'file.not_found' => 'Excel file [:path] not found on disk [:disk].',
    'file.handler_missing' => 'No handler set for import. Call withHandler() before dispatch().',
    'file.sheet_limit_exceeded' => 'File contains :count sheets, which exceeds the maximum of :limit.',
    'file.no_sheets_to_extract' => 'No sheets found for file :file_id – marking as extracted.',
    'file.no_chunks_created' => 'No chunks created for file :file_id – marking as completed.',
    'file.handler_not_found' => 'No handler found for file :file_id.',
    'file.handler_invoked' => 'Handler invoked for file :file_id.',
    'file.processing_batch_completed' => 'Processing batch completed for file :file_id.',
    'file.processing_batch_failed' => 'Processing batch failed for file :file_id. Error: :error.',
    'file.handle_all_rows_extracted_failed' => 'HandleAllRowsExtracted listener failed for file :file_id. Error: :error.',
    'file.deleted' => 'File :file_id has been deleted. Skipping further processing.',

    'sheet.discovery_started' => 'Starting sheet discovery for file :file_id.',
    'sheet.discovery_completed' => 'Sheets discovered for file :file_id. Count: :count.',
    'sheet.discovery_failed' => 'Sheet discovery failed for file :file_id. Error: :error.',
    'sheet.already_discovered' => 'Sheets already exist for file :file_id. Skipping discovery.',
    'sheet.extraction_success' => 'Extracted :rows rows from sheet :sheet_id.',
    'sheet.extraction_failed' => 'Extraction failed for sheet :sheet_id. Error: :error.',
    'sheet.extraction_batch_completed' => 'All sheets extracted for file :file_id.',
    'sheet.extraction_batch_failed' => 'Extraction batch failed for file :file_id. Error: :error.',
    'sheet.processing_completed' => 'Sheet :sheet_id processing completed.',
    'sheet.processing_completed_fallback' => 'Sheet :sheet_id processing completed (fallback).',

    'chunk.created' => 'Created :chunk_cnt chunks (size :chunk_sz) for file :file_id.',
    'chunk.processed' => 'Chunk :chunk_id processed with :rows rows.',
    'chunk.processing_failed' => 'Chunk :chunk_id failed for file :file_id. Error: :error.',
    'chunk.processing_started' => 'Processing chunk :chunk_id for file :file_id.',

    'listener.failed_after_retries' => 'Listener failed after retries for file :file_id. Error: :error.',
];