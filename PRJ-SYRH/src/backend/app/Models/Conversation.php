<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'agency_id',
        'property_id',
        'client_name',
        'client_phone',
        'client_email',
        'inquiry_id',
        'guest_token',
        'archived_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    protected $casts = [
        'archived_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
    }

    public function scopeInbox($query)
    {
        return $query->whereNull('archived_at');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class)->latestOfMany();
    }

    public function unreadClientMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)
            ->where('sender_type', 'client')
            ->whereNull('read_at');
    }

    public function unreadAgencyMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)
            ->where('sender_type', 'agency')
            ->whereNull('read_at');
    }
}
