<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoldWallet extends Model
{
    protected $fillable = [
        'user_id',
        'balance_grams',
        'total_bought_grams',
        'total_sold_grams',
        'total_invested_usd',
        'current_value_usd',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'balance_grams' => 'decimal:4',
            'total_bought_grams' => 'decimal:4',
            'total_sold_grams' => 'decimal:4',
            'total_invested_usd' => 'decimal:2',
            'current_value_usd' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(GoldTransaction::class);
    }

    public function holdings(): HasMany
    {
        return $this->hasMany(GoldHolding::class);
    }

    /**
     * Fetch (and lazily create) the per-karat holding row under the same
     * pessimistic lock as the caller's transaction.
     */
    public function holdingForKarat(string $karat): GoldHolding
    {
        return $this->holdings()
            ->where('karat', $karat)
            ->lockForUpdate()
            ->first()
            ?? $this->holdings()->create(['karat' => $karat, 'balance_grams' => 0, 'total_invested_usd' => 0]);
    }

    /**
     * Credit grams into the given karat's holding and roll up the
     * denormalized wallet-level totals (kept for backward-compat display).
     */
    public function creditGrams(float $grams, float $usdSpent, string $karat): bool
    {
        $holding = $this->holdingForKarat($karat);
        $holding->credit($grams, $usdSpent);

        $this->balance_grams += $grams;
        $this->total_bought_grams += $grams;
        $this->total_invested_usd += $usdSpent;
        return $this->save();
    }

    /**
     * Debit grams from the given karat's holding only — the fix for the
     * karat-arbitrage bug. Fails (no writes) if that karat's holding does
     * not have enough grams, even if other karats do.
     */
    public function debitGrams(float $grams, string $karat): bool
    {
        $holding = $this->holdingForKarat($karat);
        if ((float) $holding->balance_grams < $grams) {
            return false;
        }

        $avgCost = $holding->averageCostPerGram();
        $costBasisSold = $avgCost * $grams;

        if (!$holding->debit($grams)) {
            return false;
        }

        $this->balance_grams -= $grams;
        $this->total_sold_grams += $grams;
        $this->total_invested_usd = max(0, (float) $this->total_invested_usd - $costBasisSold);
        return $this->save();
    }
}
