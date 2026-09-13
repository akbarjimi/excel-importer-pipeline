<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Console\Commands;

use Akbarjimi\ExcelImporter\Enums\ExcelChunkStatus;
use Akbarjimi\ExcelImporter\Enums\ExcelFileStatus;
use Akbarjimi\ExcelImporter\Events\FileProcessingCompleted;
use Akbarjimi\ExcelImporter\Jobs\ProcessChunkJob;
use Akbarjimi\ExcelImporter\Models\ExcelFile;
use Akbarjimi\ExcelImporter\Models\ExcelRowChunk;
use Akbarjimi\ExcelImporter\Repositories\ExcelFileRepository;
use Akbarjimi\ExcelImporter\Repositories\ExcelRowChunkRepository;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Throwable;

final class RetryCommand extends Command
{
    protected $signature = 'excel:retry {fileId : Excel file ID}';

    protected $description = 'Re-dispatch failed chunks for an Excel import file.';

    public function handle(
        ExcelFileRepository $fileRepository,
        ExcelRowChunkRepository $chunkRepository,
    ): int {
        $fileId = (int) $this->argument('fileId');
        $file = ExcelFile::find($fileId);

        if ($file === null) {
            $this->error("File [{$fileId}] not found.");

            return self::FAILURE;
        }

        if ($file->trashed()) {
            $this->error("File [{$fileId}] is soft-deleted.");

            return self::FAILURE;
        }

        $failedIds = ExcelRowChunk::query()
            ->whereHas('excelSheet', fn ($q) => $q->where('excel_file_id', $fileId))
            ->where('status', ExcelChunkStatus::FAILED->value)
            ->pluck('id')
            ->all();

        if ($failedIds === []) {
            $this->info("No failed chunks for file [{$fileId}].");

            return self::SUCCESS;
        }

        if ($file->status === ExcelFileStatus::FAILED) {
            $fileRepository->markAsPending($fileId);
        }

        $reset = $chunkRepository->markManyAsPending($failedIds);

        $jobs = array_map(
            static fn (int $id): ProcessChunkJob => new ProcessChunkJob($id),
            $failedIds,
        );

        Bus::batch($jobs)
            ->name("excel-retry:{$fileId}")
            ->onQueue(config('excel-importer.queue', 'default'))
            ->allowFailures(false)
            ->then(function (Batch $batch) use ($fileId, $fileRepository): void {
                $fileRepository->markAsProcessing($fileId);
                $fileRepository->markAsCompleted($fileId);
                FileProcessingCompleted::dispatch($fileId);
            })
            ->catch(function (Batch $batch, Throwable $e) use ($fileId, $fileRepository): void {
                $fileRepository->markAsFailed($fileId, $e->getMessage());
            })
            ->dispatch();

        $this->info("Reset {$reset} chunks. Dispatched ".count($jobs)." retry jobs.");

        return self::SUCCESS;
    }
}