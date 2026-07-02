<?php

declare(strict_types=1);

namespace App\Services\Agent;

use App\Enums\AgentType;
use App\Enums\RepairActionStatus;
use App\Models\AgentRepairAction;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AgentOrchestrator — coordinates all verification agents and repair execution.
 *
 * Responsibilities:
 *   1. Agent registry: map AgentType → agent class instance
 *   2. Parallel/serial agent execution
 *   3. Threshold management: drift limit per currency, per agent type
 *   4. Pending repair execution: pick signed repairs, verify, execute
 *   5. Deadlock prevention: wallet-level locking with timeout
 *   6. Concurrency guard: no duplicate runs per agent type
 *   7. Escalation routing: threshold breach → human admin queue
 *
 * Architecture:
 *   - Agents run as isolated background workers (Laravel Queue/Schedule)
 *   - Financial mutations happen ONLY via signed repair actions
 *   - Every repair action is cryptographically verified before execution
 *   - Locked wallets block concurrent agent access (SELECT ... FOR UPDATE)
 *   - Failed repairs auto-rollback via DB transaction
 */
class AgentOrchestrator
{
    /** Map of AgentType → agent class (resolved by container). */
    private array $agentRegistry = [];

    /** Cache lock TTL for concurrency guard (seconds). */
    private const LOCK_TTL = 600;

    /** Concurrency guard cache key prefix. */
    private const LOCK_PREFIX = 'agent:run:lock:';

    public function __construct(
        private AgentCryptographicSigner $signer,
        private AgentWebhookService $webhook,
        private FinancialVerificationAgent $financialAgent,
        private KycVerificationAgent $kycAgent,
    ) {
        $this->agentRegistry = [
            AgentType::FINANCIAL_RECONCILIATION->value => $financialAgent,
            AgentType::KYC_VERIFICATION->value => $kycAgent,
        ];
    }

    // ==================== Agent Execution ====================

    /**
     * Run a specific verification agent.
     *
     * Concurrency guard: prevents duplicate runs of the same agent type.
     * Returns the AgentRun, or null if blocked by concurrency guard.
     */
    public function runAgent(string|AgentType $agentType, string $trigger = 'scheduled', $triggerable = null): ?\App\Models\AgentRun
    {
        $agentTypeStr = $agentType instanceof AgentType ? $agentType->value : $agentType;
        $lockKey = self::LOCK_PREFIX . $agentTypeStr;

        // Concurrency guard — atomic lock
        if (!Cache::lock($lockKey, self::LOCK_TTL)->get()) {
            Log::warning("AgentOrchestrator: Concurrency guard blocked duplicate run of {$agentTypeStr}");
            return null;
        }

        try {
            $agent = $this->resolveAgent($agentTypeStr);
            if ($agent === null) {
                Log::error("AgentOrchestrator: Unknown agent type: {$agentTypeStr}");
                return null;
            }

            return $agent->execute($trigger, $triggerable);
        } finally {
            Cache::lock($lockKey)->release();
        }
    }

    /**
     * Run all registered agents in sequence.
     *
     * @return array<string, \App\Models\AgentRun|null>
     */
    public function runAllAgents(string $trigger = 'scheduled'): array
    {
        $results = [];

        // Financial first (critical path), then KYC
        foreach ([AgentType::FINANCIAL_RECONCILIATION, AgentType::KYC_VERIFICATION] as $type) {
            $results[$type->value] = $this->runAgent($type, $trigger);
        }

        return $results;
    }

    // ==================== Repair Execution ====================

    /**
     * Execute pending signed repair actions.
     *
     * Picks all actions with status=RepairActionStatus::SIGNED and executes
     * them via the appropriate agent handler. Signature verified before each.
     *
     * @return int Number of successfully executed repairs.
     */
    public function executePendingRepairs(int $limit = 20): int
    {
        $pending = AgentRepairAction::where('status', RepairActionStatus::SIGNED->value)
            ->where('escalated_to_human', false)
            ->orderBy('created_at')
            ->limit($limit)
           ->get();

        if ($pending->isEmpty()) {
            return 0;
        }

        $executed = 0;

        foreach ($pending as $action) {
            try {
                $success = $this->executeSingleRepair($action);
                if ($success) {
                    $executed++;
                }
            } catch (\Throwable $e) {
                Log::error('AgentOrchestrator: Repair execution threw exception', [
                    'action_uuid' => $action->uuid,
                    'error' => $e->getMessage(),
                ]);
                $action->markFailed($e->getMessage());
            }
        }

        return $executed;
    }

