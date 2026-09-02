<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Repositories;

use Akbarjimi\ExcelImporter\Enums\ExcelFileStatus;
use Akbarjimi\ExcelImporter\Models\ExcelFile;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ExcelFileRepository
{

    public function create(array $data): ExcelFile
    {
        return ExcelFile::create($data);
    }

    /**
     * @param array<int, string> $relations
     */
    public function findFile(int $fileId, array $relations = []): ?ExcelFile
    {
        return ExcelFile::query()->with($relations)->find($fileId);
    }

    /**
     * Generic status update.
     */
    public function markAs(int $fileId, ExcelFileStatus $status, array $extra = []): void
    {
        $data = ['status' => $status->value] + $extra;
        ExcelFile::query()->whereKey($fileId)->update($data);
    }

    public function markAsReading(int $fileId): void
    {
        $this->markAs($fileId, ExcelFileStatus::READING);
    }

    public function markAsRowsExtracted(int $fileId): void
    {
        $this->markAs($fileId, ExcelFileStatus::ROWS_EXTRACTED, ['rows_extracted_at' => now()]);
    }

    public function markAsFailed(int $fileId, ?string $reason = null): void
    {
        $extra = [];
        if ($reason !== null) {
            $extra['error'] = $reason;
        }
        $this->markAs($fileId, ExcelFileStatus::FAILED, $extra);
    }

    public function markAsProcessing(int $fileId): void
    {
        $this->markAs($fileId, ExcelFileStatus::PROCESSING);
    }

    public function markAsCompleted(int $fileId): void
    {
        $this->markAs($fileId, ExcelFileStatus::COMPLETED, ['completed_at' => now()]);
    }

    public function getHandler(int $fileId): ?string
    {
        return ExcelFile::query()->whereKey($fileId)->value('meta->handler');
    }

    public function logBatchFailure(int $fileId, Throwable $exception): void
    {
        activity()
            ->performedOn(ExcelFile::find($fileId))
            ->withProperties(['error' => $exception->getMessage()])
            ->log('import_batch_failed');
    }

    public function canTransition(int $fileId, ExcelFileStatus $newStatus): bool
    {
        $file = ExcelFile::find($fileId);
        if (!$file) {
            return false;
        }
        $allowed = [
            ExcelFileStatus::PENDING->value => [ExcelFileStatus::READING],
            ExcelFileStatus::READING->value => [ExcelFileStatus::ROWS_EXTRACTED, ExcelFileStatus::FAILED],
            ExcelFileStatus::ROWS_EXTRACTED->value => [ExcelFileStatus::PROCESSING, ExcelFileStatus::FAILED],
            ExcelFileStatus::PROCESSING->value => [ExcelFileStatus::COMPLETED, ExcelFileStatus::FAILED],
            ExcelFileStatus::FAILED->value => [], // terminal
            ExcelFileStatus::COMPLETED->value => [], // terminal
        ];
        return in_array($newStatus->value, $allowed[$file->status->value] ?? []);
    }
}