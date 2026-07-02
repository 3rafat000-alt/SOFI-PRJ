<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SavingsGoal extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'target_amount',
        'saved_amount',
        'currency',
        'status',
        'icon',
        'color',
        'target_date',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'saved_amount' => 'decimal:2',
            'target_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (SavingsGoal $goal) {
            $goal->uuid ??= Str::uuid();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SavingsTransaction::class);
    }

    // ==================== Balance Methods ====================

    public function deposit(float $amount): bool
    {
        // 🔒 Lock row to prevent TOCTOU on saved_amount
        return DB::transaction(function () use ($amount) {
            $locked = static::lockForUpdate()->find($this->id);
            if (!$locked) return false;

            $locked->saved_amount += $amount;
            $locked->refreshCompletion();
            return $locked->save();
        });
    }

    public function withdraw(float $amount): bool
    {
        // 🔒 Lock row to prevent TOCTOU on saved_amount
        return DB::transaction(function () use ($amount) {
            $locked = static::lockForUpdate()->find($this->id);
            if (!$locked) return false;

            if ($locked->saved_amount < $amount) return false;

            $locked->saved_amount -= $amount;
            $locked->refreshCompletion();
            return $locked->save();
        });
    }

    /// Flag the goal completed when the target is reached.
    protected function refreshCompletion(): void
    {
        if ($this->target_amount && $this->saved_amount >= $this->target_amount) {
            if ($this->status === 'active') {
                $this->status = 'completed';
                $this->completed_at = now();
            }
        } elseif ($this->status === 'completed') {
            // Dropped below target again (after a withdrawal).
            $this->status = 'active';
            $this->completed_at = null;
        }
    }

    // ==================== Accessors ====================

    public function getProgressPercentAttribute(): float
    {
        if (!$this->target_amount || $this->target_amount <= 0) {
            return 0;
        }
        return min(100, round(($this->saved_amount / $this->target_amount) * 100, 1));
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => 'نشط',
            'completed' => 'مكتمل',
            'closed' => 'مغلق',
            default => $this->status,
        };
    }
}
