<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Models;

use Akbarjimi\ExcelImporter\Database\Factories\ExcelRowFactory;
use Akbarjimi\ExcelImporter\Enums\ExcelRowStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ExcelRow extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'excel_sheet_id',
        'row_index',
        'content',
        'hash_algo',
        'content_hash',
        'status',
        'chunk_index',
    ];

    protected $casts = [
        'status' => ExcelRowStatus::class,
        'content' => 'array',
        'row_index' => 'integer',
        'chunk_index' => 'integer',
    ];

    public function excelSheet(): BelongsTo
    {
        return $this->belongsTo(ExcelSheet::class)->withTrashed();
    }

    protected static function newFactory(): ExcelRowFactory
    {
        return ExcelRowFactory::new();
    }
}