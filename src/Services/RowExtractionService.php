<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Services;

use Akbarjimi\ExcelImporter\Concerns\LogsImportActivity;
use Akbarjimi\ExcelImporter\Contracts\ExcelReaderDriver;
use Akbarjimi\ExcelImporter\Contracts\RowExtractorInterface;
use Akbarjimi\ExcelImporter\DTOs\StagedRow;
use Akbarjimi\ExcelImporter\Enums\LogLevel;
use Akbarjimi\ExcelImporter\Models\ExcelSheet;
use Akbarjimi\ExcelImporter\Repositories\ExcelRowRepository;
use Akbarjimi\ExcelImporter\Repositories\ExcelSheetRepository;
use Throwable;

final class RowExtractionService implements RowExtractorInterface
{
    use LogsImportActivity;

    /** @var list<StagedRow> */
    private array $buffer = [];
    private int $inserted = 0;
    private int $batchSize;
    private string $hashAlgo;

    public function __construct(
        private readonly ExcelReaderDriver    $readerDriver,
        private readonly ExcelRowRepository   $rowRepository,
        private readonly ExcelSheetRepository $sheetRepository,
    )
    {
        $this->batchSize = (int)config('excel-importer.insert_batch_size', 100);
        $this->hashAlgo = config('excel-importer.hash_algo', 'sha256');
    }

    public function extract(ExcelSheet $sheet): int
    {
        $this->reset();

        try {
            $this->readerDriver->readRows(
                $sheet->excelFile->path,
                $sheet->sheet_index,
                fn(array $row) => $this->bufferRow($row, $sheet)
            );

            $this->flushBuffer();

            $this->sheetRepository->markAsExtracted($sheet->id);

            $this->importLog(LogLevel::INFO, "Extracted {$this->inserted} rows from sheet {$sheet->id}.", [
                'rows' => $this->inserted,
                'sheet_id' => $sheet->id,
            ]);

            return $this->inserted;
        } catch (Throwable $e) {
            $this->importLog(LogLevel::CRITICAL, "Extraction failed for sheet {$sheet->id}. Error: {$e->getMessage()}", [
                'sheet_id' => $sheet->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function reset(): void
    {
        $this->inserted = 0;
        $this->buffer = [];
    }

    private function bufferRow(array $row, ExcelSheet $sheet): void
    {
        $encoded = json_encode($row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $this->buffer[] = new StagedRow(
            sheetId: $sheet->id,
            content: $encoded,
            hashAlgo: $this->hashAlgo,
            contentHash: hash($this->hashAlgo, $encoded),
            createdAt: now()->toDateTimeString(),
            updatedAt: now()->toDateTimeString(),
        );

        if (count($this->buffer) >= $this->batchSize) {
            $this->flushBuffer();
        }
    }

    private function flushBuffer(): void
    {
        if (empty($this->buffer)) {
            return;
        }
        $data = array_map(fn(StagedRow $row) => $row->toArray(), $this->buffer);
        $this->rowRepository->bulkUpsert($data);
        $this->inserted += count($this->buffer);
        $this->buffer = [];
    }
}