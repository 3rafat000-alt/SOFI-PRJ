<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A live-chat thread between a customer and a support agent.
 * Standalone — unrelated to the SupportTicket desk.
 */
class Conversation extends Model
{
    protected $fillable = [
        'user_id', 'agent_id', 'status', 'subject', 'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    /** Newest message — eager-load in the inbox to avoid an N+1 per row. */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class)->latestOfMany();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /** Unread = customer messages the agent has not read yet. */
    public function unreadForAgent(): int
    {
        return $this->messages()->where('sender_type', 'user')->whereNull('read_at')->count();
    }
}
