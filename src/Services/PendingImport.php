<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Services;


use Akbarjimi\ExcelImporter\Contracts\ImportHandler;
use Akbarjimi\ExcelImporter\Enums\ExcelFileStatus;
use Akbarjimi\ExcelImporter\Events\ExcelFileRegistered;
use Akbarjimi\ExcelImporter\Models\ExcelFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class PendingImport
{
    private string $handler;
    private array $meta = [];

    public function __construct(
        private readonly string $path,
        private readonly string $disk,
    ) {}

    public function withHandler(string $handlerClass): self
    {
        if (!is_a($handlerClass, ImportHandler::class, true)) {
            throw new \InvalidArgumentException(
                "Handler must implement " . ImportHandler::class
            );
        }
        $this->handler = $handlerClass;
        return $this;
    }

    public function withMeta(array $meta): self
    {
        $this->meta = $meta;
        return $this;
    }

    public function dispatch(): ExcelFile
    {
        if (!isset($this->handler)) {
            throw new \RuntimeException('No handler set. Call withHandler() first.');
        }

        $storage = Storage::disk($this->disk);
        if (!$storage->exists($this->path)) {
            throw ImportFileNotFoundException::make($this->disk, $this->path);
        }

        return DB::transaction(function () use ($storage) {
            $file = ExcelFile::query()->create([
                'file_name' => basename($this->path),
                'path' => $this->path,
                'disk' => $this->disk,
                'size' => $storage->size($this->path),
                'status' => ExcelFileStatus::PENDING,
                'meta' => array_merge($this->meta, ['handler' => $this->handler]),
            ]);

            ExcelFileRegistered::dispatch($file->id);

            return $file;
        });
    }
}