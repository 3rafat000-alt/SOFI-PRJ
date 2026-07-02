<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationLog extends Model
{
    protected $fillable = [
        'integration_id', 'level', 'action', 'message',
        'payload', 'response', 'status_code', 'ip_address', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'response' => 'array',
        ];
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function getLevelColor(): string
    {
        return match($this->level) {
            'success' => 'green',
            'error' => 'red',
            'warning' => 'yellow',
            default => 'blue',
        };
    }
}
