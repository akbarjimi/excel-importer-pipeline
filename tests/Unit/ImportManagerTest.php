<?php

use Akbarjimi\ExcelImporter\Contracts\ImportHandler;
use Akbarjimi\ExcelImporter\Events\ExcelFileRegistered;
use Akbarjimi\ExcelImporter\Models\ExcelFile;
use Akbarjimi\ExcelImporter\Services\ImportManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

it('stores metadata and dispatches ExcelUploaded', function () {
    Event::fake();

    $path = 'imports/sample.xlsx';
    Storage::put($path, 'stub');

    $handler = new class implements ImportHandler {
        public function handle(int $fileId, iterable $rows): void {}
    };

    $file = app(ImportManager::class)
        ->import($path)
        ->withHandler($handler::class)
        ->dispatch();

    expect($file)->toBeInstanceOf(ExcelFile::class)
        ->and($file->file_name)->toBe('sample.xlsx');

    Event::assertDispatched(ExcelFileRegistered::class);
});