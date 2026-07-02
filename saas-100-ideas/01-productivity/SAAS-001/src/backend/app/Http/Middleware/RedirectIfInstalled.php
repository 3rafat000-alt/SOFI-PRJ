<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Installer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the installer routes — once installed, re-running the wizard is
 * blocked (would let anyone recreate an admin). Sends them to the landing page.
 */
class RedirectIfInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Installer::isInstalled()) {
            return redirect()->route('landing');
        }

        return $next($request);
    }
}
