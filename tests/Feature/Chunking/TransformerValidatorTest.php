<?php

use Akbarjimi\ExcelImporter\Models\ExcelSheet;
use Akbarjimi\ExcelImporter\Services\TransformService;
use Akbarjimi\ExcelImporter\Services\ValidateService;

it('applies transformer correctly', function () {
    $sheet = ExcelSheet::factory()->make(['name' => 'Sheet1']);
    $rowContent = ['A1' => 'hello', 'B1' => '123'];

    $transform = app(TransformService::class);
    $transformed = $transform->apply($rowContent, $sheet); // remove load(), pass sheet directly

    expect($transformed['A1'])->toBe('HELLO');
    expect($transformed['B1'])->toBe('123');
});

it('applies validator correctly for valid rows', function () {
    $sheet = ExcelSheet::factory()->make(['name' => 'Sheet1']);
    $validRow = ['A1' => 'HELLO', 'B1' => 'john.doe@mail.com', 'C1' => 31];

    $validate = app(ValidateService::class);
    $errorsValid = $validate->apply($validRow, $sheet); // remove load()

    expect($errorsValid)->toBeEmpty();
});

it('applies validator correctly for invalid rows', function () {
    $sheet = ExcelSheet::factory()->make(['name' => 'Sheet1']);
    $invalidRow = ['A1' => '', 'B1' => 'john.doe@mail.com', 'C1' => '30'];

    $validate = app(ValidateService::class);
    $errorsInvalid = $validate->apply($invalidRow, $sheet); // remove load()

    expect($errorsInvalid)->not()->toBeEmpty();
    expect($errorsInvalid)->toHaveKeys(['A1', 'C1']);
});