<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Repositories;

use Akbarjimi\ExcelImporter\Enums\ExcelFileStatus;
use Akbarjimi\ExcelImporter\Models\ExcelFile;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ExcelFileRepository
{
    public function findFile(int $fileId, array $relations = []): ?ExcelFile
    {
        return ExcelFile::query()->with($relations)->find($fileId);
    }

    public function create(array $data): ExcelFile
    {
        return ExcelFile::create($data);
    }

    public function transitionTo(int $fileId, ExcelFileStatus $newStatus, array $extra = []): void
    {
        $file = ExcelFile::findOrFail($fileId);
        if (!$file->status->canTransitionTo($newStatus)) {
            throw new \RuntimeException(
                "Invalid status transition from {$file->status->value} to {$newStatus->value}"
            );
        }

        $data = ['status' => $newStatus->value] + $extra;
        $file->update($data);
    }

    public function markAsReading(int $fileId): void
    {
        $this->transitionTo($fileId, ExcelFileStatus::READING);
    }

    public function markAsRowsExtracted(int $fileId): void
    {
        $this->transitionTo($fileId, ExcelFileStatus::ROWS_EXTRACTED, [
            'rows_extracted_at' => now(),
        ]);
    }

    public function markAsFailed(int $fileId, ?string $reason = null): void
    {
        $extra = [];
        if ($reason !== null) {
            $extra['error'] = $reason;
        }
        $this->transitionTo($fileId, ExcelFileStatus::FAILED, $extra);
    }

    public function markAsProcessing(int $fileId): void
    {
        $this->transitionTo($fileId, ExcelFileStatus::PROCESSING);
    }

    public function markAsCompleted(int $fileId): void
    {
        $this->transitionTo($fileId, ExcelFileStatus::COMPLETED, ['completed_at' => now()]);
    }

    public function getHandler(int $fileId): ?string
    {
        return ExcelFile::whereKey($fileId)->value('meta->handler');
    }

    public function recordBatchId(int $fileId, string $batchId): void
    {
        ExcelFile::whereKey($fileId)->update(['batch_id' => $batchId]);
    }

    public function logBatchFailure(int $fileId, Throwable $exception): void
    {
        activity()
            ->performedOn(ExcelFile::find($fileId))
            ->withProperties(['error' => $exception->getMessage()])
            ->log('import_batch_failed');
    }
}