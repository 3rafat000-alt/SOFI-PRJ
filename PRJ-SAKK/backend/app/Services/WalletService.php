<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\PlatformRevenue;
use App\Enums\TransactionType;
use App\Enums\TransactionCategory;
use App\Enums\TransactionStatus;
use App\Models\ExchangeRate;
use App\Models\Fee;
use App\Services\ExchangeRateService;
use App\Support\LedgerHaltGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletService
{
    public function __construct(
        protected ExchangeRateService $exchangeRateService,
        protected FeeService $feeService
    ) {}
    /**
     * Create a new wallet for user
     */
    public function createWallet(User $user, string $currency): Wallet
    {
        return $user->wallets()->create([
            'currency' => $currency,
            'is_default' => $user->wallets()->count() === 0,
        ]);
    }

    /**
     * Deposit money to wallet
     */
    public function deposit(Wallet $wallet, float $amount, string $title = 'Deposit', array $metadata = []): Transaction
    {
        return DB::transaction(function () use ($wallet, $amount, $title, $metadata) {
            // Reload under pessimistic lock to prevent TOCTOU on balance
            $locked = Wallet::lockForUpdate()->find($wallet->id);
            if (!$locked) {
                throw new \RuntimeException('المحفظة غير موجودة');
            }

            $balanceBefore = $locked->balance;

            // Credit the wallet
            $locked->credit($amount);

            // Update deposit stats
            $locked->increment('total_deposits', $amount);

            // Create transaction record
            return Transaction::create([
                'user_id' => $locked->user_id,
                'wallet_id' => $locked->id,
                'type' => TransactionType::DEPOSIT,
                'category' => TransactionCategory::WALLET,
                'currency' => $locked->currency,
                'amount' => $amount,
                'fee' => 0,
                'net_amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $locked->balance,
                'status' => TransactionStatus::COMPLETED,
                'title' => $title,
                'metadata' => $metadata,
                'completed_at' => now(),
            ]);
        });
    }

    /**
     * Withdraw money from wallet
     */
    public function withdraw(Wallet $wallet, float $amount, string $title = 'Withdrawal', array $metadata = []): Transaction
    {
        LedgerHaltGuard::assertNotHalted();

        return DB::transaction(function () use ($wallet, $amount, $title, $metadata) {
            // Reload under pessimistic lock to prevent TOCTOU on balance
            $locked = Wallet::lockForUpdate()->find($wallet->id);
            if (!$locked) {
                throw new \RuntimeException('المحفظة غير موجودة');
            }

            $balanceBefore = $locked->balance;

            // KYC velocity cap — INSIDE the lock, BEFORE debit, so concurrent
            // withdraws can't each pass then all debit. Identity-based: user
            // wallets only (company payroll wallets aren't KYC-level gated).
            if ($locked->user_id) {
                app(KycService::class)->assertWithinKycLimits(
                    $locked->user, (float) $amount, $locked->currency, 'withdrawal'
                );
            }

            // Calculate fee via Fee model
            $feeRecord = Fee::active()->byType(Fee::TYPE_WITHDRAWAL)
                ->byCurrency($locked->currency)
                ->first();

            if (!$feeRecord) {
                $feeRecord = Fee::create([
                    'code' => 'withdraw_' . strtolower($locked->currency),
                    'name_ar' => 'سحب ' . $locked->currency,
                    'name_en' => $locked->currency . ' Withdrawal',
                    'type' => Fee::TYPE_WITHDRAWAL,
                    'currency' => $locked->currency,
                    'fixed_amount' => 0,
                    'percentage' => 1,
                    'min_fee' => 0,
                    'min_amount' => 0,
                    'is_active' => true,
                    'sort_order' => 10,
                ]);
            }

            $fee = $feeRecord->calculateFee($amount);
            $netAmount = $amount - $fee;

            // Debit the wallet
            if (!$locked->debit($amount)) {
                throw new \RuntimeException('رصيد غير كافٍ');
            }

            // Update withdrawal stats
            $locked->increment('total_withdrawals', $amount);

            // Create transaction record
            $tx = Transaction::create([
                'user_id' => $locked->user_id,
                'wallet_id' => $locked->id,
                'type' => TransactionType::WITHDRAWAL,
                'category' => TransactionCategory::WALLET,
                'currency' => $locked->currency,
                'amount' => -$amount,
                'fee' => $fee,
                'net_amount' => -$netAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $locked->balance,
                'status' => TransactionStatus::PROCESSING, // Requires processing
                'title' => $title,
                'metadata' => $metadata,
                'processed_at' => now(),
            ]);

            // SEC M8: recognize the withdrawal fee as platform income (treasury
            // ledger), mirroring the convert-spread booking. Without this the fee
            // was debited from the user but never recorded as revenue, so the
            // ledger drifted by the fee on every withdrawal. The wallet is debited
            // the gross amount = net-to-user ($netAmount) + this fee.
            if ($fee > 0) {
                PlatformRevenue::create([
                    'source' => PlatformRevenue::SOURCE_WITHDRAW_FEE,
                    'currency' => $locked->currency,
                    'amount' => round($fee, in_array($locked->currency, ['USD', 'SYP']) ? 2 : 8),
                    'transaction_id' => $tx->id,
                    'user_id' => $locked->user_id,
                    'metadata' => ['gross_amount' => $amount, 'net_amount' => $netAmount],
                ]);
            }

            return $tx;
        });
    }

    /**
     * Get wallet statistics
     */
    public function getStats(Wallet $wallet): array
    {
        $today = $wallet->transactions()
            ->whereDate('created_at', today())
            ->completed()
            ->get();

        $thisMonth = $wallet->transactions()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->completed()
            ->get();

        return [
            'balance' => [
                'current' => $wallet->balance,
                'available' => $wallet->available_balance,
                'pending' => $wallet->pending_balance,
            ],
            'limits' => [
                'daily_limit' => $wallet->daily_limit,
                'daily_spent' => $wallet->daily_spent,
                'daily_remaining' => $wallet->daily_limit - $wallet->daily_spent,
                'monthly_limit' => $wallet->monthly_limit,
                'monthly_spent' => $wallet->monthly_spent,
                'monthly_remaining' => $wallet->monthly_limit - $wallet->monthly_spent,
            ],
            'totals' => [
                'deposits' => $wallet->total_deposits,
                'withdrawals' => $wallet->total_withdrawals,
                'sent' => $wallet->total_sent,
                'received' => $wallet->total_received,
                'transaction_count' => $wallet->transaction_count,
            ],
            'today' => [
                'income' => $today->where('amount', '>', 0)->sum('amount'),
                'expense' => abs($today->where('amount', '<', 0)->sum('amount')),
                'count' => $today->count(),
            ],
            'this_month' => [
                'income' => $thisMonth->where('amount', '>', 0)->sum('amount'),
                'expense' => abs($thisMonth->where('amount', '<', 0)->sum('amount')),
                'count' => $thisMonth->count(),
            ],
        ];
    }

    /**
     * Currency-specific spending limits. SYP limits are scaled up because
     * SYP amounts are ~13,000x larger than their USD equivalents.
     */
    private const WALLET_LIMITS = [
        'USD' => ['daily_limit' => 10000, 'monthly_limit' => 100000],
        'SYP' => ['daily_limit' => 130000000, 'monthly_limit' => 1300000000],
    ];

    /**
     * Ensure user has all required wallets (USD, SYP) with proper limits.
     */
    public function ensureUserWallets(User $user): void
    {
        foreach (self::WALLET_LIMITS as $currency => $limits) {
            $wallet = $user->wallets()->firstOrCreate(
                ['currency' => $currency],
                array_merge(['balance' => '0.000000'], $limits)
            );

            // Backfill limits for wallets created before currency-aware limits.
            if ($wallet->daily_limit !== null && (float) $wallet->daily_limit < $limits['daily_limit']) {
                $wallet->update($limits);
            }
        }
    }

    /**
     * Convert currency between wallets.
     */
    public function convert(Wallet $fromWallet, Wallet $toWallet, float $amount, string $direction): Transaction
    {
        LedgerHaltGuard::assertNotHalted();

        return DB::transaction(function () use ($fromWallet, $toWallet, $amount, $direction) {
            // deadlock-safe: lock wallets in ascending id order (see E-SEV-1)
            $orderedIds = [$fromWallet->id, $toWallet->id];
            sort($orderedIds);
            $lockedById = Wallet::lockForUpdate()
                ->whereIn('id', $orderedIds)
                ->orderBy('id')
                ->get()
                ->keyBy('id');

            $fromLocked = $lockedById->get($fromWallet->id);
            $toLocked = $lockedById->get($toWallet->id);

            if (!$fromLocked || !$toLocked) {
                throw new \RuntimeException('المحفظة غير موجودة');
            }

            // Verify both wallets belong to the same user (IDOR guard)
            if ($fromLocked->user_id !== $toLocked->user_id) {
                throw new \RuntimeException('لا يمكن التحويل بين محافظ مستخدمين مختلفين');
            }

            // Money-committing path: read the authoritative ExchangeRate row
            // under lockForUpdate() inside this same locked transaction —
            // NOT the cached value (up to 5 min stale) — so a rate change
            // mid-flight can't be arbitraged (desk item 4).
            $rateData = $this->exchangeRateService->getAuthoritativeRate('USD', 'SYP');

            if (!$rateData['success']) {
                throw new \RuntimeException('سعر الصرف غير متاح');
            }

            $midRate  = $rateData['rate'];
            $buyRate  = $rateData['buy_rate'];   // platform BUYS USD at this (lower)
            $sellRate = $rateData['sell_rate'];  // platform SELLS USD at this (higher)

            if ($buyRate <= 0 || $sellRate <= 0) {
                throw new \RuntimeException('سعر الصرف غير متاح');
            }

            // Spread is the platform's profit — the customer always gets the worse
            // side. usd→syp: customer sells USD, platform pays the LOW (buy) rate.
            // syp→usd: customer buys USD, platform charges the HIGH (sell) rate.
            if ($direction === 'usd_to_syp') {
                $rateUsed = $buyRate;
                $convertedAmount = $amount * $rateUsed;
                // Profit (SYP kept) = what we'd owe at mid minus what we paid.
                $profitSyp = $amount * ($midRate - $buyRate);
            } else {
                $rateUsed = $sellRate;
                $convertedAmount = $amount / $rateUsed;
                // Profit (SYP kept) = customer's SYP minus the mid-value of the USD given out.
                $profitSyp = $amount * (1 - ($midRate / $sellRate));
            }

            // Re-check balance inside lock (canSpend was checked outside, but TOCTOU)
            if ((float) $fromLocked->available_balance < $amount) {
                throw new \RuntimeException('رصيد غير كافٍ');
            }

            $fromBalanceBefore = $fromLocked->balance;

            if (!$fromLocked->debit($amount)) {
                throw new \RuntimeException('رصيد غير كافٍ');
            }

            $toBalanceBefore = $toLocked->balance;
            $toLocked->credit($convertedAmount);

            $fromLocked->increment('total_sent', $amount);
            $toLocked->increment('total_received', $convertedAmount);

            $sharedMeta = [
                'direction' => $direction,
                'rate' => $rateUsed,
                'mid_rate' => $midRate,
                'from_currency' => $fromLocked->currency,
                'to_currency' => $toLocked->currency,
                'from_amount' => $amount,
                'to_amount' => $convertedAmount,
                'spread_profit_syp' => round($profitSyp, 4),
            ];

            // Double-sided ledger: a debit leg on the source wallet AND a credit
            // leg on the destination wallet. Recording only the debit (the old bug)
            // let the destination balance move with no matching transaction row,
            // so wallet balances drifted from the transaction history.
            $debit = Transaction::create([
                'user_id' => $fromLocked->user_id,
                'wallet_id' => $fromLocked->id,
                'type' => TransactionType::EXCHANGE,
                'category' => TransactionCategory::EXCHANGE,
                'currency' => $fromLocked->currency,
                'amount' => -$amount,
                'fee' => 0,
                'net_amount' => -$amount,
                'balance_before' => $fromBalanceBefore,
                'balance_after' => $fromLocked->balance,
                'status' => TransactionStatus::COMPLETED,
                'title' => "صرف من {$fromLocked->currency} إلى {$toLocked->currency}",
                'original_currency' => $toLocked->currency,
                'original_amount' => $convertedAmount,
                'exchange_rate' => $rateUsed,
                'metadata' => $sharedMeta + ['leg' => 'debit', 'counterpart_wallet_id' => $toLocked->id],
                'completed_at' => now(),
            ]);

            Transaction::create([
                'user_id' => $toLocked->user_id,
                'wallet_id' => $toLocked->id,
                'type' => TransactionType::EXCHANGE,
                'category' => TransactionCategory::EXCHANGE,
                'currency' => $toLocked->currency,
                'amount' => $convertedAmount,
                'fee' => 0,
                'net_amount' => $convertedAmount,
                'balance_before' => $toBalanceBefore,
                'balance_after' => $toLocked->balance,
                'status' => TransactionStatus::COMPLETED,
                'title' => "صرف من {$fromLocked->currency} إلى {$toLocked->currency}",
                'original_currency' => $fromLocked->currency,
                'original_amount' => $amount,
                'exchange_rate' => $rateUsed,
                'metadata' => $sharedMeta + ['leg' => 'credit', 'counterpart_wallet_id' => $fromLocked->id],
                'completed_at' => now(),
            ]);

            // Recognize the spread as platform income (in SYP) — the treasury ledger.
            // No wallet movement: the spread is already retained in platform float
            // (we paid out less than mid-value); this is the accounting record.
            if ($profitSyp > 0) {
                PlatformRevenue::create([
                    'source' => PlatformRevenue::SOURCE_EXCHANGE_SPREAD,
                    'currency' => 'SYP',
                    'amount' => round($profitSyp, 2),
                    'transaction_id' => $debit->id,
                    'user_id' => $fromLocked->user_id,
                    'metadata' => ['direction' => $direction, 'mid_rate' => $midRate, 'rate_used' => $rateUsed],
                ]);
            }

            return $debit;
        });
    }

    /**
     * Get all active exchange rates
     */
    public function getExchangeRates(): array
    {
        return ExchangeRate::where('is_active', true)
            ->get()
            ->toArray();
    }

    /**
     * Freeze wallet
     */
    public function freeze(Wallet $wallet, string $reason): void
    {
        $wallet->update([
            'is_frozen' => true,
            'frozen_reason' => $reason,
        ]);
    }

    /**
     * Unfreeze wallet
     */
    public function unfreeze(Wallet $wallet): void
    {
        $wallet->update([
            'is_frozen' => false,
            'frozen_reason' => null,
        ]);
    }
}
