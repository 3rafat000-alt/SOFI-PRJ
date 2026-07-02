<?php

namespace App\Services;

use App\Mail\AgentApprovedMail;
use App\Mail\CompanyApprovedMail;
use App\Mail\MerchantApprovedMail;
use App\Models\Agent;
use App\Models\Company;
use App\Models\Merchant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sends the one-time "you have been approved — here is your portal login" email
 * to the owning user when a partner entity (Agent / Merchant / Company) transitions
 * into kyc_status = 'approved'.
 *
 * Idempotency: guards on `approval_notified_at` — only fires on the first
 * non-approved → approved transition. Subsequent admin saves do NOT re-send.
 *
 * Best-effort: the entire send is wrapped in rescue() so a mailer failure
 * NEVER blocks or rolls back the approval write.
 */
class PartnerApprovalNotifier
{
    /**
     * Call after an Agent record is saved.
     * Checks wasChanged / dirty state externally; caller must ensure this is
     * only invoked when kyc_status just became 'approved'.
     */
    public function notifyAgent(Agent $agent): void
    {
        if ($agent->approval_notified_at !== null) {
            return; // already sent
        }

        $email = $agent->user?->email ?? $agent->phone ?? null;
        if (!$email || !str_contains($email, '@')) {
            Log::warning("PartnerApprovalNotifier: agent #{$agent->id} has no valid email — skipping notification.");
            return;
        }

        $url = route('agent.login');

        rescue(
            fn () => Mail::to($email)->send(new AgentApprovedMail($agent, $url, $email)),
            fn (\Throwable $e) => Log::error("PartnerApprovalNotifier: failed to send agent approval mail (agent #{$agent->id}): {$e->getMessage()}"),
            report: false,
        );

        $agent->forceFill(['approval_notified_at' => now()])->save();
    }

    /**
     * Call after a Merchant record is saved.
     */
    public function notifyMerchant(Merchant $merchant): void
    {
        if ($merchant->approval_notified_at !== null) {
            return; // already sent
        }

        $email = $merchant->user?->email ?? $merchant->email ?? null;
        if (!$email || !str_contains($email, '@')) {
            Log::warning("PartnerApprovalNotifier: merchant #{$merchant->id} has no valid email — skipping notification.");
            return;
        }

        $url = route('merchant.login');

        rescue(
            fn () => Mail::to($email)->send(new MerchantApprovedMail($merchant, $url, $email)),
            fn (\Throwable $e) => Log::error("PartnerApprovalNotifier: failed to send merchant approval mail (merchant #{$merchant->id}): {$e->getMessage()}"),
            report: false,
        );

        $merchant->forceFill(['approval_notified_at' => now()])->save();
    }

    /**
     * Call after a Company record is saved.
     */
    public function notifyCompany(Company $company): void
    {
        if ($company->approval_notified_at !== null) {
            return; // already sent
        }

        $email = $company->user?->email ?? $company->email ?? null;
        if (!$email || !str_contains($email, '@')) {
            Log::warning("PartnerApprovalNotifier: company #{$company->id} has no valid email — skipping notification.");
            return;
        }

        $url = route('company.login');

        rescue(
            fn () => Mail::to($email)->send(new CompanyApprovedMail($company, $url, $email)),
            fn (\Throwable $e) => Log::error("PartnerApprovalNotifier: failed to send company approval mail (company #{$company->id}): {$e->getMessage()}"),
            report: false,
        );

        $company->forceFill(['approval_notified_at' => now()])->save();
    }
}
