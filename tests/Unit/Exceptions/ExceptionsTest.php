<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Tests\Unit\Exceptions;

use Akbarjimi\ExcelImporter\Exceptions\File\FileNotFoundException;
use Akbarjimi\ExcelImporter\Exceptions\File\HandlerMissingException;
use Akbarjimi\ExcelImporter\Exceptions\ImportException;
use Akbarjimi\ExcelImporter\Exceptions\ImportFileNotFoundException;
use Akbarjimi\ExcelImporter\Exceptions\MissingHandlerException;
use Akbarjimi\ExcelImporter\Exceptions\Sheet\EmptySheetException;
use Akbarjimi\ExcelImporter\Exceptions\Sheet\SheetNotFoundException;

/**
 * Test all custom exceptions.
 *
 * Ensures exceptions are thrown with correct messages and types.
 *
 * @group exceptions
 */
describe('Exceptions', function () {
    it('FileNotFoundException throws with correct message', function () {
        $exception = FileNotFoundException::make('local', 'test.xlsx');

        expect($exception)
            ->toBeInstanceOf(ImportException::class)
            ->getMessage()->toContain('test.xlsx')
            ->getMessage()->toContain('local');
    });

    it('HandlerMissingException throws with correct message', function () {
        $exception = HandlerMissingException::make();

        expect($exception)
            ->toBeInstanceOf(ImportException::class)
            ->getMessage()->toContain('handler');
    });

    it('ImportFileNotFoundException throws with correct message', function () {
        $exception = ImportFileNotFoundException::make('s3', 'path/file.xlsx');

        expect($exception)
            ->toBeInstanceOf(ImportException::class)
            ->getMessage()->toContain('path/file.xlsx');
    });

    it('EmptySheetException throws with correct message', function () {
        $exception = EmptySheetException::forFile(123);

        expect($exception)
            ->toBeInstanceOf(ImportException::class)
            ->getMessage()->toContain('123');
    });

    it('SheetNotFoundException throws with correct message', function () {
        $exception = new SheetNotFoundException('Sheet not found');

        expect($exception)
            ->toBeInstanceOf(ImportException::class)
            ->getMessage()->toBe('Sheet not found');
    });

    it('MissingHandlerException (alias) throws with correct message', function () {
        $exception = MissingHandlerException::make();

        expect($exception)
            ->toBeInstanceOf(ImportException::class)
            ->getMessage()->toContain('handler');
    });

    it('all exceptions extend ImportException', function () {
        expect(FileNotFoundException::make('local', 'file.xlsx'))
            ->toBeInstanceOf(ImportException::class);

        expect(HandlerMissingException::make())
            ->toBeInstanceOf(ImportException::class);

        expect(ImportFileNotFoundException::make('local', 'file.xlsx'))
            ->toBeInstanceOf(ImportException::class);

        expect(EmptySheetException::forFile(1))
            ->toBeInstanceOf(ImportException::class);

        expect(new SheetNotFoundException('msg'))
            ->toBeInstanceOf(ImportException::class);

        expect(MissingHandlerException::make())
            ->toBeInstanceOf(ImportException::class);
    });
});