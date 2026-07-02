<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AgentRunStatus;
use App\Enums\AgentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property AgentType|string $agent_type
 * @property string $agent_version
 * @property string $trigger
 * @property int|null $triggerable_id
 * @property string|null $triggerable_type
 * @property AgentRunStatus|string $status
 * @property \Carbon\Carbon $started_at
 * @property \Carbon\Carbon|null $completed_at
 * @property int $items_scanned
 * @property int $anomalies_found
 * @property int $auto_repairs_triggered
 * @property int $escalations
 * @property array|null $summary
 * @property string|null $log
 * @property int $duration_ms
 * @property bool $threshold_breached
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AgentRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_type',
        'agent_version',
        'trigger',
        'triggerable_id',
        'triggerable_type',
        'status',
        'started_at',
        'completed_at',
        'items_scanned',
        'anomalies_found',
        'auto_repairs_triggered',
        'escalations',
        'summary',
        'log',
        'duration_ms',
        'threshold_breached',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'items_scanned' => 'integer',
            'anomalies_found' => 'integer',
            'auto_repairs_triggered' => 'integer',
            'escalations' => 'integer',
            'summary' => 'array',
            'duration_ms' => 'integer',
            'threshold_breached' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (AgentRun $run) {
            $run->uuid ??= (string) Str::uuid();
            $run->started_at ??= now();
        });
    }

    // ==================== Relations ====================

    public function triggerable(): MorphTo
    {
        return $this->morphTo();
    }

    public function repairActions(): HasMany
    {
        return $this->hasMany(AgentRepairAction::class);
    }

    // ==================== Helpers ====================

    public function markCompleted(
        int $itemsScanned = 0,
        int $anomaliesFound = 0,
        int $repairsTriggered = 0,
        int $escalations = 0,
        ?array $summary = null,
        ?string $log = null,
        bool $thresholdBreached = false,
    ): void {
        $started = $this->started_at;
        if (is_string($started)) {
            $started = \Carbon\Carbon::parse($started);
        }
        $now = now();
        $this->forceFill([
            'status' => AgentRunStatus::COMPLETED->value,
            'completed_at' => $now,
            'duration_ms' => max(0, (int) ($started ? $started->diffInMilliseconds($now) : 0)),
            'items_scanned' => $itemsScanned,
            'anomalies_found' => $anomaliesFound,
            'auto_repairs_triggered' => $repairsTriggered,
            'escalations' => $escalations,
            'summary' => $summary,
            'log' => $log,
            'threshold_breached' => $thresholdBreached,
        ])->save();
    }

    public function markFailed(string $error, ?string $log = null): void
    {
        $started = $this->started_at;
        if (is_string($started)) {
            $started = \Carbon\Carbon::parse($started);
        }
        $now = now();
        $this->forceFill([
            'status' => AgentRunStatus::FAILED->value,
            'completed_at' => $now,
            'duration_ms' => max(0, (int) ($started ? $started->diffInMilliseconds($now) : 0)),
            'log' => ($this->log ?? '') . "\n[FAILED] {$error}\n" . ($log ?? ''),
        ])->save();
    }

    public function markSkipped(string $reason): void
    {
        $this->forceFill([
            'status' => AgentRunStatus::SKIPPED->value,
            'completed_at' => now(),
            'duration_ms' => 0,
            'log' => "[SKIPPED] {$reason}",
        ])->save();
    }
}
