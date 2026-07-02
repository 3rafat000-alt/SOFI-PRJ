<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\VirtualCard;
use App\Models\KycVerification;
use App\Models\Merchant;
use App\Models\Agent;
use App\Models\Company;
use App\Models\ExchangeRate;
use App\Models\PlatformRevenue;
use App\Models\SupportTicket;
use App\Enums\TransactionStatus;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // ───────────────────────────────────────────────────────────────
        // PLATFORM BALANCES — USD + SYP separate (never summed blindly)
        // ───────────────────────────────────────────────────────────────
        $usdRate  = (float) (ExchangeRate::where('from_currency', 'USD')
            ->where('to_currency', 'SYP')->value('rate') ?? 13000);
        $usdBalance = (float) Wallet::where('currency', 'USD')->sum('balance');
        $sypBalance = (float) Wallet::where('currency', 'SYP')->sum('balance');
        $totalUsdEq = $usdBalance + ($usdRate > 0 ? $sypBalance / $usdRate : 0);

        $toUsd = fn (float $syp) => $usdRate > 0 ? $syp / $usdRate : 0.0;

        // ───────────────────────────────────────────────────────────────
        // REVENUE — fees (USD+SYP) + exchange spread profit
        // ───────────────────────────────────────────────────────────────
        $feeUsd      = (float) Transaction::where('currency', 'USD')->sum('fee');
        $feeSyp      = (float) Transaction::where('currency', 'SYP')->sum('fee');
        $spreadSyp   = (float) PlatformRevenue::where('source', PlatformRevenue::SOURCE_EXCHANGE_SPREAD)->sum('amount');
        $revenueUsd  = $feeUsd + $toUsd($feeSyp + $spreadSyp);

        $feeUsdToday    = (float) Transaction::where('currency', 'USD')->whereDate('created_at', today())->sum('fee');
        $feeSypToday    = (float) Transaction::where('currency', 'SYP')->whereDate('created_at', today())->sum('fee');
        $spreadSypToday = (float) PlatformRevenue::where('source', PlatformRevenue::SOURCE_EXCHANGE_SPREAD)
            ->whereDate('created_at', today())->sum('amount');
        $revenueToday   = $feeUsdToday + $toUsd($feeSypToday + $spreadSypToday);

        // ───────────────────────────────────────────────────────────────
        // AGGREGATE STATS
        // ───────────────────────────────────────────────────────────────
        $activeCards  = VirtualCard::where('status', 'active')->count();
        $totalTxCount = Transaction::count();
        $txCountToday = Transaction::whereDate('created_at', today())->count();
        $totalUsers   = User::count();
        $newUsersToday = User::whereDate('created_at', today())->count();

        $stats = [
            'total_users'         => $totalUsers,
            'new_users_today'     => $newUsersToday,
            'total_balance'       => $totalUsdEq,
            'total_balance_usd'   => $usdBalance,
            'total_balance_syp'   => $sypBalance,
            'total_transactions'  => $totalTxCount,
            'transactions_today'  => $txCountToday,
            'active_cards'        => $activeCards,
            'total_revenue'       => $revenueUsd,
            'revenue_today'       => $revenueToday,
            'exchange_spread_syp' => $spreadSyp,
            'exchange_spread_usd' => $toUsd($spreadSyp),
            'volume'              => (float) Transaction::where('status', TransactionStatus::COMPLETED)
                ->sum(DB::raw('ABS(amount)')),
            'pending_kyc'         => KycVerification::where('status', 'pending')->count(),
            'merchants'           => Merchant::count(),
            'agents'              => Agent::count(),
            'companies'           => Company::count(),
            'usd_rate'            => $usdRate,
        ];

        // ───────────────────────────────────────────────────────────────
        // C · RECENT LARGE OPERATIONS LEDGER
        // Large financial movements (injections, high-value tx) — any tx
        // with amount ≥ threshold in its currency.
        // ───────────────────────────────────────────────────────────────
        $recentLargeOps = Transaction::with('user')
            ->where('status', TransactionStatus::COMPLETED)
            ->where(function ($q) {
                $q->where(function ($q2) {
                    // USD threshold: >= 10,000
                    $q2->where('currency', 'USD')->where('amount', '>=', 10000);
                })->orWhere(function ($q2) {
                    // SYP threshold: >= 1,000,000
                    $q2->where('currency', 'SYP')->where('amount', '>=', 1000000);
                });
            })
            ->latest()
            ->take(8)
            ->get();

        // ───────────────────────────────────────────────────────────────
        // F · KYC STATS + PENDING QUEUE
        // ───────────────────────────────────────────────────────────────
        $kycStats = [
            'pending'  => KycVerification::where('status', 'pending')->count(),
            'approved' => KycVerification::where('status', 'approved')->count(),
            'rejected' => KycVerification::where('status', 'rejected')->count(),
            'total'    => KycVerification::count(),
        ];

        // G · Latest verification requests (all, not just pending)
        $latestKyc = KycVerification::with('user')
            ->latest()
            ->take(6)
            ->get();

        // Pending KYC queue (for the attention section)
        $pendingKyc = KycVerification::with('user')
            ->where('status', 'pending')
            ->latest()
            ->take(5)
            ->get();

        // ───────────────────────────────────────────────────────────────
        // RECENT TRANSACTIONS (for the table)
        // ───────────────────────────────────────────────────────────────
        $recentTransactions = Transaction::with('user')
            ->latest()
            ->take(8)
            ->get();

        // ───────────────────────────────────────────────────────────────
        // PENDING SUPPORT TICKETS
        // ───────────────────────────────────────────────────────────────
        $pendingTickets = SupportTicket::whereIn('status', ['open', 'pending'])->count();

        // ───────────────────────────────────────────────────────────────
        // WEEK-OVER-WEEK GROWTH
        // ───────────────────────────────────────────────────────────────
        $growth = [
            'users'        => $this->weekGrowthCount(User::query()),
            'transactions' => $this->weekGrowthCount(Transaction::query()),
            'revenue'      => $this->weekGrowthSum(Transaction::query(), 'fee'),
        ];

        // ───────────────────────────────────────────────────────────────
        // CHART DATA (last 7 days)
        // ───────────────────────────────────────────────────────────────
        $chartData = $this->getChartData();

        return view('admin.dashboard', compact(
            'stats', 'growth', 'chartData',
            'recentTransactions', 'recentLargeOps',
            'kycStats', 'pendingKyc', 'latestKyc',
            'activeCards', 'pendingTickets'
        ));
    }

    private function weekGrowthCount($query): float
    {
        $now  = Carbon::now();
        $cur  = (clone $query)->whereBetween('created_at', [$now->copy()->subDays(7), $now])->count();
        $prev = (clone $query)->whereBetween('created_at', [$now->copy()->subDays(14), $now->copy()->subDays(7)])->count();

        if ($prev === 0) {
            return $cur > 0 ? 100.0 : 0.0;
        }

        return round((($cur - $prev) / $prev) * 100, 1);
    }

    private function weekGrowthSum($query, string $column): float
    {
        $now  = Carbon::now();
        $cur  = (float) (clone $query)->whereBetween('created_at', [$now->copy()->subDays(7), $now])->sum($column);
        $prev = (float) (clone $query)->whereBetween('created_at', [$now->copy()->subDays(14), $now->copy()->subDays(7)])->sum($column);

        if ($prev == 0.0) {
            return $cur > 0 ? 100.0 : 0.0;
        }

        return round((($cur - $prev) / $prev) * 100, 1);
    }

    private function getChartData(): array
    {
        $labels = $transactions = $revenue = $users = [];
        $weekdaysAr = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $day  = $date->toDateString();
            $labels[]       = $weekdaysAr[$date->dayOfWeek] ?? $date->format('D');
            $transactions[] = Transaction::whereDate('created_at', $day)->count();
            $revenue[]      = round((float) Transaction::whereDate('created_at', $day)->sum('fee'), 2);
            $users[]        = User::whereDate('created_at', $day)->count();
        }

        return compact('labels', 'transactions', 'revenue', 'users');
    }
}
