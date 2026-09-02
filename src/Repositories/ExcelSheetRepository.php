<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Repositories;

use Akbarjimi\ExcelImporter\DTOs\SheetInfo;
use Akbarjimi\ExcelImporter\Enums\ExcelSheetStatus;
use Akbarjimi\ExcelImporter\Exceptions\EmptySheetException;
use Akbarjimi\ExcelImporter\Models\ExcelSheet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ExcelSheetRepository
{
    /**
     * @param array<int, SheetInfo> $sheets
     * @throws EmptySheetException
     */
    public function bulkCreate(int $fileId, array $sheets): void
    {
        if (empty($sheets)) {
            throw EmptySheetException::forFile($fileId);
        }

        $now = now();

        $rows = array_map(static fn(SheetInfo $sheet): array => [
            'excel_file_id' => $fileId,
            'name' => $sheet->name,
            'sheet_index' => $sheet->index,
            'total_rows' => $sheet->totalRows,
            'status' => ExcelSheetStatus::PENDING->value,
            'meta' => json_encode($sheet->raw, JSON_THROW_ON_ERROR),
            'created_at' => $now,
            'updated_at' => $now,
        ], $sheets);

        ExcelSheet::query()->upsert(
            $rows,
            uniqueBy: ['excel_file_id', 'sheet_index'],
            update: ['name', 'total_rows', 'meta', 'updated_at'],
        );
    }

    public function existsForFile(int $fileId): bool
    {
        return ExcelSheet::query()->where('excel_file_id', $fileId)->exists();
    }

    /**
     * @return Collection<int, ExcelSheet>
     */
    public function getByFileId(int $fileId): Collection
    {
        return ExcelSheet::query()
            ->where('excel_file_id', $fileId)
            ->orderBy('sheet_index')
            ->get();
    }

    public function getById(int $sheetId): ?ExcelSheet
    {
        return ExcelSheet::find($sheetId);
    }

    public function incrementProcessedChunks(int $sheetId): int
    {
        return ExcelSheet::query()
            ->where('id', $sheetId)
            ->whereColumn('processed_chunks', '<', 'chunk_count')
            ->increment('processed_chunks');
    }

    public function setChunkCount(int $sheetId, int $count): void
    {
        ExcelSheet::query()->where('id', $sheetId)->update(['chunk_count' => $count]);
    }

    public function markAsCompleted(int $sheetId): void
    {
        ExcelSheet::query()->where('id', $sheetId)->update(['status' => ExcelSheetStatus::COMPLETED->value]);
    }

    public function markAsFailed(int $sheetId, string $reason): void
    {
        ExcelSheet::query()->where('id', $sheetId)->update([
            'status' => ExcelSheetStatus::FAILED->value,
            'meta' => DB::raw("JSON_SET(meta, '$.error', '{$reason}')"),
        ]);
    }
}