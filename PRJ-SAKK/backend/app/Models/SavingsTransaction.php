<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SavingsTransaction extends Model
{
    protected $fillable = [
        'reference',
        'savings_goal_id',
        'user_id',
        'type',
        'amount',
        'currency',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (SavingsTransaction $tx) {
            $tx->reference ??= 'SAV-' . strtoupper(Str::random(10));
        });
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(SavingsGoal::class, 'savings_goal_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'deposit' ? 'إيداع' : 'سحب';
    }
}
