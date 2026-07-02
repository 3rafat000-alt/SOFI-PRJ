<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\AgentType;
use App\Services\Agent\AgentOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queue job to run the KYC Verification Agent asynchronously.
 *
 * Dispatched on a cron schedule or when new KYC documents are uploaded.
 */
class RunKycVerificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries = 2;

    private ?int $userId;

    public function __construct(?int $userId = null)
    {
        $this->userId = $userId;
    }

    public function handle(AgentOrchestrator $orchestrator): void
    {
        Log::info('RunKycVerificationJob: Starting', [
            'user_id' => $this->userId,
        ]);

        $orchestrator->runAgent(
            AgentType::KYC_VERIFICATION,
            'scheduled',
        );

        // Execute pending approvals/rejections
        $executed = $orchestrator->executePendingRepairs();

        Log::info('RunKycVerificationJob: Complete', [
            'repairs_executed' => $executed,
        ]);
    }
}
