<?php

namespace Database\Factories;

use App\Models\ReferralReward;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReferralRewardFactory extends Factory
{
    protected $model = ReferralReward::class;

    public function definition(): array
    {
        return [
            'referrer_id' => 1,
            'referred_id' => 2,
            'transaction_id' => null,
            'referrer_reward' => 5.0,
            'referred_reward' => 0,
            'currency' => 'USD',
            'trigger' => 'kyc_verified',
            'status' => 'credited',
        ];
    }
}
