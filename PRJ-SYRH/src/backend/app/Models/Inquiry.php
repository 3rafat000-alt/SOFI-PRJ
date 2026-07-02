<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inquiry extends Model
{
    protected $fillable = [
        'property_id',
        'agent_id',
        'user_id',
        'name',
        'phone',
        'email',
        'message',
        'type',
        'preferred_at',
        'offer_amount',
        'status',
    ];

    protected $casts = [
        'property_id'  => 'integer',
        'agent_id'     => 'integer',
        'user_id'      => 'integer',
        'offer_amount' => 'decimal:2',
        'preferred_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
