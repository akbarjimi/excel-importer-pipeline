<?php

return [
    'chunk_size' => env('EXCEL_IMPORTER_CHUNK_SIZE', 1000),
    'insert_batch_size' => env('EXCEL_IMPORTER_INSERT_BATCH_SIZE', 100),
    'default_disk' => env('EXCEL_IMPORTER_DISK', 'local'),
    'hash_algo' => env('EXCEL_IMPORTER_HASH_ALGO', 'md5'),
    'queue' => env('EXCEL_IMPORTER_QUEUE', 'default'),
    'mapper_batch_size' => env('EXCEL_IMPORTER_MAPPER_BATCH_SIZE', 500),
    'max_sheets' => 50,
    'logging' => [
        'enabled' => true,
        'channels' => ['stack'],
        'level' => 'info', // debug, info, warning, error
    ],
    'strict_validation' => false,
    'driver' => env('EXCEL_IMPORTER_DRIVER', 'maatwebsite'),
    'drivers' => [
        'maatwebsite' => \Akbarjimi\ExcelImporter\Drivers\MaatwebsiteDriver::class,
        'openspout'   => \Akbarjimi\ExcelImporter\Drivers\OpenSpoutDriver::class,
    ],    
];
