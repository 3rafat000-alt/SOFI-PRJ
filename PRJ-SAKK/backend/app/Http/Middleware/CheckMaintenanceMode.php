<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces the admin-facing "maintenance_mode" SystemSetting toggle.
 *
 * Laravel's own down/up (storage/framework/down) is a separate, ops-level
 * switch that already works via app()->isDownForMaintenance(). This
 * middleware wires the SEPARATE database toggle exposed on
 * /admin/settings (SystemSetting::get('maintenance_mode')), which was
 * previously read nowhere and therefore had zero effect.
 *
 * Admin panel routes and the admin auth (login/logout) routes are always
 * allowed through so the admin can toggle the flag back off — the switch
 * must never be able to lock the operator out.
 */
class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!SystemSetting::get('maintenance_mode', false)) {
            return $next($request);
        }

        // Never block the admin panel itself (guest login routes or the
        // authenticated admin area, on both the web and API surfaces) — that
        // is how the flag gets switched back off.
        if ($request->is('admin', 'admin/*', 'api/admin', 'api/admin/*')) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'code' => 'maintenance_mode',
                'message' => 'التطبيق قيد الصيانة حالياً. يرجى المحاولة لاحقاً.',
            ], 503);
        }

        abort(503, 'التطبيق قيد الصيانة حالياً. يرجى المحاولة لاحقاً.');
    }
}
