<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\Agency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentFactory extends Factory
{
    protected $model = Agent::class;

    public function definition(): array
    {
        return [
            'user_id'      => User::factory(),
            'agency_id'    => Agency::factory(),
            'display_name' => fake()->name(),
            'email'        => fake()->safeEmail(),
            'phone'        => fake()->phoneNumber(),
            'bio_ar'       => fake()->realText(150),
            'bio_en'       => fake()->realText(150),
            'license_no'   => 'AG-' . fake()->bothify('#######'),
            'rating'       => fake()->randomFloat(1, 3, 5),
            'reviews_count'=> fake()->numberBetween(0, 50),
            'status'       => 'active',
            'verified_at'  => now(),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verified_at' => null,
        ]);
    }
}
