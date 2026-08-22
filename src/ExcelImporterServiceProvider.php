<?php

declare(strict_types=1);

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
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'excel-importer');

        $this->publishes([
            __DIR__ . '/../../lang' => $this->app->langPath('vendor/excel-importer'),
        ], 'excel-importer-lang');

        $this->app->bind(ChunkService::class);

        $this->app->bind(RowExtractionService::class);

        $this->app->singleton(ExcelReaderManager::class);

        $this->app->bind(ExcelReaderDriver::class, function ($app) {
            return $app->make(ExcelReaderManager::class)->driver();
        });

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
