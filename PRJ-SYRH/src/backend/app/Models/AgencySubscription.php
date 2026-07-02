<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgencySubscription extends Model
{
    protected $fillable = [
        'agency_id', 'plan_id', 'start_at', 'end_at', 'status',
        'trial_ends_at', 'cancelled_at', 'payment_method',
    ];

    protected $casts = [
        'start_at'      => 'datetime',
        'end_at'        => 'datetime',
        'trial_ends_at' => 'datetime',
        'cancelled_at'  => 'datetime',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->end_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || $this->end_at->isPast();
    }
}
