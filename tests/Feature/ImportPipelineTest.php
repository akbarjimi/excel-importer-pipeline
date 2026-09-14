<?php

declare(strict_types=1);

use Akbarjimi\ExcelImporter\Contracts\ImportHandler;
use Akbarjimi\ExcelImporter\DTOs\ValidatedRow;
use Akbarjimi\ExcelImporter\Enums\ExcelFileStatus;
use Akbarjimi\ExcelImporter\Enums\ExcelSheetStatus;
use Akbarjimi\ExcelImporter\Models\ExcelFile;
use Akbarjimi\ExcelImporter\Models\ExcelRow;
use Akbarjimi\ExcelImporter\Models\ExcelSheet;
use Akbarjimi\ExcelImporter\Services\ImportManager;
use Illuminate\Support\Facades\Storage;

final class PipelineTestHandler implements ImportHandler
{
    /** @var list<ValidatedRow> */
    public array $rows = [];

    public function handle(int $fileId, iterable $rows): void
    {
        foreach ($rows as $row) {
            $this->rows[] = $row;
        }
    }
}

beforeEach(function () {
    $stub = __DIR__.'/../stubs/1sheet3rows1header.xlsx';
    $this->relativeTargetPath = 'testing/1sheet3rows1header.xlsx';

    Storage::disk('local')->put($this->relativeTargetPath, file_get_contents($stub));

    app()->bind(PipelineTestHandler::class, fn () => new PipelineTestHandler);
});

it('runs the full pipeline to completion', function () {
    config(['queue.default' => 'sync']);

    $file = app(ImportManager::class)
        ->import($this->relativeTargetPath)
        ->withHandler(PipelineTestHandler::class)
        ->dispatch();

    $file->refresh();

    expect($file)->toBeInstanceOf(ExcelFile::class)
        ->and($file->status)->toBe(ExcelFileStatus::COMPLETED);

    $sheets = ExcelSheet::where('excel_file_id', $file->id)->get();
    expect($sheets)->toHaveCount(1)
        ->and($sheets->first()->status)->toBe(ExcelSheetStatus::COMPLETED);

    $rows = ExcelRow::whereIn('excel_sheet_id', $sheets->pluck('id'))
        ->where('status', 'validated')
        ->get();
    expect($rows)->toHaveCount(3)
        ->and(app(PipelineTestHandler::class)->rows)->toHaveCount(3);

});