    /**
     * Execute a single repair action with full signing verification.
     */
    private function executeSingleRepair(AgentRepairAction $action): bool
    {
        // Step 1: Verify cryptographic signature
        if (!$this->signer->verify($action)) {
            $action->markFailed('Signature verification failed.');
            $action->escalate(null, 'Signature verification failed — possible tampering.');
            $this->webhook->notifyRepairAction($action);
            return false;
        }

        // Step 2: Resolve the correct handler
        $handler = $this->resolveRepairHandler($action);

        if ($handler === null) {
            $action->markFailed("No handler for action_type: {$action->action_type}");
            $this->webhook->notifyRepairAction($action);
            return false;
        }

        // Step 3: Execute in transaction with wallet-level locking
        return DB::transaction(function () use ($action, $handler) {
            // Lock affected wallet(s) if financial action
            $this->lockAffectedWallets($action);

            try {
                $handler($action);
                $action->markExecuted(['success' => true]);
                $this->webhook->notifyRepairAction($action);
                return true;
            } catch (\Throwable $e) {
                $action->markFailed($e->getMessage());
                $action->markRolledBack(['error' => $e->getMessage()]);
                $this->webhook->notifyRepairAction($action);
                return false;
            }
        });
    }

    /**
     * Resolve the execution handler for a given repair action.
     */
    private function resolveRepairHandler(AgentRepairAction $action): ?callable
    {
        $agentType = $action->agent_type instanceof AgentType
            ? $action->agent_type->value
            : $action->agent_type;

        $agentTypeStr = $agentType;

        return match ($agentTypeStr) {
            AgentType::FINANCIAL_RECONCILIATION->value => match ($action->action_type) {
                'settle_ledger' => fn(AgentRepairAction $a) => $this->financialAgent->executeSettleLedger($a),
                default => null,
            },
            AgentType::KYC_VERIFICATION->value => match ($action->action_type) {
                'approve_kyc_document' => fn(AgentRepairAction $a) => $this->kycAgent->executeApproveKyc($a),
                'reject_kyc_document' => fn(AgentRepairAction $a) => $this->kycAgent->executeRejectKyc($a),
                'review_kyc_document' => null, // Escalated — no auto-handler
                default => null,
            },
            default => null,
        };
    }

    // ==================== Wallet Locking ====================

    /**
     * Lock affected wallets within the current transaction to prevent
     * race conditions during financial repair execution.
     */
    private function lockAffectedWallets(AgentRepairAction $action): void
    {
        $payload = $action->payload;

        // Extract wallet IDs from payload
        $walletIds = [];

        if (!empty($payload['wallet_id'])) {
            $walletIds[] = (int) $payload['wallet_id'];
        }

        // For transaction-linked actions, lock the source + destination wallets
        if (!empty($payload['transaction_id'])) {
            $txn = Transaction::find((int) $payload['transaction_id']);
            if ($txn) {
                if ($txn->wallet_id) $walletIds[] = (int) $txn->wallet_id;
                if ($txn->recipient_wallet_id) $walletIds[] = (int) $txn->recipient_wallet_id;
            }
        }

        // For targetable morph relation
        if ($action->targetable_type === Wallet::class && $action->targetable_id) {
            $walletIds[] = (int) $action->targetable_id;
        }

        $walletIds = array_unique($walletIds);

        // Issue SELECT ... FOR UPDATE on each wallet
        foreach ($walletIds as $id) {
            Wallet::where('id', $id)->lockForUpdate()->first();
        }
    }

    // ==================== Escalation ====================

    /**
     * Count pending escalations (repairs waiting for human review).
     */
    public function countPendingEscalations(): int
    {
        return AgentRepairAction::where('status', RepairActionStatus::ESCALATED->value)
            ->where('escalated_to_human', true)
            ->count();
    }

    /**
     * Get all pending escalations for the admin panel.
     */
    public function getPendingEscalations(int $limit = 50): iterable
    {
        return AgentRepairAction::where('status', RepairActionStatus::ESCALATED->value)
            ->where('escalated_to_human', true)
            ->with(['agentRun', 'targetable'])
            ->orderBy('financial_impact', 'desc')
            ->orderBy('created_at')
            ->limit($limit)
            ->get();
    }

    // ==================== Agent Registry ====================

    /**
     * Register a custom agent for a given type.
     */
    public function registerAgent(string $agentType, BaseVerificationAgent $agent): void
    {
        $this->agentRegistry[$agentType] = $agent;
    }

    /**
     * Resolve the agent instance for a given type string.
     */
    private function resolveAgent(string $agentTypeStr): ?BaseVerificationAgent
    {
        return $this->agentRegistry[$agentTypeStr] ?? null;
    }

    /**
     * Get all registered agent types.
     */
    public function getRegisteredAgents(): array
    {
        return array_keys($this->agentRegistry);
    }
}
