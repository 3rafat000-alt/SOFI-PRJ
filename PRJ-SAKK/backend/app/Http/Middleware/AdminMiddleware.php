<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || !auth()->user()->is_admin) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }
            
            return redirect()->route('admin.login')
                ->with('error', 'You do not have admin access.');
        }

        return $next($request);
    }

    /**
     * Defence-in-depth ability gate callable directly from controllers that serve
     * sensitive data. The 'admin' route middleware already guarantees an
     * authenticated admin; controllers re-assert the ability so a future route
     * change can never silently de-gate identity PII. Aborts 403 when the current
     * user is not an authenticated admin.
     *
     * @param string $ability Reserved for future granular abilities; today every
     *                         admin holds every ability.
     */
    public static function authorize(string $ability): void
    {
        if (!auth()->check() || !auth()->user()->is_admin) {
            abort(403);
        }
    }
}
