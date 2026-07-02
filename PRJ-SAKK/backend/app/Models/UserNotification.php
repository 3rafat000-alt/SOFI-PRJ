<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotification extends Model
{
    use HasFactory;
    protected $fillable = [
        'uuid',
        'user_id',
        'template_code',
        'channel',
        'title',
        'body',
        'data',
        'action_url',
        'is_read',
        'read_at',
        'sent_at',
        'status',
        'failure_reason',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'is_read' => 'boolean',
            'read_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
                'status' => 'read',
            ]);
        }
    }
}
