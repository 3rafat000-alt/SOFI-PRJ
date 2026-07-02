<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AgentType;
use App\Enums\RepairActionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $agent_run_id
 * @property AgentType|string $agent_type
 * @property string $action_type
 * @property string $action_category
 * @property int|null $targetable_id
 * @property string|null $targetable_type
 * @property array|null $target_snapshot
 * @property array $payload
 * @property string $reason
 * @property float $financial_impact
 * @property string|null $signing_key_fingerprint
 * @property string|null $signature
 * @property \Carbon\Carbon|null $signed_at
 * @property RepairActionStatus|string $status
 * @property \Carbon\Carbon|null $executed_at
 * @property array|null $execution_result
 * @property bool $escalated_to_human
 * @property int|null $escalated_to_user_id
 * @property \Carbon\Carbon|null $escalated_at
 * @property string|null $escalation_note
 * @property bool $is_rolled_back
 * @property \Carbon\Carbon|null $rolled_back_at
 * @property array|null $rollback_payload
 */
class AgentRepairAction extends Model
{
    use HasFactory;

    protected $table = 'agent_repair_actions';

    protected $fillable = [
        'agent_run_id',
        'agent_type',
        'action_type',
        'action_category',
        'targetable_id',
        'targetable_type',
        'target_snapshot',
        'payload',
        'reason',
        'financial_impact',
        'signing_key_fingerprint',
        'signature',
        'signed_at',
        'status',
        'executed_at',
        'execution_result',
        'escalated_to_human',
        'escalated_to_user_id',
        'escalated_at',
        'escalation_note',
        'is_rolled_back',
        'rolled_back_at',
        'rollback_payload',
    ];

    protected function casts(): array
    {
        return [
            'target_snapshot' => 'array',
            'payload' => 'array',
            'execution_result' => 'array',
            'rollback_payload' => 'array',
            'financial_impact' => 'decimal:8',
            'signed_at' => 'datetime',
            'executed_at' => 'datetime',
            'escalated_at' => 'datetime',
            'rolled_back_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (AgentRepairAction $action) {
            $action->uuid ??= (string) Str::uuid();
        });
    }

    // ==================== Relations ====================

    public function agentRun(): BelongsTo
    {
        return $this->belongsTo(AgentRun::class);
    }

    public function targetable(): MorphTo
    {
        return $this->morphTo();
    }

    public function escalatedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_to_user_id');
    }

    // ==================== Status Helpers ====================

    public function sign(string $fingerprint, string $signature): void
    {
        $this->forceFill([
            'status' => RepairActionStatus::SIGNED->value,
            'signing_key_fingerprint' => $fingerprint,
            'signature' => $signature,
            'signed_at' => now(),
        ])->save();
    }

    public function markExecuted(array $result): void
    {
        $this->forceFill([
            'status' => RepairActionStatus::EXECUTED->value,
            'executed_at' => now(),
            'execution_result' => $result,
        ])->save();
    }

    public function markFailed(string $error): void
    {
        $this->forceFill([
            'status' => RepairActionStatus::FAILED->value,
            'execution_result' => ['error' => $error],
        ])->save();
    }

    public function escalate(?User $toUser = null, ?string $note = null): void
    {
        $this->forceFill([
            'status' => RepairActionStatus::ESCALATED->value,
            'escalated_to_human' => true,
            'escalated_to_user_id' => $toUser?->id,
            'escalated_at' => now(),
            'escalation_note' => $note ?? 'Auto-escalated: threshold exceeded or signing failed.',
        ])->save();
    }

    public function markRolledBack(array $rollbackPayload = []): void
    {
        $this->forceFill([
            'status' => RepairActionStatus::ROLLED_BACK->value,
            'is_rolled_back' => true,
            'rolled_back_at' => now(),
            'rollback_payload' => $rollbackPayload,
        ])->save();
    }
}
