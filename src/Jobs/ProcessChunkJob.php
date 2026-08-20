<?php

namespace Akbarjimi\ExcelImporter\Jobs;

use Akbarjimi\ExcelImporter\Enums\ExcelRowStatus;
use Akbarjimi\ExcelImporter\Events\SheetProcessingCompleted;
use Akbarjimi\ExcelImporter\Models\ExcelRow;
use Akbarjimi\ExcelImporter\Models\ExcelRowChunk;
use Akbarjimi\ExcelImporter\Models\ExcelRowError;
use Akbarjimi\ExcelImporter\Models\ExcelSheet;
use Akbarjimi\ExcelImporter\Repositories\ExcelRowRepository;
use Akbarjimi\ExcelImporter\Services\TransformService;
use Akbarjimi\ExcelImporter\Services\ValidateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ProcessChunkJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable;


    public function __construct(public readonly int $chunkId)
    {
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping("chunk:{$this->chunkId}"))->dontRelease()];
    }

    public function handle(
        TransformService   $transform,
        ValidateService    $validate,
        ExcelRowRepository $repo
    ): void
    {
        /** @var ExcelRowChunk $chunk */
        $chunk = ExcelRowChunk::findOrFail($this->chunkId);
        $sheet = ExcelSheet::findOrFail($chunk->excel_sheet_id);

        if ($chunk->status === 'completed') {
            return;
        }

        $chunk->update(['status' => 'processing', 'attempts' => $chunk->attempts + 1]);

        $rowsCursor = ExcelRow::query()
            ->where('excel_sheet_id', $chunk->excel_sheet_id)
            ->whereBetween('id', [$chunk->from_row_id, $chunk->to_row_id])
            ->orderBy('id')
            ->cursor();

        $buffer = [];
        $batchSize = config('excel-importer.insert_batch_size', 100);
        $processed = 0;

        DB::beginTransaction();
        try {
            foreach ($rowsCursor as $row) {
                try {
                    $payload = $transform->apply($row->content ?? $row->toArray(), $sheet);

                    if (!empty($errors = $validate->apply($payload))) {
                        throw new \Exception($errors);
                    }

                    $buffer[] = [
                        'id' => $row->id,
                        'excel_sheet_id' => $row->excel_sheet_id,
                        'row_index' => $row->row_index,
                        'content' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                        'updated_at' => now(),
                    ];
                } catch (Throwable $e) {
                    ExcelRowError::create([
                        'excel_row_id' => $row->id,
                        'field' => null,
                        'error_type' => 'validation',
                        'error_code' => null,
                        'message' => $e->getMessage(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $row->update(['status' => ExcelRowStatus::FAILED_VALIDATION->value]);
                }

                if (count($buffer) >= $batchSize) {
                    $repo->bulkUpsert($buffer);
                    $processed += count($buffer);
                    $buffer = [];
                }
            }

            if (!empty($buffer)) {
                $repo->bulkUpsert($buffer);
                $processed += count($buffer);
                $buffer = [];
            }

            $chunk->update(['status' => 'completed', 'processed_at' => now(), 'error' => null]);

            DB::commit();

            $sheetUpdated = DB::table('excel_sheets')
                ->where('id', $sheet->id)
                ->whereColumn('processed_chunks', '<', 'chunk_count')
                ->increment('processed_chunks');

            $sheetFresh = ExcelSheet::find($sheet->id);
            if ($sheetUpdated > 0) {
                if ($sheetFresh->processed_chunks >= $sheetFresh->chunk_count && $sheetFresh->chunk_count > 0) {
                    event(new SheetProcessingCompleted($sheetFresh->id));
                    Log::info('AllChunksCompleted fired (via increment)', ['sheet_id' => $sheetFresh->id]);
                }
            } else {
                if ($sheetFresh->processed_chunks >= $sheetFresh->chunk_count && $sheetFresh->chunk_count > 0) {
                    event(new SheetProcessingCompleted($sheetFresh->id));
                    Log::info('AllChunksCompleted fired (fallback)', ['sheet_id' => $sheetFresh->id]);
                }
            }

            Log::info('Chunk processed', ['chunk_id' => $chunk->id, 'processed' => $processed]);
        } catch (Throwable $e) {
            DB::rollBack();

            $chunk->update([
                'status' => 'failed',
                'error' => mb_substr($e->getMessage(), 0, 2000),
            ]);

            Log::error('Chunk processing failed', [
                'chunk_id' => $chunk->getKey(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}