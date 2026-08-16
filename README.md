# Laravel Excel Pipeline

[![Latest Version on Packagist](https://img.shields.io/packagist/v/akbarjimi/laravel-excel-pipeline.svg?style=flat-square)](https://packagist.org/packages/akbarjimi/laravel-excel-pipeline)
[![Tests](https://img.shields.io/github/actions/workflow/status/akbarjimi/laravel-excel-pipeline/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/akbarjimi/laravel-excel-pipeline/actions)
[![Total Downloads](https://img.shields.io/packagist/dt/akbarjimi/laravel-excel-pipeline.svg?style=flat-square)](https://packagist.org/packages/akbarjimi/laravel-excel-pipeline)

Import large Excel files in Laravel without timeouts, memory exhaustion, or
all-or-nothing failures. The import runs as a staged, event-driven pipeline on
your queue workers — fully observable in Laravel Horizon.

## Why this package?

Importing a 100,000-row spreadsheet inside an HTTP request fails in predictable
ways: the request times out, the worker runs out of memory, and a single
malformed row can abort the entire import with no indication of what went
wrong.

This package treats an import as a durable, multi-stage workflow:

1. **Discovery** — the file's sheets are detected and persisted
2. **Extraction** — each sheet's rows are extracted by a batch of queued jobs
3. **Processing** — rows are validated and transformed in configurable chunks

Each stage emits events, every batch is named and visible in Horizon, and
invalid rows are recorded individually while the rest of the file continues.

## Requirements

- PHP 8.4+
- Laravel 12.x
- A queue driver that supports batches (Redis recommended)

## Installation

Install via Composer:
```bash
composer require akbarjimi/laravel-excel-pipeline
```

Publish and run the migrations (the package uses Laravel's job batching, so
the `job_batches` table is required):

```bash
php artisan vendor:publish --tag="excel-pipeline-migrations"
php artisan queue:batches-table
php artisan migrate
```
Optionally publish the config file:

```bash
php artisan vendor:publish --tag="excel-pipeline-config"
```

## Quick start

Register an uploaded file and let the pipeline take over:

```php
ExcelPipeline::import($request->file('spreadsheet'));
```

Track progress by listening to pipeline events:

```php
use Akbarjimi\ExcelPipeline\Events\AllRowsExtracted;

Event::listen(AllRowsExtracted::class, function (AllRowsExtracted $event) {
// all sheets extracted — processing stage begins
});
```

## Pipeline events

| Event | Fired when |
| --- | --- |
| `ExcelFileRegistered` | A file has been registered for import |
| `FileSheetsScanCompleted` | Sheet discovery has finished |
| `SheetRowsExtracted` | A single sheet's rows have been extracted |
| `AllRowsExtracted` | Every sheet in the file has been extracted |
| `SheetProcessingCompleted` | A sheet's chunks have all been processed |

## Handling invalid rows

Rows that fail validation do not abort the import. Each failure is recorded
with its row number and validation errors, and the file completes with
partial-success semantics:

```php
$file->rowErrors; // collection of per-row validation failures
```

## Configuration

```php
return [
'chunk_size' => 500,          // rows per processing chunk
'max_sheets' => 10,           // reject files exceeding this
'hash_algo' => 'xxh128',      // row content hashing for idempotency
'logging' => [
'enabled' => true,
'channels' => ['stack'],
],
];
```

## Horizon integration

Every extraction and processing batch is named after its file
(`excel-import:{id}`), so you can follow an individual import through
Horizon's batch dashboard, inspect failed jobs, and retry them safely —
all jobs are idempotent.

## Custom reader drivers

The Excel reading engine sits behind a contract. Implement
`ExcelReaderDriver` to plug in a different engine without touching the
pipeline:

```php
interface ExcelReaderDriver
{
public function readRows(string $filePath, int $sheetIndex, callable $callback): void;
}
```

## Testing

```bash
composer test
```

## Changelog

See [CHANGELOG](CHANGELOG.md) for recent changes.

## Contributing

See [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

Please report security vulnerabilities via the process in [SECURITY](SECURITY.md),
not the public issue tracker.

## Credits

- [Akbar Jimi](https://github.com/akbarjimi)

## License

The MIT License (MIT). See [LICENSE](LICENSE.md) for details.


Two things flagged inside the README as TODOs deserve emphasis: the quick-start API call and the event names must match your real code, and the configuration block reflects the config keys we've discussed (`hash_algo`, `logging.channels`) plus plausible ones (`chunk_size`, `max_sheets`) that you should reconcile with your actual config file. You'll also want CHANGELOG, CONTRIBUTING, SECURITY, and LICENSE files to exist before linking to them — Packagist and laravel-news reviewers do click those links.
