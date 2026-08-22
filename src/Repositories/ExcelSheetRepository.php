<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Repositories;

use Akbarjimi\ExcelImporter\DTOs\SheetInfo;
use Akbarjimi\ExcelImporter\Enums\ExcelSheetStatus;
use Akbarjimi\ExcelImporter\Models\ExcelSheet;
use Illuminate\Support\Collection;

final readonly class ExcelSheetRepository
{
    /**
     * @param array<int, SheetInfo> $sheets
     */
    public function bulkCreate(int $fileId, array $sheets): void
    {
        if ($sheets === []) {
            return;
        }

        $now = now();

        $rows = array_map(static fn(SheetInfo $sheet): array => [
            'excel_file_id' => $fileId,
            'name' => $sheet->name,
            'sheet_index' => $sheet->index,
            'total_rows' => $sheet->totalRows,
            'status' => ExcelSheetStatus::PENDING,
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
}
