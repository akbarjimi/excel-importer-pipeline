<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Tests\Unit\Enums;

use Akbarjimi\ExcelImporter\Enums\ExcelRowStatus;

/**
 * Test the row status state machine.
 *
 * Ensures rows follow the correct lifecycle during validation
 * and processing.
 *
 * @group enums
 * @group row-status
 */
describe('ExcelRowStatus', function () {
    it('allows valid transitions', function (
        ExcelRowStatus $from,
        ExcelRowStatus $to,
        bool $expected
    ) {
        expect($from->canTransitionTo($to))->toBe($expected);
    })->with([
        'PENDING → VALIDATING' => [ExcelRowStatus::PENDING, ExcelRowStatus::VALIDATING, true],
        'PENDING → FAILED' => [ExcelRowStatus::PENDING, ExcelRowStatus::FAILED, true],
        'PENDING → VALIDATED' => [ExcelRowStatus::PENDING, ExcelRowStatus::VALIDATED, false],
        'PENDING → PROCESSED' => [ExcelRowStatus::PENDING, ExcelRowStatus::PROCESSED, false],
        'PENDING → FAILED_VALIDATION' => [ExcelRowStatus::PENDING, ExcelRowStatus::FAILED_VALIDATION, false],
        'VALIDATING → VALIDATED' => [ExcelRowStatus::VALIDATING, ExcelRowStatus::VALIDATED, true],
        'VALIDATING → FAILED_VALIDATION' => [ExcelRowStatus::VALIDATING, ExcelRowStatus::FAILED_VALIDATION, true],
        'VALIDATING → FAILED' => [ExcelRowStatus::VALIDATING, ExcelRowStatus::FAILED, true],
        'VALIDATING → PROCESSED' => [ExcelRowStatus::VALIDATING, ExcelRowStatus::PROCESSED, false],
        'VALIDATING → PENDING' => [ExcelRowStatus::VALIDATING, ExcelRowStatus::PENDING, false],
        'VALIDATED → PROCESSED' => [ExcelRowStatus::VALIDATED, ExcelRowStatus::PROCESSED, true],
        'VALIDATED → FAILED' => [ExcelRowStatus::VALIDATED, ExcelRowStatus::FAILED, true],
        'VALIDATED → FAILED_VALIDATION' => [ExcelRowStatus::VALIDATED, ExcelRowStatus::FAILED_VALIDATION, false],
        'VALIDATED → VALIDATING' => [ExcelRowStatus::VALIDATED, ExcelRowStatus::VALIDATING, false],
        'FAILED_VALIDATION → PROCESSED' => [ExcelRowStatus::FAILED_VALIDATION, ExcelRowStatus::PROCESSED, false],
        'FAILED_VALIDATION → VALIDATED' => [ExcelRowStatus::FAILED_VALIDATION, ExcelRowStatus::VALIDATED, false],
        'FAILED_VALIDATION → FAILED' => [ExcelRowStatus::FAILED_VALIDATION, ExcelRowStatus::FAILED, false],
        'PROCESSED → FAILED' => [ExcelRowStatus::PROCESSED, ExcelRowStatus::FAILED, false],
        'PROCESSED → VALIDATED' => [ExcelRowStatus::PROCESSED, ExcelRowStatus::VALIDATED, false],
        'FAILED → PROCESSED' => [ExcelRowStatus::FAILED, ExcelRowStatus::PROCESSED, false],
        'FAILED → VALIDATED' => [ExcelRowStatus::FAILED, ExcelRowStatus::VALIDATED, false],
    ]);

    it('has all expected status values', function () {
        $statuses = array_column(ExcelRowStatus::cases(), 'value');
        expect($statuses)->toContain('pending', 'validating', 'validated', 'failed_validation', 'processed', 'failed');
    });
});