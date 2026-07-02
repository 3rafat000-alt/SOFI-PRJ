<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'company_code' => 'CO-' . str_pad((string) $this->faker->unique()->numberBetween(1000, 999999), 6, '0', STR_PAD_LEFT),
            'name' => $this->faker->company(),
            'owner_name' => $this->faker->name(),
            'email' => $this->faker->unique()->companyEmail(),
            'phone' => '09' . $this->faker->numerify('########'),
            'is_active' => true,
            'is_verified' => false,
            'payroll_enabled' => false,
            'kyc_status' => 'pending',
            'kyc_submitted_at' => now(),
        ];
    }

    /** A KYC-approved company (with a portal operator) that may run payroll. */
    public function payrollReady(): static
    {
        return $this->state(fn () => [
            'user_id' => User::factory(), // portal operator who runs payroll
            'is_verified' => true,
            'payroll_enabled' => true,
            'kyc_status' => 'approved',
            'kyc_approved_at' => now(),
        ]);
    }
}
