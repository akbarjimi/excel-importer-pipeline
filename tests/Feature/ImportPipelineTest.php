<?php

use Akbarjimi\ExcelImporter\Enums\ExcelFileStatus;
use Akbarjimi\ExcelImporter\Enums\ExcelSheetStatus;
use Akbarjimi\ExcelImporter\Events\AllRowsExtracted;
use Akbarjimi\ExcelImporter\Events\ExcelFileRegistered;
use Akbarjimi\ExcelImporter\Events\SheetReadyForExtraction;
use Akbarjimi\ExcelImporter\Models\ExcelFile;
use Akbarjimi\ExcelImporter\Models\ExcelSheet;
use Akbarjimi\ExcelImporter\Services\ImportManager;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    $this->stubFileName = '1sheet3rows1header.xlsx';
    $this->driver = config('excel-importer.default_disk');

    $this->sourcePath = __DIR__.'/../stubs/'.$this->stubFileName;

    $this->relativeTargetPath = 'testing/'.$this->stubFileName;
    $this->absoluteTargetPath = Storage::disk($this->driver)->path($this->relativeTargetPath);

    if (! is_dir(dirname($this->absoluteTargetPath))) {
        mkdir(dirname($this->absoluteTargetPath), 0777, true);
    }

    copy($this->sourcePath, $this->absoluteTargetPath);

    expect(file_exists($this->absoluteTargetPath))
        ->toBeTrue("Failed to copy test Excel file into storage: {$this->absoluteTargetPath}");
});

it('stores Excel file metadata in database', function () {
    Event::fake([ExcelFileRegistered::class]);

    $manager = app(ImportManager::class);

    $file = $manager->import($this->relativeTargetPath);

    expect($file)
        ->toBeInstanceOf(ExcelFile::class)
        ->file_name->toBe($this->stubFileName)
        ->path->toBe($this->relativeTargetPath)
        ->status->toBe(ExcelFileStatus::PENDING);

    assertDatabaseHas('excel_files', [
        'id' => $file->id,
        'path' => $this->relativeTargetPath,
        'file_name' => $this->stubFileName,
    ]);

    Event::assertDispatched(ExcelFileRegistered::class, fn ($event) => $event->file->id === $file->id);
});

it('stores Excel sheet metadata in database after file is uploaded', function () {
    Event::fake([SheetReadyForExtraction::class]);
    $manager = app(ImportManager::class);

    $file = $manager->import($this->relativeTargetPath);

    $sheets = ExcelSheet::where('excel_file_id', $file->id)->get();

    expect($sheets)
        ->not->toBeEmpty()
        ->count()->toBeGreaterThan(0);

    $firstSheet = $sheets->first();

    expect($firstSheet->name)->toBeString()->not->toBeEmpty();
    expect($firstSheet->rows_count)->toBeGreaterThan(0);
    expect($firstSheet->status)->toBe(ExcelSheetStatus::PENDING);
});

it('dispatches sheet events after importing Excel file', function () {
    Event::fake([
        SheetReadyForExtraction::class,
    ]);

    $manager = app(ImportManager::class);
    $manager->import($this->relativeTargetPath);

    Event::assertDispatched(SheetReadyForExtraction::class);

    $sheet = ExcelSheet::first();
    expect($sheet)->not->toBeNull();
    expect($sheet->status)->toBe(ExcelSheetStatus::PENDING);
});

it('extracts rows and fires AllSheetsDispatched when last sheet is processed', function () {
    Event::fake([AllRowsExtracted::class]);

    $manager = app(ImportManager::class);
    $file = $manager->import($this->relativeTargetPath);

    $this->assertDatabaseCount('excel_rows', 3);

    $this->assertNotNull($file->excelSheets->first()->rows_extracted_at);

    Event::assertDispatched(AllRowsExtracted::class, function ($event) use ($file) {
        return $event->fileId === $file->getKey();
    });
});
