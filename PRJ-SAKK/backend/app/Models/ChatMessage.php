<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One message inside a live-chat Conversation.
 * sender_type: user | agent | system. sender_id references users.id
 * (customer for user, admin for agent; null for system notices).
 */
class ChatMessage extends Model
{
    protected $fillable = [
        'conversation_id', 'sender_type', 'sender_id', 'body', 'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
