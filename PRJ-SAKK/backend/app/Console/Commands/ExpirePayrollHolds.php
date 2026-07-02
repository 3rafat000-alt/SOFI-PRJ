<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\PayrollItem;
use App\Models\Wallet;
use App\Services\PayrollService;
use Illuminate\Console\Command;

/**
 * Releases salary that has been held (for unregistered employees) longer than the
 * configured window back to the company wallet, and reports any reconciliation
 * drift between held items and company pending_balance.
 *
 * Schedule daily, e.g. in routes/console.php:
 *   Schedule::command('payroll:expire-holds')->daily();
 */
class ExpirePayrollHolds extends Command
{
    protected $signature = 'payroll:expire-holds {--days=30 : Release holds older than this many days}';

    protected $description = 'Release expired payroll holds back to company wallets and report reconciliation drift';

    public function handle(PayrollService $payroll): int
    {
        $days = (int) $this->option('days');

        $expired = $payroll->expireHeldOlderThan($days);
        $this->info("Released {$expired} expired hold(s) older than {$days} days.");

        // Reconciliation: per company+currency, sum(held items) must equal the
        // company wallet's pending_balance. Report any drift for investigation.
        $drift = 0;
        $heldByWallet = PayrollItem::where('status', PayrollItem::STATUS_HELD)
            ->selectRaw('company_id, currency, SUM(amount) as held_sum')
            ->groupBy('company_id', 'currency')
            ->get();

        foreach ($heldByWallet as $row) {
            $wallet = Wallet::where('company_id', $row->company_id)
                ->where('currency', $row->currency)->first();
            $pending = (float) ($wallet->pending_balance ?? 0);
            if (abs($pending - (float) $row->held_sum) > 0.00000001) {
                $drift++;
                $company = Company::find($row->company_id);
                $this->warn(sprintf(
                    'DRIFT company=%s currency=%s held=%s pending=%s',
                    $company?->company_code ?? $row->company_id,
                    $row->currency,
                    $row->held_sum,
                    $pending,
                ));
            }
        }

        $this->info($drift === 0 ? 'Reconciliation OK — held == pending everywhere.' : "Reconciliation drift on {$drift} wallet(s).");

        return self::SUCCESS;
    }
}
