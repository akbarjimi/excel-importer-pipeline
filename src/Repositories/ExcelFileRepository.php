<?php

namespace Akbarjimi\ExcelImporter\Repositories;

use Akbarjimi\ExcelImporter\Models\ExcelFile;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExcelFileRepository
{
    public function findFile(int $fileId, array $relations = []): ?ExcelFile
    {
        return ExcelFile::with($relations)->find($fileId);
    }
}
