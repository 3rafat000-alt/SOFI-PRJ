<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\TransactionCategory;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Detect (and optionally settle) drift between a wallet's stored balance and the
 * sum of its completed transactions.
 *
 * The admin panel and the apps display `wallet.balance`; the transaction tab
 * shows the ledger. When a balance was changed without a matching transaction
 * (the old single-sided exchange bug, or balances seeded directly), the two
 * disagree and the wallet "conflicts" with its history.
 *
 * --fix inserts a single non-destructive `adjustment` settlement entry equal to
 * the drift, so the ledger sums to the (authoritative) balance WITHOUT moving
 * any money. Re-runnable: a wallet with zero drift is skipped.
 *
 *   php artisan wallet:reconcile            # report only
 *   php artisan wallet:reconcile --fix      # insert settlement entries
 *   php artisan wallet:reconcile --user=14  # limit to one user
 */
class ReconcileWalletLedger extends Command
{
    protected $signature = 'wallet:reconcile {--fix : Insert settlement adjustment entries} {--user= : Limit to a single user id}';

    protected $description = 'Reconcile wallet balances against their transaction ledger';

    private const EPSILON = 0.0001;

    public function handle(): int
    {
        $query = Wallet::query()->orderBy('user_id')->orderBy('currency');
        if ($this->option('user')) {
            $query->where('user_id', (int) $this->option('user'));
        }

        $fix = (bool) $this->option('fix');
        $drifted = 0;
        $fixed = 0;
        $rows = [];

        foreach ($query->get() as $wallet) {
            $ledger = (float) Transaction::where('wallet_id', $wallet->id)
                ->where('status', TransactionStatus::COMPLETED->value)
                ->sum('amount');
            $balance = (float) $wallet->balance;
            $drift = round($balance - $ledger, 4);

            if (abs($drift) < self::EPSILON) {
                continue;
            }

            $drifted++;
            $rows[] = [$wallet->id, $wallet->user_id, $wallet->currency, $balance, $ledger, $drift];

            if ($fix) {
                $this->settle($wallet, $ledger, $balance, $drift);
                $fixed++;
            }
        }

        if (!$rows) {
            $this->info('✅ All wallets reconcile — balance == ledger everywhere.');
            return self::SUCCESS;
        }

        $this->table(['wallet', 'user', 'cur', 'balance', 'ledger_sum', 'drift'], $rows);
        $this->warn("Drifted wallets: {$drifted}");
        if ($fix) {
            $this->info("Settlement entries inserted: {$fixed} (balances unchanged; ledger now reconciles).");
        } else {
            $this->line('Run again with --fix to insert non-destructive settlement entries.');
        }

        return self::SUCCESS;
    }

    private function settle(Wallet $wallet, float $ledger, float $balance, float $drift): void
    {
        DB::transaction(function () use ($wallet, $ledger, $balance, $drift) {
            Transaction::create([
                'user_id' => $wallet->user_id,
                'wallet_id' => $wallet->id,
                'type' => TransactionType::ADJUSTMENT,
                'category' => TransactionCategory::ADJUSTMENT,
                'currency' => $wallet->currency,
                'amount' => $drift,
                'fee' => 0,
                'net_amount' => $drift,
                'balance_before' => $ledger,
                'balance_after' => $balance,
                'status' => TransactionStatus::COMPLETED,
                'title' => 'تسوية دفترية — مطابقة الرصيد مع سجل المعاملات',
                'description' => 'Ledger settlement entry — reconciles transaction history to the authoritative wallet balance. No funds moved.',
                'metadata' => ['kind' => 'ledger_settlement', 'drift' => $drift],
                'completed_at' => now(),
            ]);
        });
    }
}
