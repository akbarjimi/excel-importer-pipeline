<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Repositories;

use Akbarjimi\ExcelImporter\Concerns\HasStatusTransitions;
use Akbarjimi\ExcelImporter\Enums\ExcelChunkStatus;
use Akbarjimi\ExcelImporter\Models\ExcelRowChunk;
use Akbarjimi\ExcelImporter\Repositories\Contracts\ExcelRowChunkRepositoryInterface;
use Illuminate\Support\Collection;

final class ExcelRowChunkRepository
{
    use HasStatusTransitions;

    public function findOrFail(int $chunkId): ExcelRowChunk
    {
        return ExcelRowChunk::findOrFail($chunkId);
    }

    public function insertMany(array $data): Collection
    {
        if (empty($data)) {
            return collect();
        }

        ExcelRowChunk::insert($data);

        $sheetId = $data[0]['excel_sheet_id'];
        $fromIds = array_column($data, 'from_row_id');
        $toIds = array_column($data, 'to_row_id');

        return ExcelRowChunk::query()
            ->where('excel_sheet_id', $sheetId)
            ->whereIn('from_row_id', $fromIds)
            ->whereIn('to_row_id', $toIds)
            ->get();
    }

    public function markAsPending(int $chunkId): void
    {
        $this->markAs($chunkId, ExcelRowChunk::class, ExcelChunkStatus::PENDING);
    }

    public function markAsProcessing(int $chunkId): void
    {
        $this->markAs($chunkId, ExcelRowChunk::class, ExcelChunkStatus::PROCESSING);
    }

    public function markAsCompleted(int $chunkId): void
    {
        $this->markAs($chunkId, ExcelRowChunk::class, ExcelChunkStatus::COMPLETED, [
            'processed_at' => now(),
        ]);
    }

    public function markAsFailed(int $chunkId, ?string $reason = null): void
    {
        $extra = $reason !== null ? ['error' => $reason] : [];
        $this->markAs($chunkId, ExcelRowChunk::class, ExcelChunkStatus::FAILED, $extra);
    }
}