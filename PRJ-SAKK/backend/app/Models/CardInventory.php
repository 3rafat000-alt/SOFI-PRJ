<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardInventory extends Model
{
    protected $table = 'card_inventory';

    protected $fillable = [
        'card_number_encrypted',
        'card_number_hash',
        'cvv_encrypted',
        'expiry_month',
        'expiry_year',
        'cardholder_name',
        'brand',
        'type',
        'bin',
        'source_file',
        'purchase_price',
        'min_load',
        'max_load',
        'is_assigned',
        'assigned_to',
        'assigned_at',
    ];

    protected $guarded = [];

    protected $hidden = [
        'card_number_encrypted',
        'cvv_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:8',
            'min_load' => 'decimal:8',
            'max_load' => 'decimal:8',
            'is_assigned' => 'boolean',
            'assigned_at' => 'datetime',
        ];
    }

    public function assignedCard(): BelongsTo
    {
        return $this->belongsTo(VirtualCard::class, 'assigned_to');
    }
}
