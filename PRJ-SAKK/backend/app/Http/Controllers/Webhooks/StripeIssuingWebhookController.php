<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessStripeIssuingWebhook;
use App\Services\StripeIssuingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Stripe Issuing Webhook Controller
 *
 * CRITICAL: issuing_authorization.request has 2-second timeout.
 * Must approve/decline within 2 seconds or Stripe auto-declines.
 * All other event types are dispatched to the queue and return 200
 * immediately to avoid holding the webhook connection.
 */
class StripeIssuingWebhookController extends Controller
{
    public function __construct(
        protected StripeIssuingService $stripeService
    ) {}

    /**
     * Handle all Stripe Issuing webhooks
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        if (!$this->stripeService->verifyWebhookSignature($payload, $signature ?? '')) {
            Log::warning('Invalid Stripe webhook signature');
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $event = json_decode($payload, true);
        $eventType = $event['type'] ?? 'unknown';
        $eventId = $event['id'] ?? null;
        $eventData = $event['data']['object'] ?? [];

        Log::info('Stripe webhook received', [
            'type' => $eventType,
            'id' => $eventId ?? 'unknown',
        ]);

        // SEC H4: idempotency. Stripe may resend a signed event (delivery retries,
        // or a captured payload replayed within the 5-minute signature window). Claim
        // each event id exactly once (atomic Cache::add) so money-moving handlers —
        // authorization capture/reversal credit the wallet — can never run twice.
        // issuing_authorization.request is exempt: it must always answer synchronously.
        if ($eventId !== null && $eventType !== 'issuing_authorization.request') {
            if (!Cache::add("stripe_evt:{$eventId}", true, now()->addDays(3))) {
                Log::info('Stripe webhook: duplicate event ignored', [
                    'id' => $eventId,
                    'type' => $eventType,
                ]);
                return response()->json(['received' => true, 'duplicate' => true]);
            }
        }

        // issuing_authorization.request is the only event Stripe waits on for a
        // synchronous approve/decline decision — it must respond within 2 seconds.
        // All other events are dispatched to the queue and return 200 immediately.
        if ($eventType === 'issuing_authorization.request') {
            return $this->handleAuthorizationRequest($eventData);
        }

        ProcessStripeIssuingWebhook::dispatch($eventType, $eventData);

        return response()->json(['received' => true]);
    }

    /**
     * Handle real-time authorization request
     * CRITICAL: Must respond within 2 seconds
     */
    protected function handleAuthorizationRequest(array $authorization): JsonResponse
    {
        $result = $this->stripeService->handleAuthorizationRequest($authorization);

        if ($result['approved']) {
            return response()->json([
                'approved' => true,
            ]);
        }

        return response()->json([
            'approved' => false,
            'decline_reason' => $this->mapDeclineReason($result['reason'] ?? 'unknown'),
        ]);
    }

    /**
     * Map internal decline reasons to Stripe decline reasons
     */
    protected function mapDeclineReason(string $reason): string
    {
        return match ($reason) {
            'insufficient_funds' => 'insufficient_funds',
            'card_inactive', 'card_frozen' => 'card_inactive',
            'spending_limit_exceeded' => 'spending_controls',
            'merchant_blocked' => 'webhook_declined',
            'international_disabled' => 'webhook_declined',
            'card_not_found' => 'card_inactive',
            'system_error' => 'webhook_error',
            default => 'webhook_declined',
        };
    }
}
