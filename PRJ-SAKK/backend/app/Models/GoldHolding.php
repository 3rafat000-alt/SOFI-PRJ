<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-karat gold holding — the karat-scoped ledger row that backs
 * GoldWallet.balance_grams. A user's 18k grams and 24k grams are
 * tracked and cost-based separately so sell cannot arbitrage across
 * karats (buy cheap 18k, sell declaring 24k at the higher price).
 */
class GoldHolding extends Model
{
    protected $fillable = [
        'gold_wallet_id',
        'karat',
        'balance_grams',
        'total_invested_usd',
    ];

    protected function casts(): array
    {
        return [
            'balance_grams' => 'decimal:4',
            'total_invested_usd' => 'decimal:2',
        ];
    }

    public function goldWallet(): BelongsTo
    {
        return $this->belongsTo(GoldWallet::class);
    }

    /**
     * Average cost basis per gram for this holding (0 if empty).
     */
    public function averageCostPerGram(): float
    {
        $grams = (float) $this->balance_grams;
        return $grams > 0 ? (float) $this->total_invested_usd / $grams : 0.0;
    }

    public function credit(float $grams, float $usdSpent): bool
    {
        $this->balance_grams += $grams;
        $this->total_invested_usd += $usdSpent;
        return $this->save();
    }

    /**
     * Debit grams from this karat holding, reducing invested cost by the
     * average-cost basis of the grams sold. Returns false if insufficient
     * grams held at this karat (no partial/cross-karat debit).
     */
    public function debit(float $grams): bool
    {
        if ((float) $this->balance_grams < $grams) {
            return false;
        }

        $avgCost = $this->averageCostPerGram();
        $this->balance_grams -= $grams;
        $this->total_invested_usd = max(0, (float) $this->total_invested_usd - ($avgCost * $grams));

        return $this->save();
    }
}
