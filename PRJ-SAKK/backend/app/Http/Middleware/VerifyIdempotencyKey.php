<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-side idempotency guard for money-mutating endpoints (W-SEV-5, item 5).
 *
 * Reads an `X-Idempotency-Key` header (client-generated UUID) on the guarded
 * route. A duplicate in-flight request for the same user+key gets a 409
 * Conflict instead of re-running the underlying money code; a request that
 * already completed replays the stored response byte-for-byte instead of
 * re-executing anything.
 *
 * Two modes, selected via a middleware parameter so one class covers every
 * money-out route without forking behavior:
 *   - `idempotency:required` (crypto withdraw, routes/api.php ->
 *     POST /ccpayment/withdraw) — missing/malformed key = 400. Unchanged
 *     from the original W-SEV-5 fix; do not loosen this route.
 *   - `idempotency` (default, no parameter — transfer, generic wallet
 *     withdraw) — missing key = fail OPEN (request proceeds untouched, logged
 *     at debug) so existing mobile clients that don't send the header yet
 *     keep working. A malformed key is still rejected (cheap to send right).
 *     Sending a valid key gets the same dedupe/replay/409 protection.
 */
class VerifyIdempotencyKey
{
    /** How long a completed response stays replayable for the same key. */
    private const RESPONSE_TTL_SECONDS = 86400; // 24h

    /** How long the atomic lock is held while the real request is in flight. */
    private const LOCK_TTL_SECONDS = 120;

    /** How long to wait to acquire the lock before giving up (no blocking retry loop). */
    private const LOCK_WAIT_SECONDS = 0;

    public function handle(Request $request, Closure $next, string $mode = 'optional'): Response
    {
        $key = $request->header('X-Idempotency-Key');
        $user = $request->user();

        if (!$key || !$user) {
            if ($mode !== 'required') {
                // Fail open: no key (or unauthenticated context) — proceed
                // without dedupe protection, don't break legacy callers.
                logger()->debug('idempotency.key_missing_fail_open', [
                    'route' => $request->route()?->getName(),
                    'user_id' => $user?->id,
                ]);

                return $next($request);
            }

            return response()->json([
                'success' => false,
                'message' => 'ترويسة X-Idempotency-Key مطلوبة.',
                'code' => 'idempotency_key_required',
            ], 400);
        }

        if (!preg_match('/^[a-f0-9\-]{8,64}$/i', $key)) {
            return response()->json([
                'success' => false,
                'message' => 'صيغة X-Idempotency-Key غير صحيحة.',
                'code' => 'idempotency_key_invalid',
            ], 400);
        }

        $cacheKey = 'idempotency:' . $request->route()?->getName() . ':' . $user->id . ':' . $key;
        $responseKey = $cacheKey . ':response';

        // A prior request with this exact key already completed — replay its
        // stored response without touching any money code.
        if ($stored = Cache::get($responseKey)) {
            return response()->json($stored['body'], $stored['status'])
                ->header('X-Idempotency-Replayed', 'true');
        }

        $lock = Cache::lock($cacheKey, self::LOCK_TTL_SECONDS);

        if (!$lock->get()) {
            // Another request with the same user+key is currently in flight.
            return response()->json([
                'success' => false,
                'message' => 'طلب مطابق قيد المعالجة بالفعل.',
                'code' => 'duplicate_request_in_flight',
            ], 409);
        }

        try {
            /** @var Response $response */
            $response = $next($request);

            // Only cache genuinely-final responses (2xx/4xx) so a transient
            // 5xx doesn't get permanently replayed to a retrying client.
            if ($response->getStatusCode() < 500 && $response instanceof \Illuminate\Http\JsonResponse) {
                Cache::put($responseKey, [
                    'status' => $response->getStatusCode(),
                    'body' => $response->getData(true),
                ], self::RESPONSE_TTL_SECONDS);
            }

            return $response;
        } finally {
            $lock->release();
        }
    }
}
