<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\TransactionCategory;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Services\CCPaymentService;
use Illuminate\Console\Command;

/**
 * Recover CCPayment crypto deposits that never got credited.
 *
 * A missed/malformed webhook (CCPayment still 200s, so it never retries) can
 * leave a deposit stuck PENDING with the user's balance untouched even though
 * the funds arrived at the gateway. This command re-checks PENDING crypto
 * deposits against CCPayment and runs them back through the (now fixed) webhook
 * handler so the wallet is credited exactly once (the idempotency guard makes
 * re-runs safe).
 *
 *   php artisan ccpayment:reconcile-deposits                       # all pending, via API
 *   php artisan ccpayment:reconcile-deposits --reference=sarva_14_X7uY56AE
 *   php artisan ccpayment:reconcile-deposits --reference=... --record=2026...   # known record id
 *   php artisan ccpayment:reconcile-deposits --reference=... --amount=50  # offline manual credit
 *   php artisan ccpayment:reconcile-deposits --dry-run
 */
class ReconcileCCPaymentDeposits extends Command
{
    protected $signature = 'ccpayment:reconcile-deposits
        {--reference= : Only reconcile this transaction reference (referenceId)}
        {--record= : Known CCPayment recordId to look up directly (with --reference)}
        {--amount= : Credit this amount manually without calling the gateway (offline recovery)}
        {--dry-run : Show what would happen without writing}';

    protected $description = 'Re-check pending CCPayment crypto deposits and credit any that completed at the gateway.';

    public function handle(CCPaymentService $cc): int
    {
        $query = Transaction::query()
            ->where('type', TransactionType::DEPOSIT)
            ->where('category', TransactionCategory::CRYPTO)
            ->where('status', TransactionStatus::PENDING);

        if ($ref = $this->option('reference')) {
            $query->where('reference', $ref);
        }

        $pending = $query->get();

        if ($pending->isEmpty()) {
            $this->info('No pending crypto deposits to reconcile.');
            return self::SUCCESS;
        }

        $this->info("Found {$pending->count()} pending crypto deposit(s).");
        $credited = 0;

        foreach ($pending as $tx) {
            $this->line("• {$tx->reference} (tx #{$tx->id}, wallet {$tx->wallet_id})");

            $records = $this->resolveRecords($cc, $tx);

            if (empty($records)) {
                $this->warn('  → no gateway record found / amount unresolved — skipped');
                continue;
            }

            foreach ($records as $record) {
                $status = strtolower(trim((string) ($record['status'] ?? '')));
                $amount = $record['amount'] ?? '?';
                $this->line("  → gateway status={$status} amount={$amount}");

                if ($this->option('dry-run')) {
                    continue;
                }

                // Route through the real handler — single source of truth for
                // status mapping, amount resolution, idempotency and crediting.
                $cc->handleDepositWebhook(['msg' => $record]);

                $tx->refresh();
                if ($tx->status === TransactionStatus::COMPLETED) {
                    $this->info("  ✓ credited {$tx->amount} to wallet {$tx->wallet_id}");
                    $credited++;
                }
            }
        }

        $this->info($this->option('dry-run')
            ? 'Dry run complete — no changes written.'
            : "Done. Credited {$credited} deposit(s).");

        return self::SUCCESS;
    }

    /**
     * Build the record(s) to feed the handler: a manual override, a direct
     * record lookup, or a gateway list filtered by referenceId.
     *
     * @return array<int,array<string,mixed>>
     */
    private function resolveRecords(CCPaymentService $cc, Transaction $tx): array
    {
        // Offline manual recovery: admin supplies the amount from the CCPayment dashboard.
        if ($amount = $this->option('amount')) {
            return [[
                'recordId' => $this->option('record') ?? ('manual_' . $tx->reference),
                'referenceId' => $tx->reference,
                'status' => 'Success',
                'amount' => $amount,
            ]];
        }

        // Direct lookup by a known recordId.
        if ($recordId = $this->option('record')) {
            try {
                $record = $cc->getDepositRecord($recordId);
                if (!empty($record)) {
                    $record['referenceId'] ??= $tx->reference;
                    return [$record];
                }
            } catch (\Throwable $e) {
                $this->warn('  → getDepositRecord failed: ' . $e->getMessage());
            }
            return [];
        }

        // CCPayment's record list cannot be filtered by referenceId (it returns
        // "invalid argument"); only coinId is accepted. So scan the candidate
        // coins, paginate, and match our referenceId client-side.
        foreach ($this->candidateCoinIds($tx) as $coinId) {
            $nextId = '';
            do {
                try {
                    $data = $cc->getDepositRecords(array_filter([
                        'coinId' => $coinId,
                        'nextId' => $nextId ?: null,
                    ]));
                } catch (\Throwable $e) {
                    $this->warn("  → getDepositRecords(coinId={$coinId}) failed: " . $e->getMessage());
                    break;
                }

                $records = $data['records'] ?? [];
                $match = array_values(array_filter($records, fn ($r) =>
                    is_array($r) && (($r['referenceId'] ?? null) === $tx->reference)));

                if (!empty($match)) {
                    return $match;
                }

                $nextId = (string) ($data['nextId'] ?? '');
            } while ($nextId !== '');
        }

        return [];
    }

    /**
     * Coin ids to scan for a transaction: the one recorded at deposit-create
     * time plus the common USDT ids, so a reconcile with no explicit --record
     * still finds the gateway record.
     *
     * @return array<int,int>
     */
    private function candidateCoinIds(Transaction $tx): array
    {
        $fromMeta = (int) ($tx->metadata['coin_id'] ?? 0);

        // 1280 = USDT (CCPayment's canonical id seen on live BSC/TRC20 deposits).
        return array_values(array_unique(array_filter([$fromMeta, 1280, 1027, 1])));
    }
}
