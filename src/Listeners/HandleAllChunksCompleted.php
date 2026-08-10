<?php

namespace Akbarjimi\ExcelImporter\Listeners;

use Akbarjimi\ExcelImporter\Events\SheetProcessingCompleted;
use Akbarjimi\ExcelImporter\Models\ExcelSheet;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

final class HandleAllChunksCompleted
{
    public function __construct()
    {
    }

    public function handle(SheetProcessingCompleted $event): void
    {
        $sheet = ExcelSheet::with('excelRowChunks')->findOrFail($event->sheetId);

        foreach ($sheet->excelRowChunks as $chunk) {
            if ($chunk->mapping_status !== 'completed') {
                Queue::push(new MapChunkJob($chunk->id));
            }
        }

        Log::info('Dispatched MapChunkJob(s) for sheet', [
            'sheet_id' => $sheet->id,
            'chunks' => $sheet->excelRowChunks->count(),
        ]);
    }
}
