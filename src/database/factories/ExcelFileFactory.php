<?php

namespace Akbarjimi\ExcelImporter\Database\Factories;

use Akbarjimi\ExcelImporter\Enums\ExcelFileStatus;
use Akbarjimi\ExcelImporter\Models\ExcelFile;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExcelFileFactory extends Factory
{
    protected $model = ExcelFile::class;

    public function definition(): array
    {
        return [
            'file_name' => $this->faker->word() . '.xlsx',
            'path' => 'testing/' . $this->faker->uuid() . '.xlsx',
            'disk' => 'local',
            'size' => $this->faker->numberBetween(1024, 10485760), // 1KB to 10MB
            'status' => ExcelFileStatus::PENDING,
        ];
    }
}
