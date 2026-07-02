<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AgencySubscription;
use App\Models\Payment;
use App\Services\SakkService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessRecurringBilling extends Command
{
    protected $signature = 'billing:process
        {--dry-run : Log actions without executing them}
        {--renewal-days=3 : Days before expiry to trigger renewal}';

    protected $description = 'Process recurring billing: mark expired subscriptions, send renewal reminders, create SAKK payment requests for renewals';

    public function handle(SakkService $sakk): int
    {
        $dryRun   = $this->option('dry-run');
        $renewDays= (int) $this->option('renewal-days');
        $now      = now();
        $log      = [];

        // ── 1. Mark expired subscriptions ──
        $expiredCount = $this->expirePastDue($now, $dryRun);
        if ($expiredCount > 0) {
            $msg = "Marked {$expiredCount} subscription(s) as expired";
            $log[] = $msg;
            $this->info($msg);
        }

        // ── 2. Process renewals for subscriptions expiring soon ──
        $renewals = AgencySubscription::whereIn('status', ['active', 'trial'])
            ->where('end_at', '<=', $now->copy()->addDays($renewDays))
            ->where('end_at', '>', $now)
            ->get();

        foreach ($renewals as $subscription) {
            $agency   = $subscription->agency;
            $plan     = $subscription->plan;
            $owner    = $agency?->owner;

            if (!$plan || !$agency) {
                continue;
            }

            if ($plan->price <= 0) {
                // Free plan — just extend
                if (!$dryRun) {
                    $subscription->update([
                        'start_at' => $now,
                        'end_at'   => $now->copy()->addDays($plan->duration_days),
                    ]);
                }
                $msg = "Extended free plan subscription #{$subscription->id} for agency '{$agency->name}'";
                $log[] = $msg;
                $this->info($msg);
                continue;
            }

            // Paid plan — create renewal payment request via SAKK
            if ($dryRun) {
                $this->warn("[DRY-RUN] Would create renewal payment for subscription #{$subscription->id}");
                continue;
            }

            try {
                $payment = Payment::create([
                    'agency_subscription_id' => $subscription->id,
                    'agency_id'              => $agency->id,
                    'amount'                 => $plan->price,
                    'currency'               => $plan->currency ?? 'USD',
                    'payment_method'         => 'sakk',
                    'gateway'                => 'sakk',
                    'status'                 => 'pending',
                    'notes'                  => "Renewal for subscription #{$subscription->id}",
                ]);

                $result = $sakk->createPayment([
                    'amount'       => (float) $plan->price,
                    'currency'     => $plan->currency ?? 'USD',
                    'description'  => 'Subscription renewal - ' . ($plan->name_en ?? 'Plan'),
                    'reference_id' => (string) $payment->id,
                ]);

                if ($result['success']) {
                    $payment->update([
                        'transaction_id' => $result['transaction_id'] ?? null,
                        'notes'          => "Renewal payment URL: {$result['payment_url']}",
                    ]);

                    $msg = "Created renewal payment for subscription #{$subscription->id} via SAKK";
                    $log[] = $msg;
                    $this->info($msg);
                } else {
                    $payment->update([
                        'status' => 'failed',
                        'notes'  => 'SAKK payment creation failed: ' . ($result['error'] ?? 'unknown'),
                    ]);

                    $msg = "Renewal payment failed for subscription #{$subscription->id}: {$result['error']}";
                    $log[] = $msg;
                    $this->error($msg);
                }
            } catch (\Throwable $e) {
                $msg = "Renewal exception for subscription #{$subscription->id}: {$e->getMessage()}";
                $log[] = $msg;
                $this->error($msg);
                Log::error($msg, ['trace' => $e->getTraceAsString()]);
            }
        }

        // ── Summary ──
        $summary = 'Billing processed: ' . implode('; ', $log) ?: 'Nothing to process';
        $this->info($summary);
        Log::info('ProcessRecurringBilling completed', [
            'dry_run'      => $dryRun,
            'expired'      => $expiredCount,
            'renewals'     => $renewals->count(),
            'actions'      => $log,
        ]);

        return Command::SUCCESS;
    }

    private function expirePastDue($now, bool $dryRun): int
    {
        $query = AgencySubscription::whereIn('status', ['active', 'trial'])
            ->where('end_at', '<', $now);

        if ($dryRun) {
            $count = $query->count();
            $this->warn("[DRY-RUN] Would expire {$count} past-due subscription(s)");
            return 0;
        }

        return $query->update(['status' => 'expired']);
    }
}
