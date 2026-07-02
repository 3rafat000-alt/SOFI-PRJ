<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class GoldTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'gold_wallet_id',
        'type',
        'karat',
        'grams',
        'price_per_gram_usd',
        'total_usd',
        'fee_usd',
        'usd_rate_at_time',
        'reference',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'grams' => 'decimal:4',
            'price_per_gram_usd' => 'decimal:2',
            'total_usd' => 'decimal:2',
            'fee_usd' => 'decimal:2',
            'usd_rate_at_time' => 'decimal:2',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (GoldTransaction $tx) {
            $tx->reference ??= 'GLD-' . strtoupper(Str::random(14));
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function goldWallet(): BelongsTo
    {
        return $this->belongsTo(GoldWallet::class);
    }
}
