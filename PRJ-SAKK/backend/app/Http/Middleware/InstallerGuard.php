<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class InstallerGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('testing')) {
            return $next($request);
        }

        // Check marker file first, fall back to database + app key
        $installed = File::exists(storage_path('installed'));
        if (!$installed && config('app.key')) {
            try {
                DB::connection()->getPdo();
                $installed = true;
            } catch (\Exception) {
                $installed = false;
            }
        }

        // Auto-create marker if DB is reachable
        if ($installed && !File::exists(storage_path('installed'))) {
            @file_put_contents(storage_path('installed'), date('c'));
        }

        // Already installed → block install routes (except complete page), let everything else through
        if ($installed) {
            if ($request->is('install') || $request->is('install/*')) {
                if ($request->routeIs('installer.complete')) {
                    return $next($request);
                }
                return redirect('/');
            }
            return $next($request);
        }

        // Not installed → only allow install routes
        if ($request->is('install') || $request->is('install/*')) {
            return $next($request);
        }

        // API routes — return JSON
        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'التطبيق غير مثبت بعد. قم بزيارة /install لإكمال التنصيب.',
            ], 503);
        }

        if ($request->is('up') || $request->is('health')) {
            return response()->json(['status' => 'not_installed'], 503);
        }

        return redirect()->route('installer.requirements');
    }
}
