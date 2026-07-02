<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\RepairActionStatus;
use App\Models\AgentRepairAction;
use Illuminate\Console\Command;

/**
 * List pending agent escalations for human admin review.
 *
 *   php artisan agent:escalations              # list all
 *   php artisan agent:escalations --limit=10   # top 10 by impact
 *   php artisan agent:escalations --id=5       # show detail for one
 */
class ListAgentEscalations extends Command
{
    protected $signature = 'agent:escalations
        {--id= : Show details for a specific escalation ID}
        {--limit=50 : Maximum escalations to list}';

    protected $description = 'List agent repair actions escalated for human review';

    public function handle(): int
    {
        if ($id = $this->option('id')) {
            return $this->showDetail((int) $id);
        }

        $escalations = AgentRepairAction::where('status', RepairActionStatus::ESCALATED->value)
            ->where('escalated_to_human', true)
            ->with(['agentRun', 'targetable'])
            ->orderBy('financial_impact', 'desc')
            ->orderBy('created_at')
            ->limit((int) $this->option('limit'))
            ->get();

        if ($escalations->isEmpty()) {
            $this->info('✅ No pending escalations. All clear.');
            return self::SUCCESS;
        }

        $this->warn("⚠️  {$escalations->count()} pending escalation(s):");
        $this->newLine();

        $this->table(
            ['ID', 'Type', 'Action', 'Target', 'Impact (SYP)', 'Reason', 'Created'],
            $escalations->map(fn (AgentRepairAction $a) => [
                $a->id,
                $a->agent_type,
                $a->action_type,
                "{$a->targetable_type} #{$a->targetable_id}",
                number_format((float) $a->financial_impact, 2),
                \Illuminate\Support\Str::limit($a->reason, 60),
                $a->created_at->diffForHumans(),
            ])->toArray()
        );

        $this->newLine();
        $this->line("Run: php artisan agent:escalations --id={ID} to see full details.");

        return self::SUCCESS;
    }

    private function showDetail(int $id): int
    {
        $action = AgentRepairAction::with(['agentRun', 'targetable', 'escalatedTo'])
            ->find($id);

        if (!$action) {
            $this->error("Escalation #{$id} not found.");
            return self::FAILURE;
        }

        $this->info("📋 Escalation #{$id} Details:");
        $this->newLine();

        $this->line("UUID:       {$action->uuid}");
        $this->line("Agent Type: {$action->agent_type}");
        $this->line("Action:     {$action->action_type} ({$action->action_category})");
        $this->line("Target:     {$action->targetable_type} #{$action->targetable_id}");
        $this->line("Status:     {$action->status}");
        $this->line("Impact:     " . number_format((float) $action->financial_impact, 2) . ' SYP');
        $this->line("Reason:     {$action->reason}");
        $this->line("Note:       {$action->escalation_note}");
        $this->line("Created:    {$action->created_at}");
        $this->newLine();

        if ($action->payload) {
            $this->info('Payload:');
            $this->line(json_encode($action->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->newLine();
        }

        if ($action->target_snapshot) {
            $this->info('Target Snapshot (before):');
            $this->line(json_encode($action->target_snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        return self::SUCCESS;
    }
}
