<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Jobs;

use Akbarjimi\ExcelImporter\Concerns\LogsImportActivity;
use Akbarjimi\ExcelImporter\Enums\ExcelChunkStatus;
use Akbarjimi\ExcelImporter\Enums\ExcelRowStatus;
use Akbarjimi\ExcelImporter\Enums\LogLevel;
use Akbarjimi\ExcelImporter\Events\SheetProcessingCompleted;
use Akbarjimi\ExcelImporter\Models\ExcelRow;
use Akbarjimi\ExcelImporter\Models\ExcelRowChunk;
use Akbarjimi\ExcelImporter\Models\ExcelRowError;
use Akbarjimi\ExcelImporter\Models\ExcelSheet;
use Akbarjimi\ExcelImporter\Repositories\ExcelRowRepository;
use Akbarjimi\ExcelImporter\Services\TransformService;
use Akbarjimi\ExcelImporter\Services\ValidateService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ProcessChunkJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(public readonly int $chunkId)
    {
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping("chunk:{$this->chunkId}"))->dontRelease()];
    }

    public function tags(): array
    {
        return ['excel-process', "chunk:{$this->chunkId}"];
    }

    public function handle(
        TransformService   $transform,
        ValidateService    $validate,
        ExcelRowRepository $rowRepo,
    ): void
    {
        /** @var ExcelRowChunk $chunk */
        $chunk = ExcelRowChunk::findOrFail($this->chunkId);
        $sheet = ExcelSheet::findOrFail($chunk->excel_sheet_id);

        if ($chunk->status === ExcelChunkStatus::COMPLETED) {
            return;
        }

        $chunk->update([
            'status' => ExcelChunkStatus::PROCESSING,
            'attempts' => $chunk->attempts + 1,
        ]);

        $rowsCursor = ExcelRow::query()
            ->where('excel_sheet_id', $chunk->excel_sheet_id)
            ->whereBetween('id', [$chunk->from_row_id, $chunk->to_row_id])
            ->orderBy('id')
            ->cursor();

        $buffer = [];
        $batchSize = (int)config('excel-importer.insert_batch_size', 100);
        $processed = 0;

        DB::beginTransaction();

        try {
            foreach ($rowsCursor as $row) {
                try {
                    $payload = $transform->apply($row->content ?? $row->toArray(), $sheet);
                    $errors = $validate->apply($payload);

                    if (!empty($errors)) {
                        $this->recordRowErrors($row, $errors);
                        continue;
                    }

                    $buffer[] = [
                        'id' => $row->id,
                        'excel_sheet_id' => $row->excel_sheet_id,
                        'row_index' => $row->row_index,
                        'content' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                        'status' => ExcelRowStatus::VALIDATED,
                        'updated_at' => now(),
                    ];
                } catch (Throwable $e) {
                    $this->recordRowErrors($row, [$e->getMessage()]);
                }

                if (count($buffer) >= $batchSize) {
                    $rowRepo->bulkUpsert($buffer);
                    $processed += count($buffer);
                    $buffer = [];
                }
            }

            if (!empty($buffer)) {
                $rowRepo->bulkUpsert($buffer);
                $processed += count($buffer);
            }

            $chunk->update([
                'status' => ExcelChunkStatus::COMPLETED,
                'processed_at' => now(),
                'error' => null,
            ]);

            DB::commit();

            $this->incrementProcessedChunksAndFireEvent($sheet);

            $this->importLog(LogLevel::INFO, 'excel-importer::chunk_processed', [
                'chunk_id' => $chunk->id,
                'rows' => $processed,
            ]);
        } catch (Throwable $e) {
            DB::rollBack();

            $chunk->update([
                'status' => ExcelChunkStatus::FAILED,
                'error' => mb_substr($e->getMessage(), 0, 2000),
            ]);

            $this->importLog(LogLevel::ERROR, 'excel-importer::chunk_processing_failed', [
                'chunk_id' => $chunk->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function recordRowErrors(ExcelRow $row, array $errors): void
    {
        $now = now();

        $errorRecords = array_map(function ($error) use ($row, $now) {
            return [
                'excel_row_id' => $row->id,
                'field' => is_string($error) ? null : ($error['field'] ?? null),
                'error_type' => 'validation',
                'error_code' => is_string($error) ? null : ($error['code'] ?? null),
                'message' => is_string($error) ? $error : ($error['message'] ?? json_encode($error)),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $errors);

        ExcelRowError::insert($errorRecords);
        $row->update(['status' => ExcelRowStatus::FAILED_VALIDATION]);
    }

    private function incrementProcessedChunksAndFireEvent(ExcelSheet $sheet): void
    {
        $updated = DB::table('excel_sheets')
            ->where('id', $sheet->id)
            ->whereColumn('processed_chunks', '<', 'chunk_count')
            ->increment('processed_chunks');

        if ($updated > 0) {
            $freshSheet = ExcelSheet::find($sheet->id);
            if ($freshSheet->processed_chunks >= $freshSheet->chunk_count && $freshSheet->chunk_count > 0) {
                event(new SheetProcessingCompleted($sheet->id));
                $this->importLog(LogLevel::INFO, 'excel-importer::sheet_processing_completed', ['sheet_id' => $sheet->id]);
            }
        } else {
            $freshSheet = ExcelSheet::find($sheet->id);
            if ($freshSheet->processed_chunks >= $freshSheet->chunk_count && $freshSheet->chunk_count > 0) {
                event(new SheetProcessingCompleted($sheet->id));
                $this->importLog(LogLevel::INFO, 'excel-importer::sheet_processing_completed_fallback', ['sheet_id' => $sheet->id]);
            }
        }
    }
}