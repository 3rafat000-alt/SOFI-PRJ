<?php

namespace Database\Factories;

use App\Models\ExchangeRate;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExchangeRateFactory extends Factory
{
    protected $model = ExchangeRate::class;

    public function definition(): array
    {
        $rate = $this->faker->randomFloat(2, 10000, 15000);
        $spread = 2.0;
        $halfSpread = $spread / 200;

        return [
            'from_currency' => 'USD',
            'to_currency' => 'SYP',
            'rate' => $rate,
            'buy_rate' => $rate * (1 - $halfSpread),
            'sell_rate' => $rate * (1 + $halfSpread),
            'spread' => $spread,
            'source' => 'manual',
            'is_active' => true,
            'fetched_at' => now(),
        ];
    }
}
