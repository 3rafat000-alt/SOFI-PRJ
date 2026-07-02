<?php

namespace App\Models;

use App\Enums\CardBrand;
use App\Enums\CardStatus;
use App\Enums\CardType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class VirtualCard extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'wallet_id',
        'card_type',
        'brand',
        'cardholder_name',
        // 🔒 SEC-003: 'balance' intentionally NOT fillable — prevents arbitrary
        // balance manipulation via mass assignment. Balance is mutated only
        // through dedicated methods (loadFunds, unload, spend, refund).
        'spending_limit',
        'daily_limit',
        'monthly_limit',
        'per_transaction_limit',
        // 🔒 SEC-003: 'status' and 'is_active' intentionally NOT fillable —
        // managed via freeze/unfreeze/cancel service methods only.
        'online_enabled',
        'international_enabled',
        'contactless_enabled',
        'atm_enabled',
        'apple_pay_enabled',
        'google_pay_enabled',
        'nickname',
        'color',
        'provider',
        'provider_card_id',
        'provider_data',
    ];

    // 🔒 SEC-002: أُزيل $guarded=[] — قائمة $fillable أعلاه هي المرجع المسموح به

    protected $hidden = [
        'card_number',
        'cvv',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'spending_limit' => 'decimal:2',
            'daily_limit' => 'decimal:2',
            'monthly_limit' => 'decimal:2',
            'per_transaction_limit' => 'decimal:2',
            'daily_spent' => 'decimal:2',
            'monthly_spent' => 'decimal:2',
            'total_spent' => 'decimal:2',
            'is_active' => 'boolean',
            'online_enabled' => 'boolean',
            'international_enabled' => 'boolean',
            'contactless_enabled' => 'boolean',
            'atm_enabled' => 'boolean',
            'apple_pay_enabled' => 'boolean',
            'google_pay_enabled' => 'boolean',
            'samsung_pay_enabled' => 'boolean',
            'activated_at' => 'datetime',
            'expires_at' => 'datetime',
            'daily_reset_at' => 'date',
            'monthly_reset_at' => 'date',
            'provider_data' => 'array',
            'card_type' => CardType::class,
            'brand' => CardBrand::class,
            'status' => CardStatus::class,
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (VirtualCard $card) {
            if (empty($card->uuid)) {
                $card->uuid = Str::uuid();
            }

            $isProviderCard = !empty($card->provider_card_id);

            if ($isProviderCard) {
                // Provider (e.g. Stripe) owns the real PAN/CVV. Store only
                // non-sensitive placeholders locally to satisfy NOT NULL
                // columns, and keep any masked/expiry the provider supplied.
                if (empty($card->card_number)) {
                    $card->card_number = 'ext_' . substr((string) $card->provider_card_id, -12);
                }
                if (empty($card->card_number_masked)) {
                    $card->card_number_masked = '**** **** **** ****';
                }
                if (empty($card->cvv)) {
                    $card->cvv = '***';
                }
            } elseif (empty($card->card_number)) {
                // Local card — generate a valid 16-digit Luhn card number + CVV.
                // 6-digit BIN + 9 random digits = 15, plus 1 Luhn check = 16.
                $prefix = $card->brand === CardBrand::VISA ? '4' : '5';
                $card->bin = $prefix . str_pad((string) rand(10000, 99999), 5, '0', STR_PAD_LEFT);
                $cardNumber = $card->bin . str_pad((string) rand(0, 999999999), 9, '0', STR_PAD_LEFT);

                $card->card_number = $cardNumber . self::calculateLuhnCheckDigit($cardNumber);
                $card->card_number_masked = '**** **** **** ' . substr($card->card_number, -4);
                $card->cvv = str_pad((string) rand(100, 999), 3, '0', STR_PAD_LEFT);
            }

            // Expiry — keep provider's value if already set, else default 3 years.
            if (empty($card->expiry_month) || empty($card->expiry_year)) {
                $expiry = now()->addYears(3);
                $card->expiry_month = $expiry->format('m');
                $card->expiry_year = $expiry->format('Y');
                $card->expires_at = $expiry->endOfMonth();
            }

            if (empty($card->status)) {
                $card->status = CardStatus::ACTIVE;
            }
            if (empty($card->activated_at)) {
                $card->activated_at = now();
            }
        });
    }

    private static function calculateLuhnCheckDigit(string $number): string
    {
        $sum = 0;
        $length = strlen($number);
        
        for ($i = 0; $i < $length; $i++) {
            $digit = (int) $number[$length - 1 - $i];
            if ($i % 2 === 0) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
        }
        
        return (string) ((10 - ($sum % 10)) % 10);
    }

    // ==================== Relationships ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeWhereUserId($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'card_id');
    }

    // ==================== Card Methods ====================

    public function freeze(?string $reason = null): bool
    {
        $this->status = CardStatus::FROZEN;
        $this->is_active = false;
        $this->frozen_reason = $reason;
        return $this->save();
    }

    public function unfreeze(): bool
    {
        if ($this->status !== CardStatus::FROZEN) {
            return false;
        }

        $this->status = CardStatus::ACTIVE;
        $this->is_active = true;
        $this->frozen_reason = null;
        return $this->save();
    }

    public function cancel(): bool
    {
        $this->status = CardStatus::CANCELLED;
        $this->is_active = false;
        return $this->save();
    }

    public function loadFunds(float $amount): bool
    {
        if ($amount <= 0 || !$this->is_active) {
            return false;
        }

        // Debit from wallet
        if (!$this->wallet->debit($amount, "Load card {$this->card_number_masked}")) {
            return false;
        }

        $this->balance += $amount;
        return $this->save();
    }

    public function unload(float $amount): bool
    {
        if ($amount <= 0 || $this->balance < $amount) {
            return false;
        }

        // Credit to wallet
        if (!$this->wallet->credit($amount, "Unload card {$this->card_number_masked}")) {
            return false;
        }

        $this->balance -= $amount;
        return $this->save();
    }

    public function canSpend(float $amount): bool
    {
        $this->resetLimitsIfNeeded();

        if (!$this->is_active || $this->status !== CardStatus::ACTIVE) {
            return false;
        }

        if ($this->balance < $amount) {
            return false;
        }

        if ($amount > $this->per_transaction_limit) {
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

    public function spend(float $amount): bool
    {
        if (!$this->canSpend($amount)) {
            return false;
        }

        $this->balance -= $amount;
        $this->daily_spent += $amount;
        $this->monthly_spent += $amount;
        $this->total_spent += $amount;
        
        return $this->save();
    }

    public function refund(float $amount): bool
    {
        if ($amount <= 0) {
            return false;
        }

        $this->balance += $amount;
        $this->total_spent -= $amount;

        return $this->save();
    }

    private function resetLimitsIfNeeded(): void
    {
        $today = now()->toDateString();
        $thisMonth = now()->startOfMonth()->toDateString();

        if ($this->daily_reset_at?->toDateString() !== $today) {
            $this->daily_spent = 0;
            $this->daily_reset_at = $today;
        }

        if ($this->monthly_reset_at?->toDateString() !== $thisMonth) {
            $this->monthly_spent = 0;
            $this->monthly_reset_at = $thisMonth;
        }

        $this->saveQuietly();
    }

    // ==================== Accessors ====================

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getExpiryAttribute(): string
    {
        return "{$this->expiry_month}/{$this->expiry_year}";
    }

    public function getDecryptedCardNumberAttribute(): string
    {
        // In production, decrypt from encrypted storage
        return $this->card_number;
    }

    public function getDecryptedCvvAttribute(): string
    {
        // In production, decrypt from encrypted storage
        return $this->cvv;
    }

    public function getFormattedBalanceAttribute(): string
    {
        return \App\Support\Money::format((float) $this->balance, 'USD');
    }
}
