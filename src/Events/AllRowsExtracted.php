<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when every ExtractSheetRowsJob in the extraction batch has completed
 * successfully and all raw rows for the file are persisted.
 *
 * The payload is a scalar identifier rather than an Eloquent model so the
 * event serializes safely onto any queue driver and never carries stale
 * model state.
 */
final class AllRowsExtracted implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $fileId,
    ) {}
}
