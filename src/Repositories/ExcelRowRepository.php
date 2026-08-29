<?php

namespace Akbarjimi\ExcelImporter\Repositories;

use Akbarjimi\ExcelImporter\DTOs\ValidatedRow;
use Akbarjimi\ExcelImporter\Enums\ExcelRowStatus;
use Akbarjimi\ExcelImporter\Models\ExcelRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

final class ExcelRowRepository
{
    public function bulkUpsert(array $rows, int $chunkSize = 500): void
    {
        collect($rows)
            ->chunk($chunkSize)
            ->each(function ($chunk) {
                $sanitized = collect($chunk)->map(function ($row) {
                    // If id were included, the DB would try to match or insert the
                    // id column explicitly, potentially causing conflicts with
                    // auto-increment behaviour. Removing it lets the DB
                    // assign IDs on insert and match on the composite
                    // unique key on update
                    unset($row['id']);
                    return $row;
                })->all();

                DB::table('excel_rows')->upsert(
                    $sanitized,
                    ['excel_sheet_id', 'content_hash', 'hash_algo'],
                    ['content', 'status', 'chunk_index', 'row_index', 'updated_at']
                );
            });
    }

    public function getValidatedRowsForFile(int $fileId): LazyCollection
    {
        return ExcelRow::query()
            ->whereHas('excelSheet', fn($q) => $q->where('excel_file_id', $fileId))
            ->where('status', ExcelRowStatus::VALIDATED)
            ->orderBy('id')
            ->lazy()
            ->map(fn(ExcelRow $row) => new ValidatedRow(
                rowIndex: $row->row_index,
                data: $row->content,
            ));
    }
}
