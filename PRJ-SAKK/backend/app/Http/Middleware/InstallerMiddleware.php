<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class InstallerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // If already installed, redirect to home (except for complete page)
        if (File::exists(storage_path('installed')) && !$request->routeIs('installer.complete')) {
            return redirect('/');
        }

        return $next($request);
    }
}
