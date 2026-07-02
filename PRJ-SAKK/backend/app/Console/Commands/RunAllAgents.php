<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Agent\AgentOrchestrator;
use Illuminate\Console\Command;

/**
 * Run ALL registered verification agents sequentially.
 *
 * Executes Financial Reconciliation first, then KYC Verification.
 * Optionally executes pending signed repairs after all agents complete.
 *
 *   php artisan agent:run-all                  # run all agents
 *   php artisan agent:run-all --repair         # run all + execute repairs
 *   php artisan agent:run-all --dry-run        # report only
 */
class RunAllAgents extends Command
{
    protected $signature = 'agent:run-all
        {--repair : Execute pending signed repairs after all agents complete}
        {--dry-run : Report only, no actions}';

    protected $description = 'Run ALL verification agents sequentially';

    public function handle(AgentOrchestrator $orchestrator): int
    {
        $this->info('🤖 Running ALL verification agents...');

        if ($this->option('dry-run')) {
            $this->warn('⚠️  DRY RUN mode — no repair actions will be executed.');
        }

        $results = $orchestrator->runAllAgents(
            $this->option('dry-run') ? 'dry-run' : 'scheduled'
        );

        $this->newLine();
        $this->info('📊 Results:');
        $this->table(
            ['Agent', 'Status', 'Scanned', 'Anomalies', 'Repairs', 'Escalations', 'Duration (ms)'],
            collect($results)->map(fn ($run, $type) => [
                $type,
                $run?->status ?? 'blocked',
                $run?->items_scanned ?? '-',
                $run?->anomalies_found ?? '-',
                $run?->auto_repairs_triggered ?? '-',
                $run?->escalations ?? '-',
                $run?->duration_ms ?? '-',
            ])->toArray()
        );

        // Execute pending repairs
        if ($this->option('repair') && !$this->option('dry-run')) {
            $this->newLine();
            $this->info('🔧 Executing pending signed repairs...');
            $executed = $orchestrator->executePendingRepairs();
            $this->line("Repairs executed: {$executed}");
        }

        // Show pending escalations
        $pendingEsc = $orchestrator->countPendingEscalations();
        if ($pendingEsc > 0) {
            $this->newLine();
            $this->warn("⚠️  {$pendingEsc} repair action(s) pending human review (escalated).");
            $this->line('Run: php artisan agent:escalations to review them.');
        }

        $this->newLine();
        $this->info('✅ All agents complete.');

        return self::SUCCESS;
    }
}
