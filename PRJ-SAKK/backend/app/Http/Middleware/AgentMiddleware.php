<?php

namespace App\Http\Middleware;

use App\Models\Agent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate + scope the agent self-service portal. Resolves THE agent owned by the
 * authenticated user, binds it, and shares the sakk portal chrome.
 *
 * DESIGN TRUTH: the initial agent application is submitted ONLY through the
 * Flutter mobile app. A logged-in user with no agent record must NOT be sent to
 * an onboarding form on the web — instead they see a localized "no portal access
 * yet; apply via the app" page. No web path may create an Agent record.
 */
class AgentMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('agent.login');
        }

        $agent = Agent::where('user_id', auth()->id())->first();

        if (!$agent) {
            return response()->view('portal.no-access', [
                'portalLabel' => 'وكيل',
                'loginRoute'  => route('agent.login'),
            ], 403);
        }

        $request->attributes->set('agent', $agent);
        view()->share('currentAgent', $agent);
        view()->share('portal', [
            'brand' => 'بوابة الوكلاء',
            'logout' => 'agent.logout',
            'nav' => [
                ['route' => 'agent.dashboard', 'match' => 'agent.dashboard', 'label' => 'لوحة التحكم', 'icon' => '🏠'],
                ['route' => 'agent.profile', 'match' => 'agent.profile', 'label' => 'ملف الوكيل', 'icon' => '🧭'],
                ['route' => 'agent.documents', 'match' => 'agent.documents', 'label' => 'المستندات', 'icon' => '📄'],
            ],
            'entity' => [
                'name' => $agent->name,
                'code' => $agent->agent_code,
                'status_label' => $agent->kyc_status_label,
                'status_color' => $agent->kyc_status_color,
            ],
        ]);

        return $next($request);
    }
}
