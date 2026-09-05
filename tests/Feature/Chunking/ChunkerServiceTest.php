<?php

use Akbarjimi\ExcelImporter\Jobs\ProcessChunkJob;
use Akbarjimi\ExcelImporter\Models\ExcelFile;
use Akbarjimi\ExcelImporter\Models\ExcelRow;
use Akbarjimi\ExcelImporter\Services\ChunkerService;
use Illuminate\Support\Facades\Bus;

it('creates deterministic chunks and dispatches jobs after commit', function () {
    Bus::fake();

    $file = ExcelFile::factory()->create(); // not hasExcelSheets

    // Create sheets with unique indices
    $sheet1 = ExcelSheet::factory()->for($file)->create(['sheet_index' => 0]);
    $sheet2 = ExcelSheet::factory()->for($file)->create(['sheet_index' => 1]);

    ExcelRow::factory()->count(1001)->for($sheet1)->create();
    ExcelRow::factory()->count(1000)->for($sheet2)->create();

    $chunks = app(ChunkerService::class, ['chunkSize' => 1000])
        ->createChunksForFile($file->fresh());

    expect($chunks)->toHaveCount(3);
    expect($chunks->pluck('size')->sort()->values()->all())->toBe([1, 1000, 1000]);

    $chunks->each(fn($c) => ProcessChunkJob::dispatch($c->getKey())->afterCommit());

    Bus::assertDispatched(ProcessChunkJob::class, 3);
});