<?php

namespace Akbarjimi\ExcelImporter\Services;

use Akbarjimi\ExcelImporter\Concerns\LogsImportActivity;
use Akbarjimi\ExcelImporter\Contracts\ExcelReaderDriver;
use Akbarjimi\ExcelImporter\Enums\ExcelFileStatus;
use Akbarjimi\ExcelImporter\Models\ExcelSheet;
use Illuminate\Support\Facades\DB;
use Throwable;

class RowExtractionService
{
    use LogsImportActivity;

    protected array $buffer = [];
    protected int $inserted = 0;
    protected int $batchSize;

    public function __construct(private readonly ExcelReaderDriver $driver)
    {
        $this->batchSize = config('excel-importer.insert_batch_size', 100);
    }

    public function extract(ExcelSheet $sheet): int
    {
        $this->inserted = 0;
        $this->setFileStatus($sheet, ExcelFileStatus::READING);

        try {
            $this->driver->readRows(
                $sheet->excelFile->path,
                $sheet->index,
                fn(array $row) => $this->bufferRow($row, $sheet)
            );

            $this->flushBuffer($sheet);

            $sheet->update(['rows_extracted_at' => now()]);
            $this->setFileStatus($sheet, ExcelFileStatus::ROWS_EXTRACTED);

            return $this->inserted;
        } catch (Throwable $e) {
            $this->importLog('critical', 'Extraction failed', ['sheet_id' => $sheet->id, 'error' => $e->getMessage()]);
            $this->setFileStatus($sheet, ExcelFileStatus::FAILED);
            throw $e;
        }
    }

    protected function bufferRow(array $row, ExcelSheet $sheet): void
    {
        $encoded = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $hashAlgo = config('excel-importer.hash_algo', 'sha256');

        $this->buffer[] = [
            'excel_sheet_id' => $sheet->id,
            'content' => $encoded,
            'hash_algo' => $hashAlgo,
            'content_hash' => hash($hashAlgo, $encoded),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (count($this->buffer) >= $this->batchSize) {
            $this->flushBuffer($sheet);
        }
    }

    protected function flushBuffer(ExcelSheet $sheet): void
    {
        if (empty($this->buffer)) return;

        DB::table('excel_rows')->upsert(
            $this->buffer,
            ['excel_sheet_id', 'content_hash', 'hash_algo'],
            ['updated_at']
        );

        $this->inserted += count($this->buffer);
        $this->buffer = [];
    }

    protected function setFileStatus(ExcelSheet $sheet, ExcelFileStatus $status): void
    {
        $sheet->excelFile->update(['status' => $status->value]);
    }
}
