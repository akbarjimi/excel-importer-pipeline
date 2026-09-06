<?php

return [
    'not_found' => 'Excel file [:path] not found on disk [:disk].',
    'handler_missing' => 'No handler set for import. Call withHandler() before dispatch().',
    'sheet_limit_exceeded' => 'File contains :count sheets, which exceeds the maximum of :limit.',
    'no_sheets_to_extract' => 'No sheets found for file :file_id – marking as extracted.',
    'no_chunks_created' => 'No chunks created for file :file_id – marking as completed.',
    'handler_not_found' => 'No handler found for file :file_id.',
    'handler_invoked' => 'Handler invoked for file :file_id.',
    'processing_batch_completed' => 'Processing batch completed for file :file_id.',
    'processing_batch_failed' => 'Processing batch failed for file :file_id. Error: :error.',
    'handle_all_rows_extracted_failed' => 'HandleAllRowsExtracted listener failed for file :file_id. Error: :error.',
    'deleted' => 'File :file_id has been deleted. Skipping further processing.',
];