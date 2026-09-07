<?php

namespace Tests\Stubs\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tests\Stubs\Models\UserStub;

class UserStubFactory extends Factory
{
    protected $model = UserStub::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'age' => $this->faker->numberBetween(18, 80),
        ];
    }
}
