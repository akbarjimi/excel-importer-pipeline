<?php

namespace Akbarjimi\Services;

use Akbarjimi\ExcelImporter\Enums\ExcelRowStatus;
use Akbarjimi\ExcelImporter\Models\ExcelRow;

final class ErrorReportService
{
    public function getBrokenRows(int $fileId, int $perPage = 50): LengthAwarePaginator
    {
        return ExcelRow::with('errors')
            ->whereHas('excelSheet', fn ($q) => $q->where('excel_file_id', $fileId))
            ->where('status', ExcelRowStatus::FAILED_VALIDATION)
            ->paginate($perPage);
    }

    public function downloadBrokenRows(int $fileId, string $format = 'xlsx'): void // BinaryFileResponse
    {
        // Generate Excel file with errors column.
        // Return response()->download(...)
    }
}
