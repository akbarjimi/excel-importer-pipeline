<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Tests;

use Akbarjimi\ExcelImporter\ExcelImporterServiceProvider;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Use sync queue for tests to run jobs immediately
        config(['queue.default' => 'sync']);

        // Load the test config for sheets
        config(['excel-importer-sheets' => require __DIR__ . '/_fixtures/config/excel-importer-sheets.php']);

        // Fake events and jobs by default (can be overridden in specific tests)
        Event::fake();
        Bus::fake();
        Queue::fake();
    }

    /**
     * Load your service provider.
     */
    protected function getPackageProviders($app): array
    {
        return [
            ExcelImporterServiceProvider::class,
            \Maatwebsite\Excel\ExcelServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set test-specific config values
        $app['config']->set('excel-importer.default_disk', 'local');
        $app['config']->set('excel-importer.hash_algo', 'md5');
        $app['config']->set('excel-importer.max_sheets', 50);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../src/database/migrations');
    }

    /**
     * Helper to copy test Excel files to storage.
     */
    protected function copyTestFileToStorage(string $source, string $destination): void
    {
        $storagePath = storage_path($destination);
        $directory = dirname($storagePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        copy($source, $storagePath);
    }
}