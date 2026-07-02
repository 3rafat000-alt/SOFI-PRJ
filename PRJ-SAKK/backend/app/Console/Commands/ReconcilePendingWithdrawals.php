<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\TransactionCategory;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\CCPaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sweep crypto withdrawals stuck between Phase A (short-locked debit +
 * reserve, tx PENDING / metadata.gateway_dispatched=false) and Phase B (the
 * gateway HTTP call) of the optimistic-debit withdraw flow in
 * CCPaymentController::withdraw.
 *
 * A hard process kill (deploy restart, OOM kill, host crash) between the two
 * phases leaves funds debited from the wallet with the gateway never called
 * — Phase B's own try/catch never runs, so its refund-on-failure path never
 * fires either. This command finds those orphans and resolves them:
 *
 *   - Gateway has no record of the order  -> refund the wallet, mark FAILED.
 *   - Gateway DOES have a record          -> flip gateway_dispatched=true and
 *                                            sync status from the gateway's
 *                                            reported state (webhook/manual
 *                                            retry can then proceed normally).
 *
 * Idempotency: every branch locks the transaction row first and re-checks
 * gateway_dispatched / status inside the lock, so a concurrent manual retry
 * or a second sweep run cannot double-refund or clobber a state that was
 * already resolved by something else.
 *
 *   php artisan withdrawals:reconcile-pending
 *   php artisan withdrawals:reconcile-pending --minutes=15
 *   php artisan withdrawals:reconcile-pending --dry-run
 */
class ReconcilePendingWithdrawals extends Command
{
    protected $signature = 'withdrawals:reconcile-pending
        {--minutes= : Age threshold in minutes (default: config ccpayment.reconcile_withdrawals_after_minutes)}
        {--dry-run : Show what would happen without writing}';

    protected $description = 'Recover crypto withdrawals stuck debited-but-not-dispatched to the gateway (Phase A committed, Phase B never ran).';

    public function handle(CCPaymentService $cc): int
    {
        $minutes = (int) ($this->option('minutes') ?? config('services.ccpayment.reconcile_withdrawals_after_minutes', 10));
        $cutoff = now()->subMinutes($minutes);
        $dryRun = (bool) $this->option('dry-run');

        // The gateway_dispatched flag lives inside the metadata JSON column;
        // filter it in PHP rather than a JSON-path DB query so behaviour is
        // identical across sqlite (test) and MySQL/Postgres (prod) drivers.
        $stuckIds = Transaction::query()
            ->where('type', TransactionType::WITHDRAWAL)
            ->where('category', TransactionCategory::CRYPTO)
            ->where('status', TransactionStatus::PENDING)
            ->where('created_at', '<=', $cutoff)
            ->get(['id', 'metadata'])
            ->filter(fn (Transaction $tx) => ($tx->metadata['gateway_dispatched'] ?? true) === false)
            ->pluck('id');

        if ($stuckIds->isEmpty()) {
            $this->info('No stuck withdrawals found.');
            return self::SUCCESS;
        }

        $this->info("Found {$stuckIds->count()} stuck withdrawal(s) older than {$minutes} minute(s).");

        $refunded = 0;
        $dispatched = 0;

        foreach ($stuckIds as $id) {
            $outcome = $dryRun
                ? $this->preview($id, $cc)
                : $this->reconcileOne($id, $cc);

            match ($outcome) {
                'refunded' => $refunded++,
                'dispatched' => $dispatched++,
                default => null,
            };
        }

        $this->info($dryRun
            ? 'Dry run complete — no changes written.'
            : "Done. Refunded {$refunded}, re-synced {$dispatched} dispatched.");

        return self::SUCCESS;
    }

