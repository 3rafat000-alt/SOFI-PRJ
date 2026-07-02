<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyEmployee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CompanyEmployeeFactory extends Factory
{
    protected $model = CompanyEmployee::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'company_id' => Company::factory(),
            'phone' => '09' . $this->faker->numerify('########'),
            'name' => $this->faker->name(),
            'job_title' => $this->faker->jobTitle(),
            'default_amount' => $this->faker->randomFloat(2, 50, 1000),
            'default_currency' => 'USD',
            'status' => 'active',
            'is_active' => true,
        ];
    }
}
