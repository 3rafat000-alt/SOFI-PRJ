<?php

namespace App\Http\Middleware;

use App\Models\Merchant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate + scope the merchant self-service portal. Resolves THE merchant owned by
 * the authenticated user, binds it, and shares the sakk portal chrome.
 *
 * DESIGN TRUTH: the initial merchant application is submitted ONLY through the
 * Flutter mobile app. A logged-in user with no merchant record must NOT be sent to
 * an onboarding form on the web — instead they see a localized "no portal access
 * yet; apply via the app" page. No web path may create a Merchant record.
 */
class MerchantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('merchant.login');
        }

        $merchant = Merchant::where('user_id', auth()->id())->first();

        if (!$merchant) {
            return response()->view('portal.no-access', [
                'portalLabel' => 'تاجر',
                'loginRoute'  => route('merchant.login'),
            ], 403);
        }

        $request->attributes->set('merchant', $merchant);
        view()->share('currentMerchant', $merchant);
        view()->share('portal', [
            'brand' => 'بوابة التجار',
            'logout' => 'merchant.logout',
            'nav' => [
                ['route' => 'merchant.dashboard', 'match' => 'merchant.dashboard', 'label' => 'لوحة التحكم', 'icon' => '🏠'],
                ['route' => 'merchant.profile', 'match' => 'merchant.profile', 'label' => 'الملف التجاري', 'icon' => '🏪'],
                ['route' => 'merchant.documents', 'match' => 'merchant.documents', 'label' => 'المستندات', 'icon' => '📄'],
            ],
            'entity' => [
                'name' => $merchant->store_name,
                'code' => $merchant->merchant_code,
                'status_label' => $merchant->kyc_status_label,
                'status_color' => $merchant->kyc_status_color,
            ],
        ]);

        return $next($request);
    }
}
