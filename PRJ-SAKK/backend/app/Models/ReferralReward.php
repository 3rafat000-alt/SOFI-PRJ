<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralReward extends Model
{
    use HasFactory;
    protected $fillable = [
        'referrer_id',
        'referred_id',
        'transaction_id',
        'referrer_reward',
        'referred_reward',
        'currency',
        'trigger',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'referrer_reward' => 'decimal:8',
            'referred_reward' => 'decimal:8',
        ];
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_id');
    }
}
