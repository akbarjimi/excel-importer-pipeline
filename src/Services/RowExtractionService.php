<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Services;

use Akbarjimi\ExcelImporter\Concerns\LogsImportActivity;
use Akbarjimi\ExcelImporter\Contracts\ExcelReaderDriver;
use Akbarjimi\ExcelImporter\Enums\ExcelFileStatus;
use Akbarjimi\ExcelImporter\Enums\LogLevel;
use Akbarjimi\ExcelImporter\Models\ExcelSheet;
use Akbarjimi\ExcelImporter\Repositories\ExcelFileRepository;
use Akbarjimi\ExcelImporter\Repositories\ExcelRowRepository;
use Illuminate\Support\Facades\DB;
use Throwable;

final class RowExtractionService
{
    use LogsImportActivity;

    private array $buffer = [];
    private int $inserted = 0;
    private int $batchSize;
    private string $hashAlgo;

    public function __construct(
        private readonly ExcelReaderDriver $driver,
        private readonly ExcelFileRepository $fileRepo,
        private readonly ExcelRowRepository $rowRepo,
    ) {
        $this->batchSize = (int) config('excel-importer.insert_batch_size', 100);
        $this->hashAlgo = config('excel-importer.hash_algo', 'sha256');
    }

    public function extract(ExcelSheet $sheet): int
    {
        $this->inserted = 0;
        $this->buffer = [];

        $this->fileRepo->markAs($sheet->excel_file_id, ExcelFileStatus::READING);

        try {
            $this->driver->readRows(
                $sheet->excelFile->path,
                $sheet->index,
                fn(array $row) => $this->bufferRow($row, $sheet)
            );

            $this->flushBuffer($sheet);

            $sheet->update(['rows_extracted_at' => now()]);
            $this->fileRepo->markAs($sheet->excel_file_id, ExcelFileStatus::ROWS_EXTRACTED);

            $this->importLog(LogLevel::INFO, 'excel-importer::extraction_success', [
                'sheet_id' => $sheet->id,
                'rows'     => $this->inserted,
            ]);

            return $this->inserted;
        } catch (Throwable $e) {
            $this->fileRepo->markAs($sheet->excel_file_id, ExcelFileStatus::FAILED, ['error' => $e->getMessage()]);
            $this->importLog(LogLevel::CRITICAL, 'excel-importer::extraction_failed', [
                'sheet_id' => $sheet->id,
                'error'    => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function bufferRow(array $row, ExcelSheet $sheet): void
    {
        $encoded = json_encode($row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $this->buffer[] = [
            'excel_sheet_id' => $sheet->id,
            'content'        => $encoded,
            'hash_algo'      => $this->hashAlgo,
            'content_hash'   => hash($this->hashAlgo, $encoded),
            'created_at'     => now(),
            'updated_at'     => now(),
        ];

        if (count($this->buffer) >= $this->batchSize) {
            $this->flushBuffer($sheet);
        }
    }

    private function flushBuffer(ExcelSheet $sheet): void
    {
        if (empty($this->buffer)) {
            return;
        }

        $this->rowRepo->bulkUpsert($this->buffer);
        $this->inserted += count($this->buffer);
        $this->buffer = [];
    }
}