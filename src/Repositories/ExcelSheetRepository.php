<?php

namespace Akbarjimi\ExcelImporter\Repositories;

use Akbarjimi\ExcelImporter\DTOs\SheetInfo;
use Akbarjimi\ExcelImporter\Enums\ExcelSheetStatus;
use Akbarjimi\ExcelImporter\Models\ExcelSheet;
use Illuminate\Support\Collection;


final readonly class ExcelSheetRepository
{
    public function bulkCreate(int $fileId, array $sheets): void
    {
        $now = now();

        $rows = array_map(static fn(SheetInfo $sheet): array => [
            'excel_file_id' => $fileId,
            'name' => $sheet->name,
            'index' => $sheet->index,
            'total_rows' => $sheet->totalRows,
            'status' => ExcelSheetStatus::PENDING->value,
            'meta' => json_encode($sheet->raw),
            'created_at' => $now,
            'updated_at' => $now,
        ], $sheets);

        ExcelSheet::query()->insert($rows);
    }

    /**
     * @return Collection<int, ExcelSheet>
     */
    public function getByFileId(int $fileId): Collection
    {
        return ExcelSheet::query()
            ->where('excel_file_id', $fileId)
            ->orderBy('index')
            ->get();
    }
}
