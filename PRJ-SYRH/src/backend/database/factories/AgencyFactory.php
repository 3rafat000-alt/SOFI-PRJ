<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AgencyFactory extends Factory
{
    protected $model = Agency::class;

    public function definition(): array
    {
        $name = fake()->company() . ' العقارية';

        return [
            'name'             => $name,
            'slug'             => Str::slug($name),
            'description_ar'   => fake()->realText(200),
            'description_en'   => fake()->realText(200),
            'phone'            => fake()->phoneNumber(),
            'email'            => fake()->companyEmail(),
            'whatsapp'         => fake()->phoneNumber(),
            'address'          => fake()->address(),
            'license_no'       => 'LIC-' . fake()->bothify('#######'),
            'commission_rate'  => fake()->randomFloat(1, 1, 5),
            'status'           => 'active',
            'owner_id'         => User::factory(),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verified_at' => null,
        ]);
    }
}
