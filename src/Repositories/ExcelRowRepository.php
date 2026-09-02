<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Repositories;

use Akbarjimi\ExcelImporter\DTOs\ValidatedRow;
use Akbarjimi\ExcelImporter\Enums\ExcelRowStatus;
use Akbarjimi\ExcelImporter\Models\ExcelRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

final class ExcelRowRepository
{
    public function bulkInsert(array $rows): void
    {
        if (empty($rows)) {
            return;
        }
        DB::table('excel_rows')->insert($rows);
    }

    public function bulkUpsert(array $rows, int $chunkSize = 500): void
    {
        collect($rows)
            ->chunk($chunkSize)
            ->each(function ($chunk) {
                $sanitized = $chunk->map(fn($row) => array_diff_key($row, ['id' => null]))->all();
                DB::table('excel_rows')->upsert(
                    $sanitized,
                    ['excel_sheet_id', 'content_hash', 'hash_algo'],
                    ['content', 'status', 'row_index', 'updated_at']
                );
            });
    }

    public function getValidatedRowsForFile(int $fileId): LazyCollection
    {
        return ExcelRow::query()
            ->whereHas('excelSheet', fn($q) => $q->where('excel_file_id', $fileId))
            ->where('status', ExcelRowStatus::VALIDATED->value)
            ->orderBy('id')
            ->lazy()
            ->map(fn(ExcelRow $row) => new ValidatedRow(
                rowIndex: $row->row_index,
                data: $row->content,
            ));
    }

    public function transitionTo(int $rowId, ExcelRowStatus $newStatus): void
    {
        $row = ExcelRow::findOrFail($rowId);
        if (!$row->status->canTransitionTo($newStatus)) {
            throw new \RuntimeException(
                "Invalid transition from {$row->status->value} to {$newStatus->value}"
            );
        }
        $row->update(['status' => $newStatus->value]);
    }
}