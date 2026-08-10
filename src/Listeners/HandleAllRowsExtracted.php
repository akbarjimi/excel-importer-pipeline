<?php

namespace Akbarjimi\ExcelImporter\Listeners;

use Akbarjimi\ExcelImporter\Enums\ExcelFileStatus;
use Akbarjimi\ExcelImporter\Events\AllRowsExtracted;
use Akbarjimi\ExcelImporter\Jobs\ProcessChunkJob;
use Akbarjimi\ExcelImporter\Models\ExcelFile;
use Akbarjimi\ExcelImporter\Services\ChunkerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

final class HandleAllRowsExtracted implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(private readonly ChunkerService $chunker) {}

    public function handle(AllRowsExtracted $event): void
    {
        $file = ExcelFile::query()->with('excelSheets')->findOrFail($event->fileId);

        // Create all chunks transactionally
        $chunks = $this->chunker->createChunksForFile($file);

        if ($chunks->isEmpty()) {
            Log::warning('No chunks created for file', ['file_id' => $file->getKey()]);

            return;
        }

        // Dispatch AFTER COMMIT to avoid enqueuing for rolled-back rows
        $batch = Bus::batch(
            $chunks->map(fn ($c) => (new ProcessChunkJob($c->getKey()))->afterCommit())
        )->name("excel-file:{$file->getKey()}:row-chunks")
            ->onQueue(config('excel-importer.queue', 'default'))
            ->dispatch();

        Log::info('Chunk jobs batched', [
            'file_id' => $file->getKey(),
            'batch_id' => $batch->id,
            'jobs' => $chunks->count(),
        ]);

        $file->update(['status' => ExcelFileStatus::PROCESSING->value]);
    }

    public function failed(AllRowsExtracted $event, \Throwable $e): void
    {
        // TODO: clean up orphaned chunk records that may have been created before the failure

        Log::error('HandleAllSheetsDispatched failed', [
            'file_id' => $event->fileId,
            'error' => $e->getMessage(),
        ]);

        ExcelFile::whereKey($event->fileId)->update([
            'status' => ExcelFileStatus::FAILED->value,
        ]);
    }
}
