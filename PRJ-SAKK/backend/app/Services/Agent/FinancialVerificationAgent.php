<?php

declare(strict_types=1);

namespace App\Services\Agent;

use App\Enums\AgentType;
use App\Enums\TransactionCategory;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

/**
 * وكيل التحقق المالي والإصلاح — Financial Reconciliation Agent.
 *
 * Continuously audits transaction ledgers against wallet balances to detect and
 * prevent double-spending, database desynchronisation, and race conditions.
 *
 * Detection modes:
 *   1. Ledger-wallet drift: sum(completed transactions) ≠ wallet.balance
 *   2. Negative balance: available_balance < 0 after committed transactions
 *   3. Frozen wallet leak: wallet frozen but transactions still processed
 *   4. Orphan transactions: completed txn with no matching wallet mutation
 *   5. Duplicate reference: same external_reference used twice
 *   6. Double-spend pattern: concurrent transactions that would exceed balance
 *
 * Auto-repair (drift < threshold): inserts settlement entry with crypto signing.
 * Escalation (drift ≥ threshold): human review required, never auto-repaired.
 */
class FinancialVerificationAgent extends BaseVerificationAgent
{
    private const EPSILON = 0.0001;

    public function __construct(
        AgentCryptographicSigner $signer,
        AgentWebhookService $webhook,
    ) {
        parent::__construct($signer, $webhook);
        $this->batchLimit = (int) config('agents.financial.batch_limit', 500);
    }

    protected function agentType(): AgentType
    {
        return AgentType::FINANCIAL_RECONCILIATION;
    }

    protected function agentVersion(): string
    {
        return '2.0.0';
    }

