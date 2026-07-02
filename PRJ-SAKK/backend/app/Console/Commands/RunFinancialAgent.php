<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AgentType;
use App\Services\Agent\AgentOrchestrator;
use Illuminate\Console\Command;

/**
 * Run the Financial Reconciliation Agent (وكيل التحقق المالي والإصلاح).
 *
 * Scans all active wallets for ledger-balance drift, negative balances,
 * duplicate references, orphan transactions, and invariant violations.
 * Auto-repairs drift below threshold; escalates critical findings.
 *
 *   php artisan agent:financial                   # scan all wallets
 *   php artisan agent:financial --wallet=42       # single wallet
 *   php artisan agent:financial --currency=USD    # filter by currency
 *   php artisan agent:financial --repair          # execute pending repairs after scan
 */
class RunFinancialAgent extends Command
{
    protected $signature = 'agent:financial
        {--wallet= : Limit scan to a single wallet ID}
        {--currency= : Limit scan to a specific currency (USD, SYP)}
        {--repair : Also execute pending signed repairs after scan}
        {--dry-run : Report only, no auto-repair actions created}';

    protected $description = 'Run Financial Reconciliation Agent — audit ledgers, detect drift, auto-repair';

    public function handle(AgentOrchestrator $orchestrator): int
    {
        $this->info('🤖 Financial Reconciliation Agent starting...');

        // Build trigger context
        $triggerable = null;
        if ($walletId = $this->option('wallet')) {
            $triggerable = \App\Models\Wallet::find((int) $walletId);
            if (!$triggerable) {
                $this->error("Wallet #{$walletId} not found.");
                return self::FAILURE;
            }
            $this->line("Scope: wallet #{$walletId}");
        }

        if ($currency = $this->option('currency')) {
            $this->line("Scope: currency {$currency}");
        }

        if ($this->option('dry-run')) {
            $this->warn('⚠️  DRY RUN — no repair actions will be created or executed.');
        }

        // Run the agent
        $run = $orchestrator->runAgent(
            AgentType::FINANCIAL_RECONCILIATION,
            $this->option('dry-run') ? 'dry-run' : 'manual',
            $triggerable
        );

        if ($run === null) {
            $this->warn('⏳ Agent run blocked — concurrency guard active (another run in progress).');
            return self::SUCCESS;
        }

        // Display results
        $this->newLine();
        $this->line("Run UUID: {$run->uuid}");
        $this->line("Status:   {$run->status}");

        if ($run->status === 'failed') {
            $this->error('❌ Agent run failed. Check logs for details.');
            return self::FAILURE;
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['Items Scanned', $run->items_scanned],
                ['Anomalies Found', $run->anomalies_found],
                ['Auto-Repairs Triggered', $run->auto_repairs_triggered],
                ['Escalations', $run->escalations],
                ['Duration (ms)', $run->duration_ms],
                ['Threshold Breached', $run->threshold_breached ? 'YES' : 'No'],
            ]
        );

        if ($run->summary) {
            $this->newLine();
            $this->info('Summary:');
            foreach ($run->summary as $key => $value) {
                $this->line("  {$key}: {$value}");
            }
        }

        // Execute pending repairs if requested
        if ($this->option('repair') && !$this->option('dry-run')) {
            $this->newLine();
            $this->info('🔧 Executing pending signed repairs...');
            $executed = $orchestrator->executePendingRepairs();
            $this->line("Repairs executed: {$executed}");
        }

        $this->newLine();
        $this->info('✅ Financial Reconciliation Agent complete.');

        return self::SUCCESS;
    }
}
