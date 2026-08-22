<?php

namespace Akbarjimi\ExcelImporter\Models;

use Akbarjimi\ExcelImporter\Database\Factories\ExcelFileFactory;
use Akbarjimi\ExcelImporter\Enums\ExcelFileStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

final class ExcelFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_name',
        'path',
        'disk',
        'size',
        'status',
    ];

    protected $casts = [
        'status' => ExcelFileStatus::class,
        'size' => 'integer',
    ];

    public function excelSheets(): HasMany
    {
        return $this->hasMany(ExcelSheet::class);
    }

    protected static function newFactory(): ExcelFileFactory
    {
        return ExcelFileFactory::new();
    }
}
