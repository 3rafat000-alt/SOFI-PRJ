<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'agency_subscription_id', 'agency_id', 'amount', 'currency',
        'payment_method', 'transaction_id', 'gateway', 'status',
        'paid_at', 'notes',
    ];

    protected $casts = [
        'amount'   => 'decimal:2',
        'paid_at'  => 'datetime',
        'notes'    => 'array',
    ];

    public function agencySubscription(): BelongsTo
    {
        return $this->belongsTo(AgencySubscription::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }
}
