<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Wallet;
use App\Enums\TransactionType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TransactionFactory extends Factory
{
    protected $model = \App\Models\Transaction::class;

    public function definition(): array
    {
        $amount = $this->faker->randomFloat(2, 10, 1000);
        $types = TransactionType::cases();
        $type = $types[array_rand($types)];
        
        return [
            'user_id' => User::factory(),
            'wallet_id' => Wallet::factory(),
            'uuid' => Str::uuid(),
            'reference' => 'TXN-' . strtoupper(Str::random(10)),
            'type' => $type,
            'category' => TransactionCategory::WALLET,
            'currency' => 'USD',
            'amount' => $type->isCredit() ? $amount : -$amount,
            'fee' => $this->faker->randomFloat(2, 0, 50),
            'net_amount' => $type->isCredit() ? $amount : -$amount,
            'balance_before' => $this->faker->randomFloat(2, 100, 5000),
            'balance_after' => $this->faker->randomFloat(2, 100, 5000),
            'status' => TransactionStatus::COMPLETED,
            'title' => $type->label(),
            'description' => $this->faker->sentence,
            'completed_at' => now(),
        ];
    }

    public function deposit(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => TransactionType::DEPOSIT,
            'amount' => $this->faker->randomFloat(2, 50, 5000),
            'title' => 'إيداع',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => TransactionStatus::PENDING,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => TransactionStatus::FAILED,
        ]);
    }
}
