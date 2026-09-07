<?php

use Tests\Stubs\Models\UserStub;

return [
    // 1sheet3rows1header.xlsx
    'Sheet1' => [
        'transformers' => [
            'A1' => fn (string $value): string => strtoupper($value),
            'B1' => fn (string $value): string => strtolower($value),
            'C1' => fn (string|int $value): int => (int) $value,
        ],
        'validation' => [
            'A1' => 'required|string|max:255',
            'B1' => 'required|string|email',
            'C1' => 'required|integer|min:31',
        ],
        'mapper' => function (array $row) {
            $user = new UserStub;
            $user->fill([
                'name' => $row['A1'],
                'email' => $row['B1'],
                'age' => $row['C1'],
            ]);

            return $user;
        },
    ],
    // 2sheets2rows.xlsx
    'Sheet2' => [
        'transformers' => [
            'A1' => fn ($value) => intval($value),
            'B1' => fn ($value) => intval($value),
        ],
        'validation' => [
            'A1' => 'required|int',
            'B1' => 'required|int',
        ],
        'mapper' => fn (array $row) => $row,
    ],
    'Sheet3' => [
        'transformers' => [
            'A1' => fn ($value) => intval($value),
            'B1' => fn ($value) => intval($value),
        ],
        'validation' => [
            'A1' => 'required|int',
            'B1' => 'required|int',
        ],
        'mapper' => fn (array $row) => $row,
    ],
    // 2sheets2000rows.xlsx
    'Sheet4' => [
        'transformers' => [
            'A1' => fn ($value) => intval($value),
        ],
        'validation' => [
            'A1' => 'required|int|max:2000',
        ],
        'mapper' => fn (array $row) => $row,
    ],
    'Sheet5' => [
        'transformers' => [
            'A1' => fn ($value) => intval($value),
        ],
        'validation' => [
            'A1' => 'required|int|max:2000',
        ],
        'mapper' => fn (array $row) => $row,
    ],
];
