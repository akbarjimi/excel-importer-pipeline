<?php

declare(strict_types=1);

use Akbarjimi\ExcelImporter\Contracts\RowHandler;
use Akbarjimi\ExcelImporter\Drivers\OpenSpoutDriver;
use Akbarjimi\ExcelImporter\DTOs\RowData;
use Akbarjimi\ExcelImporter\DTOs\SheetInfo;

it('lists every sheet in an xlsx file', function () {
    $path = __DIR__ . '/../../stubs/2sheets2rows.xlsx';

    expect(is_file($path))->toBeTrue("Stub missing at {$path}");

    $sheets = (new OpenSpoutDriver)->listSheets($path);

    expect($sheets)->toBeArray()->toHaveCount(2)
        ->and($sheets[0])->toBeInstanceOf(SheetInfo::class)
        ->and($sheets[1])->toBeInstanceOf(SheetInfo::class)
        ->and($sheets[0]->name)->toBe('Sheet2')
        ->and($sheets[1]->name)->toBe('Sheet3')
        ->and($sheets[0]->raw['name'])->toBe('Sheet2')
        ->and($sheets[1]->raw['name'])->toBe('Sheet3');
});

it('reads rows from a sheet and passes them to the handler', function () {
    $path = __DIR__ . '/../../stubs/2sheets2rows.xlsx';

    $handler = new class implements RowHandler {
        /** @var list<RowData> */
        public array $rows = [];

        public function handle(RowData $row): void
        {
            $this->rows[] = $row;
        }
    };

    (new OpenSpoutDriver)->readRows($path, 1, $handler);

    expect($handler->rows)->toHaveCount(2)
        ->and($handler->rows[0])->toBeInstanceOf(RowData::class)
        ->and($handler->rows[0]->rowNumber)->toBe(0)
        ->and($handler->rows[0]->cells)->toBe(['A' => 1, 'B' => 1])
        ->and($handler->rows[1]->rowNumber)->toBe(1)
        ->and($handler->rows[1]->cells)->toBe(['A' => 2, 'B' => 2]);
});

it('throws when the sheet index does not exist', function () {
    $path = __DIR__ . '/../../stubs/2sheets2rows.xlsx';

    $handler = new class implements RowHandler {
        public function handle(RowData $row): void
        {
        }
    };

    expect(fn() => (new OpenSpoutDriver)->readRows($path, 99, $handler))
        ->toThrow(RuntimeException::class, 'Sheet index 99 not found');
});

it('throws when the file does not exist', function () {
    $handler = new class implements RowHandler {
        public function handle(RowData $row): void
        {
        }
    };

    expect(fn() => (new OpenSpoutDriver)->readRows('/nonexistent.xlsx', 0, $handler))
        ->toThrow(InvalidArgumentException::class, 'File not found');
});