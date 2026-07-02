<?php

declare(strict_types=1);

namespace App\Services\Agent;

use App\Enums\AgentRunStatus;
use App\Enums\AgentType;
use App\Models\AgentRepairAction;
use App\Models\AgentRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Abstract base for all verification agents.
 *
 * Every agent:
 *  - Runs within a tracked AgentRun record
 *  - Scans items, finds anomalies, triggers repairs or escalations
 *  - Respects threshold limits for auto-repair vs escalation
 *  - Produces an immutable audit trail
 *  - Never modifies financial state directly — always through RepairAgent
 *
 * Lifecycle:
 *   createRun() → verify() → record findings → createRepairAction() or escalate()
 */
abstract class BaseVerificationAgent
{
    protected AgentCryptographicSigner $signer;
    protected AgentWebhookService $webhook;
    protected ?AgentRun $currentRun = null;

    /** Maximum drift (in absolute value, in base currency) before auto-escalation. */
    protected float $autoRepairThreshold;

    /** Maximum items to scan in one run (0 = unlimited). */
    protected int $batchLimit = 0;

    /** When true, no repair actions are written — report only. */
    protected bool $dryRun = false;

    public function __construct(AgentCryptographicSigner $signer, AgentWebhookService $webhook)
    {
        $this->signer = $signer;
        $this->webhook = $webhook;
        $this->autoRepairThreshold = (float) config('agents.auto_repair_threshold', 100_000); // SYP
    }

    /** The agent type identifier — overridden by subclasses. */
    abstract protected function agentType(): AgentType;

    /** The agent version string — overridden by subclasses. */
    abstract protected function agentVersion(): string;

    /**
     * Core verification logic — override in subclass.
     *
     * @return array{items_scanned:int, anomalies:array, repairs_triggered:int, escalations:int, summary:array, log:string, threshold_breached:bool}
     */
    abstract protected function verify(): array;

    // ==================== Public API ====================

    /**
     * Execute a full verification run.
     *
     * Creates AgentRun, runs verify(), records results, emits webhooks.
     * Catches all exceptions and marks the run as failed.
     */
    public function execute(?string $trigger = 'scheduled', $triggerable = null): AgentRun
    {
        $this->dryRun = ($trigger === 'dry-run');
        $this->currentRun = $this->createRun($trigger, $triggerable);

        try {
            $result = $this->verify();
            $this->currentRun->markCompleted(
                itemsScanned: $result['items_scanned'],
                anomaliesFound: $result['anomalies_found'],
                repairsTriggered: $result['repairs_triggered'],
                escalations: $result['escalations'],
                summary: $result['summary'],
                log: $result['log'],
                thresholdBreached: $result['threshold_breached'],
            );

            // Fire webhook for completed runs with findings
            if ($result['anomalies_found'] > 0 || $result['repairs_triggered'] > 0 || $result['escalations'] > 0) {
                $this->webhook->notifyRunCompleted($this->currentRun);
            }
        } catch (\Throwable $e) {
            Log::error("Agent[{$this->agentType()->value}]: Execution failed", [
                'run_id' => $this->currentRun->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->currentRun->markFailed($e->getMessage());
        }

        Log::info("Agent[{$this->agentType()->value}]: Run completed", [
            'run_uuid' => $this->currentRun->uuid,
            'status' => $this->currentRun->status,
            'items_scanned' => $this->currentRun->items_scanned,
            'anomalies_found' => $this->currentRun->anomalies_found,
        ]);

        return $this->currentRun;
    }

    // ==================== Run Management ====================

    protected function createRun(string $trigger, $triggerable = null): AgentRun
    {
        return AgentRun::create([
            'agent_type' => $this->agentType()->value,
            'agent_version' => $this->agentVersion(),
            'trigger' => $trigger,
            'triggerable_id' => $triggerable?->id,
            'triggerable_type' => $triggerable ? get_class($triggerable) : null,
            'status' => AgentRunStatus::RUNNING->value,
        ]);
    }

    // ==================== Repair Action Helpers ====================

    /**
     * Create a repair action, sign it if below threshold, or escalate if above.
     *
     * Returns the AgentRepairAction with its final status.
     * In dry-run mode, returns a placeholder without persisting anything.
     */
    protected function createRepairAction(
        string $actionType,
        string $actionCategory,
        $targetable,
        array $payload,
        string $reason,
        float $financialImpact = 0.0,
        ?array $targetSnapshot = null,
    ): AgentRepairAction {
        // Dry-run guard: log and return a no-op placeholder
        if ($this->dryRun) {
            $this->log("[DRY-RUN] Would create repair action: {$actionType} on " . get_class($targetable) . " #{$targetable?->id} — {$reason}");
            $placeholder = new AgentRepairAction();
            $placeholder->uuid = 'dry-run';
            $placeholder->status = \App\Enums\RepairActionStatus::PENDING_SIGNING;
            return $placeholder;
        }

        /** @var AgentRepairAction $action */
        $action = AgentRepairAction::create([
            'agent_run_id' => $this->currentRun->id,
            'agent_type' => $this->agentType()->value,
            'action_type' => $actionType,
            'action_category' => $actionCategory,
            'targetable_id' => $targetable?->id,
            'targetable_type' => $targetable ? get_class($targetable) : null,
            'target_snapshot' => $targetSnapshot,
            'payload' => $payload,
            'reason' => $reason,
            'financial_impact' => $financialImpact,
        ]);

        // Threshold check
        if (abs($financialImpact) >= $this->autoRepairThreshold) {
            $action->escalate(null, "Financial impact {$financialImpact} exceeds auto-repair threshold {$this->autoRepairThreshold}. Requires human approval.");
            $this->webhook->notifyRepairAction($action);
            return $action;
        }

        // Sign the action
        $signature = $this->signer->sign($action);
        if ($signature === null) {
            $action->escalate(null, 'Cryptographic signing failed — escalated for human review.');
            $this->webhook->notifyRepairAction($action);
            return $action;
        }

        // Emit webhook
        $this->webhook->notifyRepairAction($action);

        return $action;
    }

    /**
     * Execute a signed repair action.
     * Delegates to the appropriate handler based on action_type.
     */
    protected function executeRepairAction(AgentRepairAction $action, callable $handler): bool
    {
        // Verify signature before execution
        if (!$this->signer->verify($action)) {
            $action->markFailed('Signature verification failed before execution.');
            $action->escalate(null, 'Signature verification failed — possible tampering detected.');
            $this->webhook->notifyRepairAction($action);
            return false;
        }

        try {
            DB::beginTransaction();

            $result = $handler($action);

            $action->markExecuted(['success' => true, 'result' => $result]);

            DB::commit();

            $this->webhook->notifyRepairAction($action);

            return true;
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error("Agent[{$this->agentType()->value}]: Repair execution failed", [
                'action_uuid' => $action->uuid,
                'action_type' => $action->action_type,
                'error' => $e->getMessage(),
            ]);

            $action->markFailed($e->getMessage());
            $this->webhook->notifyRepairAction($action);

            return false;
        }
    }

    /**
     * Take a snapshot of a model's current state for rollback purposes.
     */
    protected function snapshot($model): ?array
    {
        if ($model === null) {
            return null;
        }

        return $model->toArray();
    }

    /**
     * Log a message into the current run's log buffer.
     */
    protected function log(string $message): void
    {
        if ($this->currentRun) {
            $this->currentRun->forceFill([
                'log' => ($this->currentRun->log ?? '') . '[' . now()->toIso8601String() . '] ' . $message . "\n",
            ])->saveQuietly();
        }
    }
}
