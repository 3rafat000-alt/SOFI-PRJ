<?php

namespace App\Http\Middleware;

use App\Support\CardsFeature;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks the entire virtual-cards surface while the feature is disabled
 * (Stripe Issuing not yet configured). Returns a structured 503 the app
 * renders as a "coming soon" state instead of a hard error.
 */
class EnsureCardsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!CardsFeature::enabled()) {
            return response()->json([
                'success' => false,
                'code' => 'cards_disabled',
                'message' => CardsFeature::disabledMessage(),
            ], 503);
        }

        return $next($request);
    }
}
