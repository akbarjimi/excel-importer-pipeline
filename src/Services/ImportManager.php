<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Services;

use Illuminate\Contracts\Config\Repository as Config;

final  class ImportManager
{
    public function __construct(private Config $config)
    {
    }

    public function import(string $path, ?string $disk = null): PendingImport
    {
        $disk ??= $this->config->get('excel-importer.default_disk', $this->config->get('filesystems.default'));

        return new PendingImport($path, $disk);
    }
}