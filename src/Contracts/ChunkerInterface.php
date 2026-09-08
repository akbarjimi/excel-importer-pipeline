<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Contracts;

use Akbarjimi\ExcelImporter\Models\ExcelFile;
use Akbarjimi\ExcelImporter\Models\ExcelRowChunk;
use Illuminate\Support\Collection;

interface ChunkerInterface
{
    /**
     * @return Collection<int, ExcelRowChunk>
     */
    public function createChunksForFile(ExcelFile $file): Collection;
}
