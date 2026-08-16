# Laravel Excel Importer

**Distributed, fault-tolerant Excel importer for Laravel 10**

![Tests](https://github.com/akbarjimi/excel-importer-enterprise/actions/workflows/tests.yml/badge.svg)
> Supports queues, chunked processing, row-level error reporting, and configurability.

## Installation
```bash
composer require akbarjimi/laravel-excel-importer
```

## Usage Example
```php
// Inject service into controller
\$importer->import('/full/path/to/excel.xlsx');
```

## Documentation
- [docs/PRD.md](docs/PRD.md)
- [docs/ARCHITECTURE.md](docs/internal/ARCHITECTURE.md)
- [docs/USAGE.md](docs/USAGE.md)

## License
MIT
