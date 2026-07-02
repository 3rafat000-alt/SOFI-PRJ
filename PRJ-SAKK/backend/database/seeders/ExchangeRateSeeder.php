<?php

namespace Database\Seeders;

use App\Models\ExchangeRate;
use Illuminate\Database\Seeder;

class ExchangeRateSeeder extends Seeder
{
    public function run(): void
    {
        // Canonical TRUE scale: 1 USD ≈ 13,000 SYP (matches KYC limits, demo data,
        // and the mobile/admin displays — no ÷100 anywhere). spread 2% → ±1% buy/sell.
        ExchangeRate::updateOrCreate(
            ['from_currency' => 'USD', 'to_currency' => 'SYP'],
            [
                'rate' => 13000,
                'buy_rate' => 12870,   // 13000 × 0.99
                'sell_rate' => 13130,  // 13000 × 1.01
                'spread' => 2,
                'source' => 'manual',
                'is_active' => true,
                'fetched_at' => now(),
            ]
        );

        // Reverse row kept consistent (1 / 13000). NOTE: conversions no longer read
        // this row — WalletService::convert derives the inverse from the USD→SYP row
        // above so the two can never drift. Seeded only for any read-only consumers.
        ExchangeRate::updateOrCreate(
            ['from_currency' => 'SYP', 'to_currency' => 'USD'],
            [
                'rate' => 1 / 13000,        // ≈ 0.00007692
                'buy_rate' => 1 / 13130,    // ≈ 0.00007616
                'sell_rate' => 1 / 12870,   // ≈ 0.00007770
                'spread' => 2,
                'source' => 'manual',
                'is_active' => true,
                'fetched_at' => now(),
            ]
        );

        $this->command->info('✅ Created default exchange rates (USD/SYP)');
    }
}
