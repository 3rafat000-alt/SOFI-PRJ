<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VirtualCardFactory extends Factory
{
    protected $model = \App\Models\VirtualCard::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'wallet_id' => Wallet::factory(),
            'uuid' => Str::uuid(),
            'brand' => $this->faker->randomElement(['visa', 'mastercard']),
            'card_type' => 'virtual',
            'card_number' => encrypt('4' . $this->faker->numerify('################')),
            'card_number_masked' => '**** **** **** ' . $this->faker->numerify('####'),
            'cvv' => encrypt($this->faker->numerify('###')),
            'expiry_month' => $this->faker->numberBetween(1, 12),
            'expiry_year' => now()->addYears(3)->year,
            'cardholder_name' => $this->faker->name,
            'balance' => $this->faker->randomFloat(2, 0, 5000),
            'spending_limit' => 10000,
            'status' => 'active',
            'is_active' => true,
        ];
    }

    public function visa(): static
    {
        return $this->state(fn(array $attributes) => [
            'brand' => 'visa',
        ]);
    }

    public function mastercard(): static
    {
        return $this->state(fn(array $attributes) => [
            'brand' => 'mastercard',
        ]);
    }

    public function frozen(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'frozen',
            'is_active' => false,
        ]);
    }
}
