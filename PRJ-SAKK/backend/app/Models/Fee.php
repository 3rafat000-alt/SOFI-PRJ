<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Fee Model - Dynamic fee management for SAKK Wallet
 * 
 * @property int $id
 * @property string $code
 * @property string $name_ar
 * @property string|null $name_en
 * @property string|null $description
 * @property string $type
 * @property string $currency
 * @property string|null $payment_method
 * @property float $fixed_amount
 * @property float $percentage
 * @property float $min_fee
 * @property float|null $max_fee
 * @property float $min_amount
 * @property float|null $max_amount
 * @property bool $is_active
 * @property int $sort_order
 * @property array|null $metadata
 */
class Fee extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'description',
        'type',
        'currency',
        'payment_method',
        'fixed_amount',
        'percentage',
        'min_fee',
        'max_fee',
        'min_amount',
        'max_amount',
        'is_active',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'fixed_amount' => 'decimal:6',
        'percentage' => 'decimal:4',
        'min_fee' => 'decimal:6',
        'max_fee' => 'decimal:6',
        'min_amount' => 'decimal:6',
        'max_amount' => 'decimal:6',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    // ==========================================
    // Fee Type Constants
    // ==========================================
    
    public const TYPE_DEPOSIT = 'deposit';
    public const TYPE_WITHDRAWAL = 'withdrawal';
    public const TYPE_CARD_FUND = 'card_fund';
    public const TYPE_TRANSFER = 'transfer';
    public const TYPE_P2P = 'p2p';
    public const TYPE_GOLD = 'exchange';
    public const TYPE_PARTNER = 'partner';

    // ==========================================
    // Fee Codes (for easy reference)
    // ==========================================
    
    // Deposits
    public const CODE_DEPOSIT_USDT = 'deposit_usdt';
    
    // Withdrawals
    public const CODE_WITHDRAW_USDT = 'withdraw_usdt';
    
    // Card
    public const CODE_CARD_FUND = 'card_fund';
    public const CODE_CARD_CREATION = 'card_creation';
    public const CODE_CARD_UNLOAD = 'card_unload';

    // Gold
    public const CODE_GOLD_BUY = 'gold_buy';
    public const CODE_GOLD_SELL = 'gold_sell';

    // P2P
    public const CODE_P2P_TRANSFER = 'p2p_transfer';

    // Partner
    public const CODE_PARTNER_FEE = 'partner_fee';
    public const CODE_AGENT_FEE = 'agent_fee';
    public const CODE_MERCHANT_FEE = 'merchant_fee';
    public const CODE_COMPANY_FEE = 'company_fee';

    // ==========================================
    // Scopes
    // ==========================================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeByCurrency(Builder $query, string $currency): Builder
    {
        return $query->where('currency', $currency);
    }

    public function scopeByCode(Builder $query, string $code): Builder
    {
        return $query->where('code', $code);
    }

    public function scopeByPaymentMethod(Builder $query, string $method): Builder
    {
        return $query->where('payment_method', $method);
    }

    // ==========================================
    // Fee Calculation
    // ==========================================

    /**
     * Calculate the fee for a given amount
     * 
     * Formula: max(min_fee, min(max_fee, fixed_amount + (amount * percentage / 100)))
     */
    public function calculateFee(float $amount): float
    {
        // Calculate base fee
        $calculatedFee = $this->fixed_amount + ($amount * $this->percentage / 100);
        
        // Apply minimum
        $calculatedFee = max($this->min_fee, $calculatedFee);
        
        // Apply maximum (if set)
        if ($this->max_fee !== null && $this->max_fee > 0) {
            $calculatedFee = min($this->max_fee, $calculatedFee);
        }
        
        return round($calculatedFee, 6);
    }

    /**
     * Check if amount is within allowed limits
     */
    public function isAmountAllowed(float $amount): bool
    {
        if ($amount < $this->min_amount) {
            return false;
        }
        
        if ($this->max_amount !== null && $amount > $this->max_amount) {
            return false;
        }
        
        return true;
    }

    /**
     * Get fee breakdown for display
     */
    public function getFeeBreakdown(float $amount): array
    {
        $fee = $this->calculateFee($amount);
        $netAmount = $amount - $fee;
        
        return [
            'gross_amount' => $amount,
            'fee' => $fee,
            'net_amount' => $netAmount,
            'fee_details' => [
                'fixed' => $this->fixed_amount,
                'percentage' => $this->percentage,
                'percentage_amount' => $amount * $this->percentage / 100,
            ],
            'currency' => $this->currency,
            'fee_name' => $this->name_ar,
            'fee_code' => $this->code,
        ];
    }

    // ==========================================
    // Static Helpers
    // ==========================================

    /**
     * Get fee by code (cached)
     */
    public static function getByCode(string $code): ?self
    {
        // Cache raw attributes, NOT the Eloquent model: with CACHE_STORE=database the model is
        // serialized and read back as __PHP_Incomplete_Class, which violates the ?self return type
        // and 500s. Rehydrate a real Fee from the cached attribute array instead.
        $attrs = cache()->remember(
            "fee:{$code}",
            now()->addMinutes(10),
            fn() => optional(self::active()->byCode($code)->first())->getAttributes()
        );

        return $attrs ? (new self())->setRawAttributes($attrs, true) : null;
    }

    /**
     * Clear fee cache
     */
    public static function clearCache(?string $code = null): void
    {
        if ($code) {
            cache()->forget("fee:{$code}");
        } else {
            // Clear all fee caches
            $fees = self::all();
            foreach ($fees as $fee) {
                cache()->forget("fee:{$fee->code}");
            }
        }
    }

    // ==========================================
    // Boot
    // ==========================================

    protected static function boot()
    {
        parent::boot();

        // Clear cache on update
        static::saved(function (Fee $fee) {
            self::clearCache($fee->code);
        });

        static::deleted(function (Fee $fee) {
            self::clearCache($fee->code);
        });
    }
}
