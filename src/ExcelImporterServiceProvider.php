<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter;

use Akbarjimi\ExcelImporter\Contracts\ChunkerInterface;
use Akbarjimi\ExcelImporter\Contracts\ExcelReaderDriver;
use Akbarjimi\ExcelImporter\Contracts\RowExtractorInterface;
use Akbarjimi\ExcelImporter\Contracts\SheetDiscoveryInterface;
use Akbarjimi\ExcelImporter\Contracts\TransformerInterface;
use Akbarjimi\ExcelImporter\Contracts\ValidatorInterface;
use Akbarjimi\ExcelImporter\Events\AllRowsExtracted;
use Akbarjimi\ExcelImporter\Events\ExcelFileRegistered;
use Akbarjimi\ExcelImporter\Events\FileProcessingCompleted;
use Akbarjimi\ExcelImporter\Events\FileSheetsScanCompleted;
use Akbarjimi\ExcelImporter\Events\SheetReadyForExtraction;
use Akbarjimi\ExcelImporter\Listeners\HandleAllRowsExtracted;
use Akbarjimi\ExcelImporter\Listeners\HandleExcelFileRegistered;
use Akbarjimi\ExcelImporter\Listeners\HandleFileSheetsScanCompleted;
use Akbarjimi\ExcelImporter\Listeners\HandleSheetReadyForExtraction;
use Akbarjimi\ExcelImporter\Listeners\InvokeImportHandler;
use Akbarjimi\ExcelImporter\Services\ChunkerService;
use Akbarjimi\ExcelImporter\Services\LocalFileResolver;
use Akbarjimi\ExcelImporter\Services\RowExtractionService;
use Akbarjimi\ExcelImporter\Services\SheetDiscoveryService;
use Akbarjimi\ExcelImporter\Services\TransformService;
use Akbarjimi\ExcelImporter\Services\ValidateService;
use Akbarjimi\ExcelImporter\Support\ExcelReaderManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class ExcelImporterServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerEventListeners();
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        $this->publishes([
            __DIR__ . '/config/excel-importer.php' => config_path('listener.php'),
        ], 'config');

    }

    public function register()
    {
        $this->app->bind(SheetDiscoveryInterface::class, SheetDiscoveryService::class);
        $this->app->bind(RowExtractorInterface::class, RowExtractionService::class);
        $this->app->bind(ChunkerInterface::class, ChunkerService::class);
        $this->app->bind(TransformerInterface::class, TransformService::class);
        $this->app->bind(ValidatorInterface::class, ValidateService::class);

        $this->app->bind(RowExtractionService::class);

        $this->app->bind(LocalFileResolver::class);

        $this->app->singleton(ExcelReaderManager::class);

        $this->app->bind(ExcelReaderDriver::class, function ($app) {
            return $app->make(ExcelReaderManager::class)->driver();
        });

        $this->mergeConfigFrom(
            __DIR__ . '/config/excel-importer.php', 'excel-importer'
        );
        $this->loadFactoriesFrom(__DIR__ . '/database/factories');

    }

    public function registerEventListeners(): void
    {
        Event::listen(ExcelFileRegistered::class, HandleExcelFileRegistered::class);
        Event::listen(FileSheetsScanCompleted::class, HandleFileSheetsScanCompleted::class);
        Event::listen(AllRowsExtracted::class, HandleAllRowsExtracted::class);
        Event::listen(FileProcessingCompleted::class, InvokeImportHandler::class);
    }
}
