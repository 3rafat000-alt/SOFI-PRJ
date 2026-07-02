<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per-karat gold holdings — fixes the karat-arbitrage bug where
 * GoldWallet.balance_grams was karat-blind (buy 18k, sell declaring 24k
 * at the higher sell price = risk-free profit). Sell must be scoped to
 * the karat actually held.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gold_holdings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gold_wallet_id')->constrained()->cascadeOnDelete();
            $table->string('karat', 10);
            $table->decimal('balance_grams', 12, 4)->default(0);
            $table->decimal('total_invested_usd', 14, 2)->default(0);
            $table->timestamps();

            $table->unique(['gold_wallet_id', 'karat']);
        });

        // Backfill from existing gold_transactions (data-safe; no-op on empty table).
        // Replays buy/sell history per (gold_wallet_id, karat) in chronological order,
        // using average-cost basis identical to the app-level logic, so historic
        // holdings land in a consistent state instead of starting at zero while
        // GoldWallet.balance_grams already reflects the old karat-blind total.
        $transactions = DB::table('gold_transactions')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['gold_wallet_id', 'karat', 'type', 'grams', 'total_usd']);

        $holdings = [];
        foreach ($transactions as $tx) {
            $key = $tx->gold_wallet_id . '|' . $tx->karat;
            if (!isset($holdings[$key])) {
                $holdings[$key] = ['gold_wallet_id' => $tx->gold_wallet_id, 'karat' => $tx->karat, 'grams' => 0.0, 'invested' => 0.0];
            }

            if ($tx->type === 'buy') {
                $holdings[$key]['grams'] += (float) $tx->grams;
                $holdings[$key]['invested'] += (float) $tx->total_usd;
            } elseif ($tx->type === 'sell') {
                $priorGrams = $holdings[$key]['grams'];
                $avgCost = $priorGrams > 0 ? $holdings[$key]['invested'] / $priorGrams : 0.0;
                $soldGrams = min((float) $tx->grams, $priorGrams);
                $holdings[$key]['grams'] -= $soldGrams;
                $holdings[$key]['invested'] -= $avgCost * $soldGrams;
                if ($holdings[$key]['invested'] < 0) {
                    $holdings[$key]['invested'] = 0.0;
                }
            }
        }

        $now = now();
        $rows = [];
        foreach ($holdings as $h) {
            $rows[] = [
                'gold_wallet_id' => $h['gold_wallet_id'],
                'karat' => $h['karat'],
                'balance_grams' => round($h['grams'], 4),
                'total_invested_usd' => round($h['invested'], 2),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('gold_holdings')->insert($chunk);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gold_holdings');
    }
};
