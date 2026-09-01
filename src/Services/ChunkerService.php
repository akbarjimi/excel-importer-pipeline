<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Services;

use Akbarjimi\ExcelImporter\Concerns\LogsImportActivity;
use Akbarjimi\ExcelImporter\Enums\ExcelChunkStatus;
use Akbarjimi\ExcelImporter\Enums\LogLevel;
use Akbarjimi\ExcelImporter\Models\ExcelFile;
use Akbarjimi\ExcelImporter\Models\ExcelRow;
use Akbarjimi\ExcelImporter\Models\ExcelRowChunk;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ChunkerService
{
    use LogsImportActivity;

    public function __construct(private readonly int $chunkSize = 1000) {}

    /**
     * Atomically create chunks for all sheets of a file.
     *
     * @return Collection<int, ExcelRowChunk>
     */
    public function createChunksForFile(ExcelFile $file): Collection
    {
        return DB::transaction(function () use ($file) {
            $allChunks = collect();

            foreach ($file->excelSheets as $sheet) {
                $rowIds = ExcelRow::query()
                    ->where('excel_sheet_id', $sheet->getKey())
                    ->orderBy('id')
                    ->pluck('id');

                if ($rowIds->isEmpty()) {
                    continue;
                }

                $chunks = $rowIds->chunk($this->chunkSize)->map(function ($idChunk) use ($sheet) {
                    return ExcelRowChunk::create([
                        'excel_sheet_id' => $sheet->getKey(),
                        'from_row_id'    => $idChunk->first(),
                        'to_row_id'      => $idChunk->last(),
                        'size'           => $idChunk->count(),
                        'status'         => ExcelChunkStatus::PENDING,
                    ]);
                });

                $allChunks = $allChunks->merge($chunks);
            }

            $this->importLog(LogLevel::INFO, 'excel-importer::chunks_created', [
                'file_id'    => $file->getKey(),
                'chunk_count'  => $allChunks->count(),
                'chunk_size'   => $this->chunkSize,
            ]);

            return $allChunks;
        }, 3);
    }

    public function allChunksProcessed(ExcelFile $file): bool
    {
        $sheets = $file->relationLoaded('excelSheets')
            ? $file->excelSheets
            : $file->excelSheets()->get();

        foreach ($sheets as $sheet) {
            if ((int) $sheet->chunk_count === 0) {
                return false;
            }

            if ((int) $sheet->processed_chunks < (int) $sheet->chunk_count) {
                return false;
            }
        }

        return true;
    }
}