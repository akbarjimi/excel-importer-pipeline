<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Repositories;

use Akbarjimi\ExcelImporter\Enums\ExcelFileStatus;
use Akbarjimi\ExcelImporter\Models\ExcelFile;
use Throwable;

class ExcelFileRepository
{
    /**
     * @param array<int, string> $relations
     */
    public function findFile(int $fileId, array $relations = []): ?ExcelFile
    {
        return ExcelFile::query()->with($relations)->find($fileId);
    }

    public function markAsReading(int $fileId): void
    {
        ExcelFile::query()->whereKey($fileId)->update([
            'status' => ExcelFileStatus::READING->value,
        ]);
    }

    public function markAsRowsExtracted(int $fileId): void
    {
        ExcelFile::query()->whereKey($fileId)->update([
            'status' => ExcelFileStatus::ROWS_EXTRACTED->value,
            'rows_extracted_at' => now(),
        ]);
    }

    public function markAsFailed(int $fileId): void
    {
        ExcelFile::query()->whereKey($fileId)->update([
            'status' => ExcelFileStatus::FAILED->value,
        ]);
    }

    public function markAsProcessing(int $fileId): void
    {
        ExcelFile::query()->whereKey($fileId)->update([
            'status' => ExcelFileStatus::PROCESSING->value,
        ]);
    }

    public function markAsCompleted(int $fileId): void
    {
        ExcelFile::query()->whereKey($fileId)->update([
            'status' => ExcelFileStatus::COMPLETED->value,
        ]);
    }

    /**
     * Log a failure that occurred at the batch level (e.g. queue timeout or
     * unhandled exception in the batch logic) rather than a specific row error.
     */
    public function logBatchFailure(int $fileId, Throwable $exception): void
    {
        // This ensures that even if individual rows don't fail,
        // the developer knows why the entire file import stopped.
        activity()
            ->performedOn(ExcelFile::find($fileId))
            ->withProperties(['error' => $exception->getMessage()])
            ->log('import_batch_failed');
    }

    public function markAsCompletedById(int $fileId): void
    {
        ExcelFile::whereKey($fileId)->update([
            'status'       => ExcelFileStatus::COMPLETED->value,
            'completed_at' => now(),
        ]);
    }

    public function markAsFailedById(int $fileId, string $reason): void
    {
        ExcelFile::whereKey($fileId)->update([
            'status' => ExcelFileStatus::FAILED->value,
            'error'  => $reason,
        ]);
    }

    public function recordBatchId(int $fileId, string $batchId): void
    {
        ExcelFile::whereKey($fileId)->update(['batch_id' => $batchId]);
    }

    public function setHandler(int $fileId, string $handler): void
    {
        ExcelFile::whereKey($fileId)->update(['meta->handler' => $handler]);
    }

    public function getHandler(int $fileId): ?string
    {
        return ExcelFile::whereKey($fileId)->value('meta->handler');
    }

}
