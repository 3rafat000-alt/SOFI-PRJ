<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds defensive HTTP security headers to every response.
 *
 * Registered as a global middleware (web + api groups) in bootstrap/app.php.
 * The Content-Security-Policy keeps a `default-src 'self'` baseline but allows
 * inline script/style because the admin Blade UI and error pages ship inline
 * <script>/<style>/style= and on* handlers; tightening those to 'self' would
 * break existing behaviour. 'unsafe-eval' is required because the admin panel
 * uses the standard Alpine.js build (not @alpinejs/csp) — the CSP build
 * cannot evaluate assignment expressions like show = false in event handlers.
 * HSTS is only emitted over HTTPS so local HTTP dev is never pinned to TLS.
 */
class SecurityHeaders
{
    /**
     * Content-Security-Policy directives.
     *
     * `default-src 'self'` is the baseline; script/style permit 'unsafe-inline'
      * to preserve the existing inline-asset behaviour of the admin panel.
      * connect-src includes jsdelivr for Chart.js sourcemap fetches.
     */
    private const CSP = "default-src 'self'; "
        ."script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdn.jsdelivr.net https://maps.googleapis.com https://maps.gstatic.com https://static.cloudflareinsights.com; "
        ."style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
        ."img-src 'self' data: blob: https:; "
        ."font-src 'self' data: https://fonts.gstatic.com; "
        ."connect-src 'self' https://cdn.jsdelivr.net https://maps.googleapis.com https://maps.gstatic.com https://static.cloudflareinsights.com; "
        ."worker-src 'self' blob:; "
        ."base-uri 'self'; "
        ."form-action 'self'; "
        ."frame-ancestors 'none'";

    /**
     * Permissions-Policy: disable powerful features by default.
     */
    private const PERMISSIONS_POLICY = 'camera=(), microphone=(), geolocation=()';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = $response->headers;

        // Do not clobber a stricter policy a controller may have set deliberately.
        if (! $headers->has('Content-Security-Policy')) {
            $headers->set('Content-Security-Policy', self::CSP);
        }

        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', self::PERMISSIONS_POLICY);

        // Only pin HTTPS clients to TLS; never emit HSTS on plain-HTTP dev.
        if ($request->isSecure()) {
            $headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }
}
