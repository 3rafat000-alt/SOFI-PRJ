<?php

namespace Database\Factories;

use App\Models\Integration;
use Illuminate\Database\Eloquent\Factories\Factory;

class IntegrationFactory extends Factory
{
    protected $model = Integration::class;

    public function definition(): array
    {
        return [
            'key' => $this->faker->unique()->word(),
            'name' => $this->faker->word(),
            'name_ar' => $this->faker->word(),
            'is_active' => true,
            'is_visible' => true,
            'config' => [],
            'credentials' => [],
            'settings' => [],
        ];
    }
}
