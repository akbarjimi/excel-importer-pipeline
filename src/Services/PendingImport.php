<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Services;

use Akbarjimi\ExcelImporter\Contracts\ImportHandler;
use Akbarjimi\ExcelImporter\Enums\ExcelFileStatus;
use Akbarjimi\ExcelImporter\Events\ExcelFileRegistered;
use Akbarjimi\ExcelImporter\Exceptions\ImportFileNotFoundException;
use Akbarjimi\ExcelImporter\Exceptions\MissingHandlerException;
use Akbarjimi\ExcelImporter\Models\ExcelFile;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Support\Facades\DB;

final class PendingImport
{
    private string $handler;
    private array $meta = [];

    public function __construct(
        private readonly string $path,
        private readonly string $disk,
        private readonly FilesystemFactory $storage = new FilesystemFactory(),
    ) {}

    public function withHandler(string $handlerClass): self
    {
        if (!is_a($handlerClass, ImportHandler::class, true)) {
            throw new \InvalidArgumentException(
                sprintf('Handler must implement [%s]', ImportHandler::class)
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

    /**
     * @throws ImportFileNotFoundException
     * @throws MissingHandlerException
     */
    public function dispatch(): ExcelFile
    {
        throw_unless(isset($this->handler), MissingHandlerException::class);

        $storage = $this->storage->disk($this->disk);

        throw_unless($storage->exists($this->path), ImportFileNotFoundException::class, [
            'disk' => $this->disk,
            'path' => $this->path,
        ]);

        return DB::transaction(function () use ($storage) {
            $file = ExcelFile::query()->create([
                'file_name' => basename($this->path),
                'path'      => $this->path,
                'disk'      => $this->disk,
                'size'      => $storage->size($this->path),
                'status'    => ExcelFileStatus::PENDING,
                'meta'      => array_merge(
                    $this->meta,
                    ['handler' => $this->handler]
                ),
            ]);

            // Will be dispatched after commit (uses ShouldDispatchAfterCommit)
            ExcelFileRegistered::dispatch($file->id);

            return $file;
        });
    }
}