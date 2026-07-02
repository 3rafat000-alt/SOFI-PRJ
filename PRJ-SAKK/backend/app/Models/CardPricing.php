<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardPricing extends Model
{
    protected $table = 'card_pricing';

    protected $fillable = [
        'brand',
        'type',
        'purchase_price',
        'monthly_fee',
        'min_load',
        'max_load',
        'load_fee_percentage',
        'load_fee_fixed',
        'transaction_fee_percentage',
        'transaction_fee_fixed',
        'atm_fee',
        'international_fee_percentage',
        'kyc_level_required',
        'is_active',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:8',
            'monthly_fee' => 'decimal:8',
            'min_load' => 'decimal:8',
            'max_load' => 'decimal:8',
            'load_fee_percentage' => 'decimal:4',
            'load_fee_fixed' => 'decimal:8',
            'transaction_fee_percentage' => 'decimal:4',
            'transaction_fee_fixed' => 'decimal:8',
            'atm_fee' => 'decimal:8',
            'international_fee_percentage' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }
}
