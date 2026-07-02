<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Correct the live USD/SYP exchange-rate magnitude.
 *
 * The live rows had drifted to a broken scale (USD→SYP rate = 13, and a stale
 * SYP→USD reverse row ≈ 7.7e-05 that was ~1000× off and never refreshed by any
 * admin path). The canonical TRUE scale is 1 USD ≈ 13,000 SYP — consistent with
 * the KYC limits, demo data, and the (now ÷100-free) mobile/admin displays.
 *
 * Reversible: down() restores the previous broken values so the migration can be
 * rolled back without data loss (migration-without-rollback is rejected by policy).
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // Canonical USD→SYP @ 13,000 with a 2% spread (±1%).
        DB::table('exchange_rates')
            ->where('from_currency', 'USD')
            ->where('to_currency', 'SYP')
            ->update([
                'rate' => 13000,
                'buy_rate' => 12870,   // 13000 × 0.99
                'sell_rate' => 13130,  // 13000 × 1.01
                'spread' => 2,
                'updated_at' => $now,
            ]);

        // Keep the reverse row consistent (1 / 13000). UPSERT so it survives even
        // when the reverse row is stale (rate > 1) and would be deleted by an old
        // DELETE-first approach — the UPDATE-alone fix left zero rows behind.
        DB::table('exchange_rates')
            ->updateOrInsert(
                ['from_currency' => 'SYP', 'to_currency' => 'USD'],
                [
                    'rate' => 1 / 13000,        // ≈ 0.00007692
                    'buy_rate' => 1 / 13130,    // ≈ 0.00007616
                    'sell_rate' => 1 / 12870,   // ≈ 0.00007770
                    'spread' => 2,
                    'updated_at' => $now,
                ]
            );
    }

    public function down(): void
    {
        $now = now();

        DB::table('exchange_rates')
            ->where('from_currency', 'USD')
            ->where('to_currency', 'SYP')
            ->update([
                'rate' => 13,
                'buy_rate' => 12.935,
                'sell_rate' => 13.065,
                'spread' => 1,
                'updated_at' => $now,
            ]);

        DB::table('exchange_rates')
            ->where('from_currency', 'SYP')
            ->where('to_currency', 'USD')
            ->update([
                'rate' => 0.000077,
                'buy_rate' => 0.000078,
                'sell_rate' => 0.000076,
                'spread' => 0.000002,
                'updated_at' => $now,
            ]);
    }
};
