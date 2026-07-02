<?php

namespace Database\Factories;

use App\Enums\KycStatus;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = \App\Models\User::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->unique()->numerify('+9639########'),
            'password' => Hash::make('password'),
            'status' => UserStatus::ACTIVE,
            'kyc_status' => KycStatus::VERIFIED,
            'kyc_level' => 2,
            'is_active' => true,
            'language' => 'ar',
            'email_verified_at' => now(),
            'referral_code' => strtoupper(Str::random(8)),
            'pin_code' => Hash::make('123456'),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => UserStatus::SUSPENDED,
        ]);
    }

    public function kycPending(): static
    {
        return $this->state(fn(array $attributes) => [
            'kyc_status' => KycStatus::PENDING,
        ]);
    }
}
