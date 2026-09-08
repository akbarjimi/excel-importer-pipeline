<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Repositories;

use Akbarjimi\ExcelImporter\Models\ExcelRowChunk;
use Illuminate\Support\Collection;

final class ExcelRowChunkRepository
{
    /**
     * Insert many chunks and return the inserted models (with IDs).
     */
    public function insertMany(array $data): Collection
    {
        if (empty($data)) {
            return collect();
        }

        ExcelRowChunk::insert($data);

        $fromRowIds = array_column($data, 'from_row_id');
        $toRowIds = array_column($data, 'to_row_id');
        $minFrom = min($fromRowIds);
        $maxTo = max($toRowIds);

        $sheetId = $data[0]['excel_sheet_id'] ?? null;
        if (! $sheetId) {
            return collect($data); // fallback, but IDs will be missing
        }

        return ExcelRowChunk::where('excel_sheet_id', $sheetId)
            ->whereBetween('from_row_id', [$minFrom, $maxTo])
            ->whereBetween('to_row_id', [$minFrom, $maxTo])
            ->get();
    }
}
