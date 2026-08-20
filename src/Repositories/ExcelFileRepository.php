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

    public function markRowsExtracted(int $fileId): void
    {
        ExcelFile::whereKey($fileId)->update([
            'status' => 'rows_extracted',
            'rows_extracted_at' => Carbon::now(),
        ]);
    }

    public function markFailed(int $fileId): void
    {
        ExcelFile::whereKey($fileId)->update([
            'status' => 'failed',
            'failed_at' => Carbon::now(),
        ]);
    }

}
