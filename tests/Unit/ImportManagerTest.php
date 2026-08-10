<?php

use Akbarjimi\ExcelImporter\Events\ExcelFileRegistered;
use Akbarjimi\ExcelImporter\Models\ExcelFile;
use Akbarjimi\ExcelImporter\Services\ImportManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

it('stores metadata and dispatches ExcelUploaded', function () {
    Event::fake();

    $path = 'imports/sample.xlsx';
    Storage::put($path, 'stub');

    $file = app(ImportManager::class)->import($path);

    expect($file)->toBeInstanceOf(ExcelFile::class)
        ->and($file->file_name)->toBe('sample.xlsx');

    Event::assertDispatched(ExcelFileRegistered::class);
});
