<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_type',
        'sender_id',
        'message',
        'message_type',
        'metadata',
        'attachment_path',
        'attachment_type',
        'attachment_name',
        'attachment_size',
        'attachments',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'attachment_size' => 'integer',
        'attachments' => 'array',
        'metadata' => 'array',
    ];

    protected $appends = ['attachment_url', 'attachments_url'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment_path ? '/storage/' . $this->attachment_path : null;
    }

    public function getAttachmentsUrlAttribute(): ?array
    {
        if (!$this->attachments || !is_array($this->attachments)) {
            return null;
        }

        return array_map(fn ($att) => array_merge($att, [
            'url' => '/storage/' . $att['path'],
        ]), $this->attachments);
    }
}
