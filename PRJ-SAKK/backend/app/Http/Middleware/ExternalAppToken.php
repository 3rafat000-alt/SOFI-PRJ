<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Dual-mode auth for payment-request endpoints.
 *
 * Accepts EITHER:
 *   1. SAKK_APP_TOKEN (M2M — TaskSync Pro) via Authorization: Bearer
 *   2. Any valid Sanctum personal access token (mobile app users)
 *
 * This middleware REPLACES auth:sanctum on payment-request routes.
 * It checks both auth methods and throws 401 if neither matches.
 */
class ExternalAppToken
{
    public function handle(Request $request, \Closure $next): mixed
    {
        // 0. If Sanctum guard already has a user (Sanctum::actingAs in tests),
        //    pass through immediately.
        if (Auth::guard('sanctum')->check()) {
            $request->setUserResolver(fn () => Auth::guard('sanctum')->user());
            return $next($request);
        }

        $token = $request->bearerToken();

        if ($token === null || $token === '') {
            throw new AuthenticationException('Unauthenticated.');
        }

        // 1. Try app token (M2M — TaskSync Pro)
        $expectedToken = config('services.app_token');
        if ($expectedToken !== null && $expectedToken !== '' && hash_equals($expectedToken, $token)) {
            $email = (string) config('services.service_user_email', 'tasksync@sakk.com');
            $user = User::where('email', $email)->first();

            if ($user === null) {
                throw new AuthenticationException('Service user not found.');
            }

            Auth::guard('sanctum')->setUser($user);
            $request->setUserResolver(fn () => $user);

            return $next($request);
        }

        // 2. Try Sanctum personal access token (mobile app users)
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        if ($personalAccessToken !== null) {
            $user = $personalAccessToken->tokenable;

            if ($user === null) {
                throw new AuthenticationException('Token user not found.');
            }

            if ($personalAccessToken->expires_at !== null && $personalAccessToken->expires_at->isPast()) {
                throw new AuthenticationException('Token expired.');
            }

            Auth::guard('sanctum')->setUser($user);
            $request->setUserResolver(fn () => $user);

            return $next($request);
        }

        throw new AuthenticationException('Unauthenticated.');
    }
}
