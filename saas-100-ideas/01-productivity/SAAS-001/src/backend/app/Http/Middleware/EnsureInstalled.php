<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Installer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks the app (landing, admin, SPA shell) until the installer has run.
 * Redirects everything to /install while the lock file is absent.
 */
class EnsureInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Installer::isInstalled()) {
            return redirect()->route('install.index');
        }

        return $next($request);
    }
}
