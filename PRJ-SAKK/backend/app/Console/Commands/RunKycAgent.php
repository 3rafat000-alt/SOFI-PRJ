<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AgentType;
use App\Services\Agent\AgentOrchestrator;
use Illuminate\Console\Command;

/**
 * Run the KYC Verification Agent (وكيل التحقق من الهوية والامتثال).
 *
 * Scans pending KYC documents, cross-matches extracted data against user
 * registration metadata, auto-approves high-confidence matches, auto-rejects
 * expired or low-confidence submissions, and escalates borderline cases.
 *
 *   php artisan agent:kyc                    # scan pending documents
 *   php artisan agent:kyc --user=42          # single user
 *   php artisan agent:kyc --repair           # execute approvals/rejections after scan
 *   php artisan agent:kyc --dry-run          # report only, no actions
 */
class RunKycAgent extends Command
{
    protected $signature = 'agent:kyc
        {--user= : Limit scan to a single user ID}
        {--repair : Also execute pending signed repairs after scan}
        {--dry-run : Report only, no auto-repair actions created}';

    protected $description = 'Run KYC Verification Agent — auto-review pending documents';

    public function handle(AgentOrchestrator $orchestrator): int
    {
        $this->info('🤖 KYC Verification Agent starting...');

        if ($this->option('dry-run')) {
            $this->warn('⚠️  DRY RUN — no repair actions will be created or executed.');
        }

        // Run the agent
        $run = $orchestrator->runAgent(
            AgentType::KYC_VERIFICATION,
            $this->option('dry-run') ? 'dry-run' : 'manual',
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
                ['Auto-Approvals/Rejections', $run->auto_repairs_triggered],
                ['Escalations to Human', $run->escalations],
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
            $this->info('🔧 Executing pending signed approvals/rejections...');
            $executed = $orchestrator->executePendingRepairs();
            $this->line("Repairs executed: {$executed}");
        }

        $this->newLine();
        $this->info('✅ KYC Verification Agent complete.');

        return self::SUCCESS;
    }
}
