<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PayrollBatch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PayrollBatchFactory extends Factory
{
    protected $model = PayrollBatch::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'company_id' => Company::factory(),
            'currency' => 'USD',
            'status' => PayrollBatch::STATUS_DRAFT,
            'idempotency_key' => (string) Str::uuid(),
            'total_amount' => 0,
            'items_count' => 0,
        ];
    }
}
