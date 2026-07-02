<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'device_id' => Str::uuid(),
            'device_name' => $this->faker->words(2, true),
            'device_type' => $this->faker->randomElement(['mobile', 'tablet', 'web']),
            'public_key' => Str::random(64),
            'is_trusted' => false,
            'status' => Device::STATUS_PENDING,
            'last_ip' => $this->faker->ipv4,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Device::STATUS_APPROVED,
            'is_trusted' => true,
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Device::STATUS_REJECTED,
        ]);
    }
}