    /**
     * Resolve one stuck withdrawal. Runs inside its own DB transaction with
     * ascending-id lock order (transaction row, then its wallet — the
     * transaction's own PK is looked up first so there is only ever one lock
     * order for this pair of rows).
     */
    private function reconcileOne(int $transactionId, CCPaymentService $cc): ?string
    {
        // Query the gateway OUTSIDE any lock — same rule as Phase B itself
        // (no external HTTP under a wallet lock).
        $tx = Transaction::find($transactionId);
        if (!$tx) {
            return null;
        }

        try {
            $record = $cc->getWithdrawRecord($tx->reference);
        } catch (\Throwable $e) {
            Log::warning('withdrawals:reconcile-pending gateway lookup failed', [
                'transaction_id' => $transactionId,
                'reference' => $tx->reference,
                'error' => $e->getMessage(),
            ]);
            $record = [];
        }

        $found = !empty($record);

        return DB::transaction(function () use ($transactionId, $record, $found) {
            /** @var Transaction|null $freshTx */
            $freshTx = Transaction::lockForUpdate()->find($transactionId);

            if (!$freshTx) {
                return null;
            }

            // Re-check inside the lock: another process (webhook, manual
            // retry, a concurrent sweep run) may already have resolved this.
            if ($freshTx->status !== TransactionStatus::PENDING
                || ($freshTx->metadata['gateway_dispatched'] ?? false) === true) {
                return null;
            }

            if (!$found) {
                // Gateway has never heard of this order — Phase B never ran.
                // Refund under the deterministic lock order: transaction row
                // first (already locked above), then its wallet.
                $wallet = Wallet::lockForUpdate()->find($freshTx->wallet_id);
                if ($wallet) {
                    $wallet->credit((float) $freshTx->amount);
                }

                $freshTx->update([
                    'status' => TransactionStatus::FAILED,
                    'metadata' => array_merge($freshTx->metadata ?? [], [
                        'gateway_dispatched' => false,
                        'refunded' => true,
                        'failure_reason' => 'reconcile: no gateway record found, orphaned debit refunded',
                    ]),
                ]);

                $this->line("• #{$freshTx->id} ({$freshTx->reference}) — refunded, marked FAILED");

                return 'refunded';
            }

            // Gateway DOES have the order — Phase B's HTTP call actually
            // reached CCPayment before the process died; only the local
            // metadata update was lost. Mark it dispatched and sync status
            // from the gateway's reported state, mirroring
            // CCPaymentService::handleWithdrawWebhook's status mapping.
            $rawStatus = strtolower(trim((string) ($record['status'] ?? '')));
            $newStatus = match ($rawStatus) {
                'success', 'completed' => TransactionStatus::COMPLETED,
                'failed', 'cancelled', 'canceled', 'rejected' => TransactionStatus::FAILED,
                default => TransactionStatus::PROCESSING,
            };

            $metadata = array_merge($freshTx->metadata ?? [], [
                'gateway_dispatched' => true,
                'ccpayment_record_id' => $record['recordId'] ?? ($freshTx->metadata['ccpayment_record_id'] ?? null),
                'ccpayment_status' => $rawStatus,
            ]);

            // Refund on the way in only if the gateway itself already failed
            // the withdrawal (mirrors the webhook handler's failure branch).
            if ($newStatus === TransactionStatus::FAILED) {
                $wallet = Wallet::lockForUpdate()->find($freshTx->wallet_id);
                if ($wallet) {
                    $wallet->credit((float) $freshTx->amount);
                }
                $metadata['refunded'] = true;
            }

            $freshTx->update([
                'status' => $newStatus,
                'metadata' => $metadata,
            ]);

            $this->line("• #{$freshTx->id} ({$freshTx->reference}) — gateway found, status={$rawStatus}, synced to {$newStatus->value}");

            return 'dispatched';
        });
    }

    private function preview(int $transactionId, CCPaymentService $cc): ?string
    {
        $tx = Transaction::find($transactionId);
        if (!$tx) {
            return null;
        }

        try {
            $record = $cc->getWithdrawRecord($tx->reference);
        } catch (\Throwable $e) {
            $record = [];
        }

        if (empty($record)) {
            $this->line("• #{$tx->id} ({$tx->reference}) — would REFUND (no gateway record)");
        } else {
            $status = strtolower(trim((string) ($record['status'] ?? '')));
            $this->line("• #{$tx->id} ({$tx->reference}) — gateway found, status={$status}, would sync + mark dispatched");
        }

        return null;
    }
}
