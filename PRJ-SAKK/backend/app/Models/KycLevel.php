<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KycLevel extends Model
{
    protected $fillable = [
        'level',
        'key',
        'name',
        'name_ar',
        'description',
        'description_ar',
        'requirements',
        'limits',
        'balance_limit',
        'cards_limit',
        'daily_limit',
        'monthly_limit',
        'single_transaction_limit',
        'withdrawal_limit',
        'can_transfer',
        'can_withdraw',
        'can_create_card',
        'is_active',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'requirements' => 'array',
            'limits' => 'array',
            'balance_limit' => 'array',
            'cards_limit' => 'integer',
            'daily_limit' => 'decimal:2',
            'monthly_limit' => 'decimal:2',
            'single_transaction_limit' => 'decimal:2',
            'withdrawal_limit' => 'decimal:2',
            'can_transfer' => 'boolean',
            'can_withdraw' => 'boolean',
            'can_create_card' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Users currently at this KYC level.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'kyc_level', 'level');
    }
}
