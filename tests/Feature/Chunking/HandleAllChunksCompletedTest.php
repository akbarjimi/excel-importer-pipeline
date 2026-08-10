<?php

use Akbarjimi\ExcelImporter\Listeners\HandleAllChunksCompleted;
use Akbarjimi\ExcelImporter\Events\SheetProcessingCompleted;
use Akbarjimi\ExcelImporter\Models\ExcelRowChunk;
use Akbarjimi\ExcelImporter\Models\ExcelSheet;
use Illuminate\Support\Facades\Queue;
use Akbarjimi\ExcelImporter\Jobs\MapChunkJob;

beforeEach(function () {
    Queue::fake();

    $this->sheet = ExcelSheet::factory()->create(['chunk_count' => 2, 'processed_chunks' => 2]);

    $this->chunks = ExcelRowChunk::factory()->count(2)->create([
        'excel_sheet_id' => $this->sheet->id,
        'mapping_status' => 'pending',
    ]);
});

it('dispatches MapChunkJob for each pending chunk', function () {
    // Act
    (new HandleAllChunksCompleted())->handle(new SheetProcessingCompleted($this->sheet->id));

    // Assert
    Queue::assertPushed(MapChunkJob::class, 2);
});

it('skips chunks already mapped', function () {
    $this->chunks->first()->update(['mapping_status' => 'completed']);

    (new HandleAllChunksCompleted())->handle(new SheetProcessingCompleted($this->sheet->id));

    Queue::assertPushed(MapChunkJob::class, 1);
});
