<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Public landing page for a referral invite link: {invite_url_base}/{code}.
 *
 * Two jobs:
 *  1) If the visitor already has the app installed AND the Android App Link is
 *     verified, the OS opens the app directly and this page never renders.
 *  2) Otherwise this page is the fallback — it greets the invitee, shows the
 *     referral code, offers the APK download, and an "open in app" button
 *     (custom scheme) so the code can be carried into registration.
 */
class InviteController extends Controller
{
    public function __construct(private readonly ReferralService $referralService) {}

    public function show(Request $request, string $code): View
    {
        $code = strtoupper(trim(ltrim($code, '@#')));

        $referrer = User::whereRaw('UPPER(referral_code) = ?', [$code])->first();

        return view('invite', [
            'code'        => $code,
            'valid'       => (bool) $referrer,
            'inviterName' => $referrer?->full_name,
            'reward'      => $this->referralService->rewardAmount(),
            'currency'    => ReferralService::REWARD_CURRENCY,
            // Root-relative so it always resolves against the serving host
            // (sakk.zanjour.com) — never the branded APP_URL placeholder.
            'apkUrl'      => '/download/sakk.apk?v=1.0.0-2',
            // Custom-scheme fallback for an installed-but-unverified app.
            'appLink'     => 'sakk://invite/' . $code,
        ]);
    }
}
