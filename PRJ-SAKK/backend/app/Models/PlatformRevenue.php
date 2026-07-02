<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One platform income event (exchange spread, fees, …) — the treasury ledger.
 * Wallet balances are not affected; this is revenue recognition only.
 */
class PlatformRevenue extends Model
{
    public const SOURCE_EXCHANGE_SPREAD = 'exchange_spread';
    public const SOURCE_WITHDRAW_FEE = 'withdraw_fee';
    public const SOURCE_CARD_FEE = 'card_fee';
    public const SOURCE_DEPOSIT_FEE = 'deposit_fee';

    protected $fillable = [
        'source',
        'currency',
        'amount',
        'transaction_id',
        'user_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8',
            'metadata' => 'array',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
