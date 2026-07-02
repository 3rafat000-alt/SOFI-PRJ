<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Wallet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'company_id',
        'currency',
        'balance',
        'available_balance',
        'pending_balance',
        'daily_limit',
        'monthly_limit',
        'is_active',
        'is_default',
        'is_frozen',
        'frozen_reason',
        'network',
        'deposit_address',
    ];

    // 🔒 SEC-002: أُزيل $guarded=[] — قائمة $fillable أعلاه هي المرجع المسموح به

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:8',
            'available_balance' => 'decimal:8',
            'pending_balance' => 'decimal:8',
            'daily_limit' => 'decimal:2',
            'monthly_limit' => 'decimal:2',
            'daily_spent' => 'decimal:2',
            'monthly_spent' => 'decimal:2',
            'total_deposits' => 'decimal:8',
            'total_withdrawals' => 'decimal:8',
            'total_sent' => 'decimal:8',
            'total_received' => 'decimal:8',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'is_frozen' => 'boolean',
            'daily_reset_at' => 'date',
            'monthly_reset_at' => 'date',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Wallet $wallet) {
            $wallet->uuid = Str::uuid();
            $wallet->available_balance ??= $wallet->balance ?? 0;

            // Exactly-one-owner invariant: a wallet belongs to a user OR a
            // company, never both, never neither. (DB has a partial unique on
            // company wallets where the engine supports it; this guards the rest.)
            $hasUser = !empty($wallet->user_id);
            $hasCompany = !empty($wallet->company_id);
            if ($hasUser === $hasCompany) {
                throw new \RuntimeException('Wallet must have exactly one owner (user or company).');
            }

            // No crypto currencies supported
        });
    }

    // ==================== Relationships ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** The owning model — a User or a Company. */
    public function owner(): ?Model
    {
        return $this->company_id ? $this->company : $this->user;
    }

    public function cards(): HasMany
    {
        return $this->hasMany(VirtualCard::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // ==================== Balance Methods ====================

    public function credit(float $amount, string $description = ''): bool
    {
        if ($amount <= 0) {
            return false;
        }

        $this->balance += $amount;
        $this->available_balance += $amount;
        $this->total_received += $amount;
        $this->transaction_count++;

        return $this->save();
    }

    public function debit(float $amount, string $description = ''): bool
    {
        if ($amount <= 0 || $this->is_frozen) {
            return false;
        }

        if ($this->available_balance < $amount) {
            return false;
        }

        $this->balance -= $amount;
        $this->available_balance -= $amount;
        $this->total_sent += $amount;
        $this->daily_spent += $amount;
        $this->monthly_spent += $amount;
        $this->transaction_count++;

        return $this->save();
    }

    public function hold(float $amount): bool
    {
        if ($amount <= 0 || $this->available_balance < $amount) {
            return false;
        }

        $this->pending_balance += $amount;
        $this->available_balance -= $amount;

        return $this->save();
    }

    public function release(float $amount): bool
    {
        if ($amount <= 0 || $this->pending_balance < $amount) {
            return false;
        }

        $this->pending_balance -= $amount;
        $this->available_balance += $amount;

        return $this->save();
    }

    public function capture(float $amount): bool
    {
        if ($amount <= 0 || $this->pending_balance < $amount) {
            return false;
        }

        $this->pending_balance -= $amount;
        $this->available_balance += $amount;
        $this->balance -= $amount;
        $this->daily_spent += $amount;
        $this->monthly_spent += $amount;

        return $this->save();
    }

    // ==================== Limit Methods ====================

    public function canSpend(float $amount): bool
    {
        $this->resetLimitsIfNeeded();

        if ($this->is_frozen || !$this->is_active) {
            return false;
        }

        if ($this->available_balance < $amount) {
            return false;
        }

        if ($this->daily_spent + $amount > $this->daily_limit) {
            return false;
        }

        if ($this->monthly_spent + $amount > $this->monthly_limit) {
            return false;
        }

        return true;
    }

    public function resetLimitsIfNeeded(): void
    {
        $today = now()->toDateString();
        $thisMonth = now()->startOfMonth()->toDateString();
        $dirty = false;

        if ($this->daily_reset_at?->toDateString() !== $today) {
            $this->daily_spent = 0;
            $this->daily_reset_at = $today;
            $dirty = true;
        }

        if ($this->monthly_reset_at?->toDateString() !== $thisMonth) {
            $this->monthly_spent = 0;
            $this->monthly_reset_at = $thisMonth;
            $dirty = true;
        }

        if ($dirty) {
            $this->saveQuietly();
        }
    }

    // ==================== Accessors ====================

    public function getFormattedBalanceAttribute(): string
    {
        return \App\Support\Money::format((float) $this->balance, $this->currency);
    }

    public function getIsCryptoAttribute(): bool
    {
        return false;
    }
}
