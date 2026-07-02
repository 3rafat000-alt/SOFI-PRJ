<?php

namespace Database\Factories;

use App\Models\Fee;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeeFactory extends Factory
{
    protected $model = Fee::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->word(),
            'name' => $this->faker->word(),
            'name_en' => $this->faker->word(),
            'type' => $this->faker->randomElement(['deposit', 'withdrawal', 'card_fund']),
            'currency' => 'USD',
            'fixed_amount' => 0,
            'percentage' => 1.0,
            'min_fee' => 0,
            'max_fee' => null,
            'min_amount' => 0,
            'max_amount' => null,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