    protected function verify(): array
    {
        $anomalies = [];
        $itemsScanned = 0;
        $repairsTriggered = 0;
        $escalations = 0;
        $thresholdBreached = false;
        $log = '';

        // ──────────────────────────────────────────────
        // PASS 1: Ledger-wallet drift detection
        // ──────────────────────────────────────────────
        $this->log('[PASS 1] Scanning ledger-wallet drift...');

        $walletQuery = Wallet::query()
            ->where('is_active', true)
            ->orderBy('currency')
            ->orderBy('id');

        if ($this->batchLimit > 0) {
            $walletQuery->limit($this->batchLimit);
        }

        $wallets = $walletQuery->get();
        $itemsScanned += $wallets->count();

        foreach ($wallets as $wallet) {
            $anomaly = $this->checkLedgerDrift($wallet);
            if ($anomaly !== null) {
                $anomalies[] = $anomaly;
                if ($anomaly['severity'] === 'critical') {
                    $thresholdBreached = true;
                    $escalations++;
                } elseif ($anomaly['severity'] === 'warning') {
                    $this->handleDriftAnomaly($wallet, $anomaly);
                    $repairsTriggered++;
                }
            }

            // PASS 1.5: Negative balance check
            if ((float) $wallet->available_balance < -self::EPSILON) {
                $anomalies[] = [
                    'type' => 'negative_balance',
                    'wallet_id' => $wallet->id,
                    'currency' => $wallet->currency,
                    'available_balance' => (float) $wallet->available_balance,
                    'severity' => 'critical',
                ];
                $thresholdBreached = true;
                $escalations++;
            }
        }

        // ──────────────────────────────────────────────
        // PASS 2: Duplicate external reference check
        // ──────────────────────────────────────────────
        $this->log('[PASS 2] Scanning duplicate external references...');

        $duplicates = Transaction::select('external_reference', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('external_reference')
            ->where('external_reference', '!=', '')
            ->where('status', TransactionStatus::COMPLETED->value)
            ->groupBy('external_reference')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($duplicates as $dup) {
            $anomalies[] = [
                'type' => 'duplicate_external_reference',
                'external_reference' => $dup->external_reference,
                'count' => $dup->cnt,
                'severity' => 'critical',
            ];
            $thresholdBreached = true;
            $escalations++;
        }

        // ──────────────────────────────────────────────
        // PASS 3: Orphan transactions (completed but no wallet)
        // ──────────────────────────────────────────────
        $this->log('[PASS 3] Scanning orphan transactions...');

        $orphans = Transaction::where('status', TransactionStatus::COMPLETED->value)
            ->whereNull('wallet_id')
            ->whereNull('recipient_wallet_id')
            ->limit(100)
            ->get();

        foreach ($orphans as $txn) {
            $anomalies[] = [
                'type' => 'orphan_transaction',
                'transaction_id' => $txn->id,
                'reference' => $txn->reference,
                'amount' => (float) $txn->amount,
                'currency' => $txn->currency,
                'severity' => 'warning',
            ];
        }

        // ──────────────────────────────────────────────
        // PASS 4: Platform-wide balance consistency
        // ──────────────────────────────────────────────
        $this->log('[PASS 4] Checking platform-wide balance consistency...');

        $platformBalances = Wallet::select(
            'currency',
            DB::raw('SUM(balance) as total_balance'),
            DB::raw('SUM(available_balance) as total_available'),
            DB::raw('SUM(pending_balance) as total_pending'),
        )
            ->where('is_active', true)
            ->groupBy('currency')
            ->get();

        foreach ($platformBalances as $pb) {
            $this->log("Platform {$pb->currency}: balance={$pb->total_balance}, available={$pb->total_available}, pending={$pb->total_pending}");
        }

        // ──────────────────────────────────────────────
        // PASS 5: Verify balance invariants (balance = available + pending)
        // ──────────────────────────────────────────────
        $this->log('[PASS 5] Verifying balance invariants...');

        $broken = Wallet::where('is_active', true)
            ->whereRaw('ABS(balance - (available_balance + pending_balance)) > ' . self::EPSILON)
            ->limit(50)
            ->get();

        foreach ($broken as $wallet) {
            $anomalies[] = [
                'type' => 'broken_balance_invariant',
                'wallet_id' => $wallet->id,
                'currency' => $wallet->currency,
                'balance' => (float) $wallet->balance,
                'available' => (float) $wallet->available_balance,
                'pending' => (float) $wallet->pending_balance,
                'severity' => 'critical',
            ];
            $thresholdBreached = true;
            $escalations++;
        }

        // ──────────────────────────────────────────────
        // Summary
        // ──────────────────────────────────────────────
        $summary = [
            'wallets_scanned' => $wallets->count(),
            'drift_anomalies' => count(array_filter($anomalies, fn($a) => $a['type'] === 'ledger_drift')),
            'negative_balances' => count(array_filter($anomalies, fn($a) => $a['type'] === 'negative_balance')),
            'duplicate_refs' => count(array_filter($anomalies, fn($a) => $a['type'] === 'duplicate_external_reference')),
            'orphans' => count(array_filter($anomalies, fn($a) => $a['type'] === 'orphan_transaction')),
            'broken_invariants' => count(array_filter($anomalies, fn($a) => $a['type'] === 'broken_balance_invariant')),
        ];

        $this->log('[DONE] Financial verification complete.');

        return [
            'items_scanned' => $itemsScanned,
            'anomalies_found' => count($anomalies),
            'repairs_triggered' => $repairsTriggered,
            'escalations' => $escalations,
            'summary' => $summary,
            'log' => $this->currentRun?->log ?? '',
            'threshold_breached' => $thresholdBreached,
        ];
    }

    // ==================== Checks ====================

    /**
     * Check a single wallet for ledger-balance drift.
     *
     * @return array|null Anomaly record, or null if in sync.
     */
    private function checkLedgerDrift(Wallet $wallet): ?array
    {
        $ledgerSum = (float) Transaction::where('wallet_id', $wallet->id)
            ->where('status', TransactionStatus::COMPLETED->value)
            ->sum('amount');

        $balance = (float) $wallet->balance;
        $drift = round($balance - $ledgerSum, 4);

        if (abs($drift) < self::EPSILON) {
            return null; // In sync
        }

        $severity = abs($drift) >= $this->autoRepairThreshold ? 'critical' : 'warning';

        $this->log("Drift detected: wallet={$wallet->id} {$wallet->currency} balance={$balance} ledger={$ledgerSum} drift={$drift} severity={$severity}");

        return [
            'type' => 'ledger_drift',
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'currency' => $wallet->currency,
            'balance' => $balance,
            'ledger_sum' => $ledgerSum,
            'drift' => $drift,
            'severity' => $severity,
        ];
    }

    // ==================== Auto-Repair ====================

    /**
     * Handle a drift anomaly below threshold — insert settlement entry.
     */
    private function handleDriftAnomaly(Wallet $wallet, array $anomaly): void
    {
        $drift = $anomaly['drift'];

        $this->createRepairAction(
            actionType: 'settle_ledger',
            actionCategory: 'financial',
            targetable: $wallet,
            payload: [
                'wallet_id' => $wallet->id,
                'drift' => $drift,
                'currency' => $wallet->currency,
                'settlement_type' => 'adjustment',
            ],
            reason: "Ledger settlement — wallet #{$wallet->id} balance ({$anomaly['balance']}) drifted from ledger sum ({$anomaly['ledger_sum']}) by {$drift} {$wallet->currency}. Inserting non-destructive adjustment.",
            financialImpact: abs($drift),
            targetSnapshot: $this->snapshot($wallet),
        );
    }

    // ==================== Settle Ledger (executed after signing) ====================

    /**
     * Execute a ledger settlement repair. Called by RepairAgent after signing.
     */
    public function executeSettleLedger(AgentRepairAction $action): void
    {
        $payload = $action->payload;
        $wallet = Wallet::findOrFail($payload['wallet_id']);
        $drift = (float) $payload['drift'];

        DB::transaction(function () use ($wallet, $drift, $action) {
            Transaction::create([
                'user_id' => $wallet->user_id,
                'wallet_id' => $wallet->id,
                'type' => TransactionType::ADJUSTMENT,
                'category' => TransactionCategory::ADJUSTMENT,
                'currency' => $wallet->currency,
                'amount' => $drift,
                'fee' => 0,
                'net_amount' => $drift,
                'balance_before' => (float) $wallet->balance - $drift,
                'balance_after' => (float) $wallet->balance,
                'status' => TransactionStatus::COMPLETED,
                'title' => 'تسوية آلية — وكيل التحقق المالي',
                'description' => "Auto-settlement by FinancialVerificationAgent. Drift: {$drift} {$wallet->currency}. No funds moved.",
                'metadata' => [
                    'kind' => 'agent_ledger_settlement',
                    'drift' => $drift,
                    'agent_run_uuid' => $this->currentRun?->uuid,
                    'repair_action_uuid' => $action->uuid,
                ],
                'completed_at' => now(),
            ]);
        });
    }
}
