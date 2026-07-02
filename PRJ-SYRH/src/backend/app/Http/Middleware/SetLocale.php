<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the application locale for each API request.
 *
 * Priority: ?lang= query param → Accept-Language header → default 'ar'.
 * Whitelisted locales: ['ar', 'en'].
 *
 * Security note: the locale value is strictly whitelisted before being applied;
 * no user-supplied string is passed to app()->setLocale() without validation,
 * which prevents locale-injection path traversal (e.g. ../../etc/passwd via
 * locale-sensitive file includes).
 *
 * BOOTSTRAP REGISTRATION (Laravel 12 — add to bootstrap/app.php):
 *
 *   ->withMiddleware(function (Middleware $middleware) {
 *       $middleware->appendToGroup('api', \App\Http\Middleware\SetLocale::class);
 *   })
 */
class SetLocale
{
    /** @var list<string> Supported locales. */
    private const WHITELIST = ['ar', 'en'];

    /** @var string Fallback locale when no valid signal is found. */
    private const DEFAULT = 'ar';

    /**
     * Handle the incoming request, resolve and set the application locale.
     *
     * @param  Request  $request
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);

        return $next($request);
    }

    /**
     * Resolve the locale from the request using a defined priority chain.
     *
     * 1. ?lang= query param (explicit client override).
     * 2. First acceptable locale found in the Accept-Language header.
     * 3. Default: 'ar'.
     */
    private function resolveLocale(Request $request): string
    {
        // 1. Explicit query param — highest priority.
        $param = $request->query('lang');
        if (is_string($param) && in_array($param, self::WHITELIST, true)) {
            return $param;
        }

        // 2. Accept-Language header — parse and match against whitelist.
        $header = $request->header('Accept-Language', '');
        if (is_string($header) && $header !== '') {
            foreach ($this->parseAcceptLanguage($header) as $tag) {
                // Match primary language subtag only (e.g. 'ar' from 'ar-SY').
                $primary = strtolower(explode('-', $tag)[0]);
                if (in_array($primary, self::WHITELIST, true)) {
                    return $primary;
                }
            }
        }

        // 3. Default.
        return self::DEFAULT;
    }

    /**
     * Parse an Accept-Language header value into an ordered list of language tags.
     * Sorts by q-value descending (RFC 7231 §5.3.5).
     *
     * @return list<string>
     */
    private function parseAcceptLanguage(string $header): array
    {
        $parts = array_map('trim', explode(',', $header));
        $weighted = [];

        foreach ($parts as $part) {
            if (preg_match('/^([a-zA-Z\-]+)(?:;q=([0-9.]+))?$/', trim($part), $m)) {
                $weighted[] = [
                    'tag' => $m[1],
                    'q'   => isset($m[2]) ? (float) $m[2] : 1.0,
                ];
            }
        }

        usort($weighted, static fn (array $a, array $b): int => $b['q'] <=> $a['q']);

        return array_column($weighted, 'tag');
    }
}
