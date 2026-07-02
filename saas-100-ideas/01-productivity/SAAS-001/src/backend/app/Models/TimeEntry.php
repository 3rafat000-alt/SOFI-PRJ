<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeEntry extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'task_id',
        'user_id',
        'started_at',
        'ended_at',
        'duration_minutes',
        'notes',
        'is_manual',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'duration_minutes' => 'integer',
    ];

    // ─── Relationships ───────────────────────────────

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Scopes ──────────────────────────────────────

    public function scopeRunning($query)
    {
        return $query->whereNull('ended_at');
    }

    public function scopeByUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByTask($query, string $taskId)
    {
        return $query->where('task_id', $taskId);
    }

    public function scopeByDateRange($query, $from, $to)
    {
        return $query->whereBetween('started_at', [$from, $to]);
    }

    public function scopeLatestFirst($query)
    {
        return $query->orderBy('started_at', 'desc');
    }

    // ─── Accessors ───────────────────────────────────

    public function getIsRunningAttribute(): bool
    {
        return is_null($this->ended_at);
    }
}
