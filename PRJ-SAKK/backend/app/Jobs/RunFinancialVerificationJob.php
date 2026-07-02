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
 * Queue job to run the Financial Reconciliation Agent asynchronously.
 */
class RunFinancialVerificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries = 1;

    private ?int $walletId;
    private ?string $currency;

    public function __construct(?int $walletId = null, ?string $currency = null)
    {
        $this->walletId = $walletId;
        $this->currency = $currency;
    }

    public function handle(AgentOrchestrator $orchestrator): void
    {
        Log::info('RunFinancialVerificationJob: Starting', [
            'wallet_id' => $this->walletId,
            'currency' => $this->currency,
        ]);

        $triggerable = $this->walletId ? \App\Models\Wallet::find($this->walletId) : null;

        $orchestrator->runAgent(
            AgentType::FINANCIAL_RECONCILIATION,
            'scheduled',
            $triggerable,
        );

        // Also execute any pending signed repairs
        $executed = $orchestrator->executePendingRepairs();

        Log::info('RunFinancialVerificationJob: Complete', [
            'repairs_executed' => $executed,
        ]);
    }
}
