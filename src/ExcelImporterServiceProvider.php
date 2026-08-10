<?php

namespace Akbarjimi\ExcelImporter;

use Akbarjimi\ExcelImporter\Events\AllRowsExtracted;
use Akbarjimi\ExcelImporter\Events\ExcelFileRegistered;
use Akbarjimi\ExcelImporter\Events\SheetReadyForExtraction;
use Akbarjimi\ExcelImporter\Events\FileSheetsScanCompleted;
use Akbarjimi\ExcelImporter\Listeners\HandleAllRowsExtracted;
use Akbarjimi\ExcelImporter\Listeners\HandleExcelFileRegistered;
use Akbarjimi\ExcelImporter\Listeners\HandleSheetReadyForExtraction;
use Akbarjimi\ExcelImporter\Listeners\HandleFileSheetsScanCompleted;
use Akbarjimi\ExcelImporter\Services\RowExtractionService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class ExcelImporterServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerEventListeners();
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->publishes([
            __DIR__.'/config/excel-importer.php' => config_path('excel-importer.php'),
        ], 'config');
    }

    public function register()
    {
        $this->app->bind(ChunkService::class);
        $this->app->bind(RowExtractionService::class);

        $this->mergeConfigFrom(
            __DIR__.'/config/excel-importer.php', 'excel-importer'
        );
        $this->loadFactoriesFrom(__DIR__.'/database/factories');
    }

    public function registerEventListeners(): void
    {
        Event::listen(ExcelFileRegistered::class, HandleExcelFileRegistered::class);
        Event::listen(FileSheetsScanCompleted::class, HandleFileSheetsScanCompleted::class);
        Event::listen(SheetReadyForExtraction::class, HandleSheetReadyForExtraction::class);
        Event::listen(AllRowsExtracted::class, HandleAllRowsExtracted::class);
    }
}
