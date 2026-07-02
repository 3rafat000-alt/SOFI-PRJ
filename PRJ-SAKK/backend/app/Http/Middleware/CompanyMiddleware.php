<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate + scope the company self-service portal.
 *
 * Resolves THE company owned by the authenticated user and binds it to the
 * request + shares it to views. Every portal controller reads this resolved
 * company — it must NEVER trust a company_id from the request body (IDOR guard).
 *
 * DESIGN TRUTH: the initial company application is submitted ONLY through the
 * Flutter mobile app. A logged-in user with no company record must NOT be sent to
 * an onboarding form on the web — instead they see a localized "no portal access
 * yet; apply via the app" page. No web path may create a Company record.
 */
class CompanyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('company.login');
        }

        $company = Company::where('user_id', auth()->id())->first();

        if (!$company) {
            return response()->view('portal.no-access', [
                'portalLabel' => 'شركة',
                'loginRoute'  => route('company.login'),
            ], 403);
        }

        // Bind the resolved company so controllers never read it from input.
        $request->attributes->set('company', $company);
        app()->instance('currentCompany', $company);
        view()->share('currentCompany', $company);

        // Shared portal chrome (sakk identity) for the layout.
        view()->share('portal', [
            'brand' => 'بوابة الشركات',
            'logout' => 'company.logout',
            'nav' => [
                ['route' => 'company.dashboard', 'match' => 'company.dashboard', 'label' => 'لوحة التحكم', 'icon' => '🏠'],
                ['route' => 'company.employees.index', 'match' => 'company.employees*', 'label' => 'الموظفون', 'icon' => '👥'],
                ['route' => 'company.wallet.index', 'match' => 'company.wallet*', 'label' => 'المحفظة', 'icon' => '👛'],
                ['route' => 'company.payroll.index', 'match' => 'company.payroll*', 'label' => 'الرواتب', 'icon' => '💸'],
            ],
            'entity' => [
                'name' => $company->name,
                'code' => $company->company_code,
                'status_label' => $company->kyc_status_label,
                'status_color' => $company->kyc_status_color,
            ],
        ]);

        return $next($request);
    }
}
