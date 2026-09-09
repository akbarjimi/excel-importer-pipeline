<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Repositories;

use Akbarjimi\ExcelImporter\Concerns\HasStatusTransitions;
use Akbarjimi\ExcelImporter\DTOs\ValidatedRow;
use Akbarjimi\ExcelImporter\Enums\ExcelRowStatus;
use Akbarjimi\ExcelImporter\Models\ExcelRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

final class ExcelRowRepository
{
    use HasStatusTransitions;

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

    /**
     * Get rows with validation errors for a file.
     * @return Collection<int, array{row: ExcelRow, errors: Collection<int, ExcelRowError>}>
     */
    public function getRowsWithErrors(int $fileId): Collection
    {
        return ExcelRow::with('errors')
            ->whereHas('excelSheet', fn($q) => $q->where('excel_file_id', $fileId))
            ->where('status', ExcelRowStatus::FAILED_VALIDATION)
            ->get()
            ->map(fn($row) => ['row' => $row, 'errors' => $row->errors]);
    }

    public function chunkRowIdsBySheet(int $sheetId, int $chunkSize, callable $callback): void
    {
        ExcelRow::query()
            ->where('excel_sheet_id', $sheetId)
            ->orderBy('id')
            ->select('id')
            ->chunk($chunkSize, function ($rows) use ($callback) {
                $idChunk = $rows->pluck('id');
                $callback($idChunk);
            });
    }

    public function markAsPending(int $fileId): void
    {
        $this->markAs($fileId, ExcelRow::class, ExcelRowStatus::PENDING);
    }

    public function markAsValidating(int $fileId): void
    {
        $this->markAs($fileId, ExcelRow::class, ExcelRowStatus::VALIDATING);
    }

    public function markAsValidated(int $fileId): void
    {
        $this->markAs($fileId, ExcelRow::class, ExcelRowStatus::VALIDATED);
    }

    public function markAsFailedValidation(int $fileId): void
    {
        $this->markAs($fileId, ExcelRow::class, ExcelRowStatus::FAILED_VALIDATION);
    }

    public function markAsProcessed(int $fileId): void
    {
        $this->markAs($fileId, ExcelRow::class, ExcelRowStatus::PROCESSED);
    }

    public function markAsFailed(int $fileId): void
    {
        $this->markAs($fileId, ExcelRow::class, ExcelRowStatus::FAILED);
    }

}
