<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Repositories;

use Akbarjimi\ExcelImporter\Enums\ExcelFileStatus;
use Akbarjimi\ExcelImporter\Models\ExcelFile;

class ExcelFileRepository
{
    /**
     * @param array<int, string> $relations
     */
    public function findFile(int $fileId, array $relations = []): ?ExcelFile
    {
        return ExcelFile::query()->with($relations)->find($fileId);
    }

    public function markReading(int $fileId): void
    {
        ExcelFile::query()->whereKey($fileId)->update([
            'status' => ExcelFileStatus::READING->value,
        ]);
    }

    public function markRowsExtracted(int $fileId): void
    {
        ExcelFile::query()->whereKey($fileId)->update([
            'status' => ExcelFileStatus::ROWS_EXTRACTED->value,
            'rows_extracted_at' => now(),
        ]);
    }

    public function markFailed(int $fileId): void
    {
        ExcelFile::query()->whereKey($fileId)->update([
            'status' => ExcelFileStatus::FAILED->value,
        ]);
    }
}
