<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Webhook extends Model
{
    use HasUuids, SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'workspace_id',
        'url',
        'events',
        'secret',
        'is_active',
        'last_sent_at',
        'last_status_code',
        'last_response',
    ];

    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
        'last_sent_at' => 'datetime',
        'last_status_code' => 'integer',
    ];

    protected $hidden = [
        'secret',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByWorkspace($query, string $workspaceId)
    {
        return $query->where('workspace_id', $workspaceId);
    }

    public function scopeByEvent($query, string $event)
    {
        return $query->whereJsonContains('events', $event);
    }
}
