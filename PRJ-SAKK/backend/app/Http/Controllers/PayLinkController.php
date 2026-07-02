<?php

namespace App\Http\Controllers;

use App\Models\PaymentRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Public landing page for a payment-request link: {pay_url_base}/{uuid}.
 *
 * Two jobs (same pattern as InviteController):
 *  1) If the payer has the app installed AND the Android App Link is verified,
 *     the OS opens the app straight on the pay screen — this page never renders.
 *  2) Otherwise this page is the pretty fallback: it shows who is requesting,
 *     how much, the note, and an "open in app & pay" button (custom scheme)
 *     plus an APK download so the payer can install then pay.
 */
class PayLinkController extends Controller
{
    public function show(Request $request, string $uuid): View
    {
        $pr = PaymentRequest::with('user')->where('uuid', $uuid)->first();

        $status = null;
        if ($pr) {
            $status = $pr->status;
            if ($status === 'pending' && $pr->expires_at !== null && $pr->expires_at->isPast()) {
                $status = 'expired';
            }
        }

        $requester = $pr?->user;

        return view('pay', [
            'uuid'          => $uuid,
            'found'         => (bool) $pr,
            'status'        => $status,                    // pending|paid|expired|cancelled|rejected|null
            'amount'        => $pr ? $this->money((float) $pr->amount, $pr->currency) : null,
            'note'          => $pr?->note,
            'requesterName' => $requester?->full_name,
            'initials'      => $requester
                ? mb_strtoupper(mb_substr($requester->first_name ?? '', 0, 1) . mb_substr($requester->last_name ?? '', 0, 1))
                : null,
            'account'       => $requester ? 'SK' . str_pad((string) $requester->id, 8, '0', STR_PAD_LEFT) : null,
            // Root-relative so it always resolves against the serving host.
            'apkUrl'        => '/download/sakk.apk?v=1.0.0-2',
            // Custom-scheme fallback for an installed-but-unverified app.
            'appLink'       => 'sakk://pay/' . $uuid,
            // Redirect URLs for after payment is completed
            'successUrl'    => $pr?->success_url,
            'cancelUrl'     => $pr?->cancel_url,
        ]);
    }

    /** Display money the same way the app + notifications do. */
    private function money(float $amount, string $currency): string
    {
        return \App\Support\Money::format($amount, $currency);
    }
}
