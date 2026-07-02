<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    public function __construct(
        private RateLimiter $limiter,
    ) {}

    /**
     * Apply rate limiting per route group.
     *
     * Usage in routes: ->middleware('ratelimit:api')
     * Groups: api (300/min), auth (60/min), reports (30/min), uploads (10/min), invites (20/hour)
     */
    public function handle(Request $request, Closure $next, string $group = 'api'): Response
    {
        $key = $this->resolveKey($request, $group);
        $limits = $this->getLimits($group);

        /** @var array{maxAttempts: int, decaySeconds: int} $limits */

        if ($this->limiter->tooManyAttempts($key, $limits['maxAttempts'])) {
            $retryAfter = $this->limiter->availableIn($key);

            return new JsonResponse([
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => 'Too many requests. Please slow down.',
                    'details' => [
                        'retry_after_seconds' => $retryAfter,
                        'limit' => $limits['maxAttempts'],
                        'remaining' => 0,
                        'resets_at' => now()->addSeconds($retryAfter)->toIso8601String(),
                    ],
                ],
            ], 429, [
                'X-RateLimit-Limit' => (string) $limits['maxAttempts'],
                'X-RateLimit-Remaining' => '0',
                'X-RateLimit-Reset' => (string) now()->addSeconds($retryAfter)->unix(),
                'Retry-After' => (string) $retryAfter,
            ]);
        }

        $this->limiter->hit($key, $limits['decaySeconds']);

        $response = $next($request);

        $remaining = max(0, $limits['maxAttempts'] - $this->limiter->attempts($key));

        $response->headers->set('X-RateLimit-Limit', (string) $limits['maxAttempts']);
        $response->headers->set('X-RateLimit-Remaining', (string) $remaining);
        $response->headers->set('X-RateLimit-Reset', (string) now()->addSeconds($limits['decaySeconds'])->unix());

        return $response;
    }

    /**
     * Resolve the rate limit key based on group and request.
     */
    private function resolveKey(Request $request, string $group): string
    {
        $user = $request->user();

        $identifier = match ($group) {
            'auth' => 'auth-'.$request->ip(),
            'api' => $user ? 'api-'.$user->id : 'api-'.$request->ip(),
            'reports' => $user ? 'reports-'.$user->id : 'reports-'.$request->ip(),
            'uploads' => $user ? 'upload-'.$user->id : 'upload-'.$request->ip(),
            'invites' => $user ? 'invite-'.$user->id : 'invite-'.$request->ip(),
            default => $user ? $user->id : $request->ip(),
        };

        return 'ratelimit:'.$identifier;
    }

    /**
     * @return array{maxAttempts: int, decaySeconds: int}
     */
    private function getLimits(string $group): array
    {
        return match ($group) {
            'auth' => ['maxAttempts' => 60, 'decaySeconds' => 60],
            'reports' => ['maxAttempts' => 30, 'decaySeconds' => 60],
            'uploads' => ['maxAttempts' => 10, 'decaySeconds' => 60],
            'invites' => ['maxAttempts' => 20, 'decaySeconds' => 3600],
            default => ['maxAttempts' => 300, 'decaySeconds' => 60],
        };
    }
}
