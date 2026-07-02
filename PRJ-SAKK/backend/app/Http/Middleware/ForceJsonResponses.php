<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces every api/* request to be treated as a JSON request.
 *
 * Defensive guard (SEV-5): several first-party callers (e.g. the admin
 * integrations panel) send `Content-Type: application/json` without an
 * `Accept: application/json` header. Laravel's `expectsJson()`/`wantsJson()`
 * checks look at Accept, not Content-Type, so those requests were treated as
 * "expects HTML" — validation failures and redirects returned a 302 instead
 * of a JSON payload, silently losing the error/success message client-side.
 *
 * Registered on the `api` middleware group in bootstrap/app.php so every
 * api/* route gets a JSON-first Accept header regardless of what the caller
 * sent, before the request reaches routing/validation.
 */
class ForceJsonResponses
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('api/*')) {
            $request->headers->set('Accept', 'application/json');
        }

        return $next($request);
    }
}
