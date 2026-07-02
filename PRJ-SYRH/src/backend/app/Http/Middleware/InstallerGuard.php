<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Global middleware: blocks all traffic until installation is complete.
 *
 * - Installed → pass through
 * - Not installed + install route → allow
 * - Not installed + API (expects JSON) → 503 JSON
 * - Not installed + web → redirect to /install
 */
class InstallerGuard
{
    private const INSTALL_PATHS = [
        'api/v1/install',
        'install',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $installed = file_exists(storage_path('installed'));

        if ($installed) {
            return $next($request);
        }

        // Allow install paths through
        $path = $request->path();
        foreach (self::INSTALL_PATHS as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return $next($request);
            }
        }

        // Allow health check
        if ($path === 'up' || $path === 'health') {
            return response()->json(['status' => 'not_installed'], 503);
        }

        // API calls → JSON response
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'المنصة غير مثبتة بعد. يرجى إكمال عملية التثبيت.',
            ], 503);
        }

        // Web → redirect to installer
        return redirect()->to('/install');
    }
}
