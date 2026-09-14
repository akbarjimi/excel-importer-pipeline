<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Tests;

use Akbarjimi\ExcelImporter\ExcelImporterServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['queue.default' => 'sync']);
        config(['excel-importer-sheets' => require __DIR__.'/_fixtures/config/excel-importer-sheets.php']);

        $this->createJobBatchesTable();
    }

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

        $app['config']->set('excel-importer.default_disk', 'local');
        $app['config']->set('excel-importer.hash_algo', 'md5');
        $app['config']->set('excel-importer.max_sheets', 50);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../src/database/migrations');
    }

    private function createJobBatchesTable(): void
    {
        $connection = $this->app['db']->connection();

        if ($connection->getSchemaBuilder()->hasTable('job_batches')) {
            return;
        }

        $connection->getSchemaBuilder()->create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });
    }
}