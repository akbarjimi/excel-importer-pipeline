<?php

declare(strict_types=1);

use Akbarjimi\ExcelImporter\Drivers\OpenSpoutDriver;
use Akbarjimi\ExcelImporter\DTOs\SheetInfo;

it('lists every sheet in an xlsx file', function () {
    $path = __DIR__ . '/../../stubs/2sheets2rows.xlsx';

    expect(is_file($path))->toBeTrue("Stub missing at {$path}");

    $sheets = (new OpenSpoutDriver)->listSheets($path);

    expect($sheets)->toBeArray()->toHaveCount(2);
    expect($sheets[0])->toBeInstanceOf(SheetInfo::class);
    expect($sheets[1])->toBeInstanceOf(SheetInfo::class);
    expect($sheets[0]->name)->toBe('Sheet2');
    expect($sheets[1]->name)->toBe('Sheet3');
    expect($sheets[0]->raw['name'])->toBe('Sheet2');
    expect($sheets[1]->raw['name'])->toBe('Sheet3');
});