<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AgentType;
use App\Services\Agent\AgentOrchestrator;
use App\Services\AdminNotificationService;
use App\Support\LedgerHaltGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Scheduled ledger-integrity auditor (hourly).
 *
 * Correctness note: this command does NOT re-derive its own drift math. The
 * codebase already has an authoritative, tested invariant checker —
 * FinancialVerificationAgent (agent:financial, scheduled every 5 minutes) —
 * which, per wallet, checks:
 *
 *   wallet.balance == SUM(transactions.amount WHERE wallet_id = X AND
 *                         status = COMPLETED)
 *
 * (transactions.amount is signed: positive = credit, negative = debit, per
 * the create_transactions migration comment and every write-site in
 * WalletService/TransferService/PayrollService/CardService — verified by
 * inspection). It also checks negative-balance, duplicate external
 * references, orphan transactions, and balance = available + pending. A
 * naive SUM(debit)-SUM(credit) sketch does not fit this schema: there is no
 * separate debit/credit column, and per-transaction balance snapshots
 * (balance_before/balance_after) are captured around each wallet mutation,
 * not derived after the fact.
 *
 * What was MISSING and is what this command adds:
 *   1. A hookup from that agent's findings to AdminNotificationService (the
 *      agent only wrote an AgentRun/AgentRepairAction row + fired an
 *      internal webhook — no admin-panel alert).
 *   2. An env-gated response: Staging = alert only (detect, log, notify, do
 *      NOT stop money movement). Production = hard lockdown (engage
 *      LedgerHaltGuard so every money-out entrypoint refuses new disbursals
 *      until a human clears it).
 *
 * Tolerance: none applied here — the agent already treats any |drift| below
 * config('agents.auto_repair_threshold') (SYP) as a low-severity "warning"
 * (auto-settled, non-critical) and anything at/above it as "critical". This
 * command only acts on `threshold_breached` (i.e. a critical-severity
 * finding: drift over threshold, negative balance, duplicate reference, or
 * broken balance invariant) — never on ordinary rounding-scale warnings, so
 * it will not false-alarm on routine decimal settlement noise.
 */
class AuditLedgerIntegrity extends Command
{
    protected $signature = 'audit:ledger';

    protected $description = 'Run the ledger-integrity check and react per environment (alert-only in staging, hard-lockdown in production)';

    public function handle(AgentOrchestrator $orchestrator): int
    {
        $run = $orchestrator->runAgent(AgentType::FINANCIAL_RECONCILIATION, 'scheduled-audit');

        if ($run === null) {
            // Concurrency guard active — another financial run is in flight.
            // Not an error: skip this pass, the next scheduled run will catch it.
            $this->info('audit:ledger skipped — financial reconciliation already running.');
            return self::SUCCESS;
        }

        if (!$run->threshold_breached) {
            $this->info('audit:ledger — no critical ledger-integrity anomalies. All clear.');
            return self::SUCCESS;
        }

        $summary = $run->summary ?? [];
        $detail = sprintf(
            'AgentRun #%s: anomalies=%d escalations=%d summary=%s',
            $run->uuid,
            $run->anomalies_found,
            $run->escalations,
            json_encode($summary, JSON_UNESCAPED_UNICODE)
        );

        Log::critical('Ledger integrity breach detected', [
            'agent_run_uuid' => $run->uuid,
            'anomalies_found' => $run->anomalies_found,
            'escalations' => $run->escalations,
            'summary' => $summary,
            'environment' => app()->environment(),
        ]);

        AdminNotificationService::systemError('سلامة السجل المالي (Ledger Integrity)', $detail);

        if (app()->environment() !== 'production') {
            // Staging / non-production: alert-only. Never halt money movement
            // in a pre-prod environment — it would block UAT/QA on findings
            // that may be seeded/test data drift.
            $this->warn("audit:ledger — CRITICAL findings in {$this->envLabel()} (alert-only, no halt). {$detail}");
            return self::SUCCESS;
        }

        // Production: hard lockdown — refuse new disbursals until a human
        // clears the flag after review.
        LedgerHaltGuard::engage($detail);

        $this->error("audit:ledger — CRITICAL findings in production. DISBURSEMENT HALT ENGAGED. {$detail}");

        return self::FAILURE;
    }

    private function envLabel(): string
    {
        return app()->environment();
    }
}
