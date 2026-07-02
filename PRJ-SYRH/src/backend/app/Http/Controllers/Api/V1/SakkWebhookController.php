<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\AgencySubscription;
use App\Models\ChatMessage;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Services\SakkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handle SAKK PaymentRequest webhook callbacks
 *
 * SAKK fires this webhook when a payment request is marked as paid.
 * Payload (POST):
 *   Content-Type: application/json
 *   X-SAKK-Signature: hmac_sha256(json_body, callback_secret)
 *
 *   {
 *       "event": "payment_request.paid",
 *       "uuid": "string (uuid)",
 *       "status": "paid",
 *       "amount": float,
 *       "currency": "USD|SYP",
 *       "note": "string|null",
 *       "payer_name": "string|null",
 *       "paid_at": "ISO8601|null"
 *   }
 *
 * Flow: uuid → payment (from notes: plan_id, agency_id) → create subscription → activate
 */
class SakkWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('X-SAKK-Signature', '');

        $service = app(SakkService::class);

        // Verify HMAC-SHA256 signature (skip in dev mode when no secret configured)
        $webhookSecret = \App\Models\Setting::where('key', 'sakk_webhook_secret')->value('value');
        if (!empty($webhookSecret) && !$service->verifyWebhookSignature($payload, $signature)) {
            Log::warning('SAKK webhook: invalid signature', [
                'ip' => $request->ip(),
                'header_signature' => $signature ? substr($signature, 0, 12) . '…' : '(empty)',
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        if (empty($webhookSecret)) {
            Log::info('SAKK webhook: signature check skipped (dev mode, no secret configured)');
        }

        // Parse payload
        $data = json_decode($payload, true);
        if (!$data || !is_array($data)) {
            Log::warning('SAKK webhook: invalid JSON payload');
            return response()->json(['error' => 'Invalid JSON'], 400);
        }

        // Validate required fields
        $event = $data['event'] ?? '';
        $uuid  = $data['uuid'] ?? '';

        if ($event !== 'payment_request.paid' || empty($uuid)) {
            Log::warning('SAKK webhook: unexpected event or missing uuid', [
                'event' => $event,
                'uuid'  => $uuid,
            ]);
            // Acknowledge receipt so SAKK doesn't retry, but don't process
            return response()->json(['status' => 'ignored']);
        }

        // Find payment by SAKK uuid (stored in transaction_id)
        $payment = Payment::where('transaction_id', $uuid)->first();

        if (!$payment) {
            Log::warning('SAKK webhook: payment not found for uuid', [
                'uuid' => $uuid,
            ]);
            // Acknowledge — SAKK won't retry; manual reconciliation needed
            return response()->json(['status' => 'not_found'], 200);
        }

        // Idempotent: skip if already completed
        if ($payment->status === 'completed') {
            Log::info('SAKK webhook: payment already processed, skipped', [
                'uuid'            => $uuid,
                'payment_id'      => $payment->id,
                'subscription_id' => $payment->agency_subscription_id,
            ]);
            return response()->json(['status' => 'already_processed']);
        }

        // Parse notes to get payment type and details
        $notes = $payment->notes ?? [];
        $paymentType = $notes['type'] ?? 'subscription';

        if ($paymentType === 'escrow') {
            // ─── Escrow Payment (chat payment request) ───
            $escrowType = $notes['escrow_type'] ?? 'sale';
            $conversationId = $notes['conversation_id'] ?? null;
            $propertyId = $notes['property_id'] ?? null;

            // Calculate release time
            $releaseHours = match ($escrowType) {
                'rental_operation' => 3,
                'rent'             => 336, // 14 days
                default            => 72,  // 3 days (sale)
            };

            $paidAt = isset($data['paid_at']) ? new \DateTime($data['paid_at']) : now();
            $releaseAt = (clone $paidAt)->modify("+{$releaseHours} hours");

            DB::beginTransaction();
            try {
                $payment->update([
                    'status'  => 'paid',
                    'paid_at' => $paidAt,
                    'notes'   => array_merge(
                        $notes,
                        [
                            'webhook_payload' => $data,
                            'release_at'      => $releaseAt->format('Y-m-d H:i:s'),
                        ]
                    ),
                ]);

                // Update payment request message in chat
                if ($conversationId) {
                    $chatMsg = ChatMessage::where('conversation_id', $conversationId)
                        ->where('message_type', 'payment_request')
                        ->where('metadata->payment_id', $payment->id)
                        ->first();

                    if ($chatMsg) {
                        $meta = $chatMsg->metadata ?? [];
                        $meta['status'] = 'paid';
                        $meta['paid_at'] = $paidAt->format('c');
                        $chatMsg->update(['metadata' => $meta]);
                    }
                }

                DB::commit();

                Log::info('SAKK escrow payment completed — held in escrow', [
                    'uuid'            => $uuid,
                    'payment_id'      => $payment->id,
                    'escrow_type'     => $escrowType,
                    'release_at'      => $releaseAt->format('Y-m-d H:i:s'),
                    'conversation_id' => $conversationId,
                ]);

                return response()->json(['status' => 'escrow_held']);
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('SAKK webhook: escrow transaction failed', [
                    'uuid'       => $uuid,
                    'payment_id' => $payment->id,
                    'error'      => $e->getMessage(),
                ]);
                return response()->json(['status' => 'error'], 500);
            }
        }

        // ─── Subscription Payment (existing flow) ───
        $planId = $notes['plan_id'] ?? null;
        $agencyId = $notes['agency_id'] ?? null;

        if (!$planId || !$agencyId) {
            Log::error('SAKK webhook: payment notes missing plan_id or agency_id', [
                'uuid'       => $uuid,
                'payment_id' => $payment->id,
                'notes'      => $payment->notes,
            ]);
            return response()->json(['status' => 'invalid_notes'], 200);
        }

        $plan = SubscriptionPlan::find($planId);
        if (!$plan) {
            Log::error('SAKK webhook: plan not found', [
                'plan_id' => $planId,
            ]);
            return response()->json(['status' => 'plan_not_found'], 200);
        }

        DB::beginTransaction();
        try {
            // Cancel existing active/trial subscriptions for this agency
            AgencySubscription::where('agency_id', $agencyId)
                ->whereIn('status', ['active', 'trial'])
                ->update([
                    'status'       => 'cancelled',
                    'cancelled_at' => now(),
                ]);

            // Create subscription with active status
            $subscription = AgencySubscription::create([
                'agency_id'     => $agencyId,
                'plan_id'       => $plan->id,
                'start_at'      => now(),
                'end_at'        => now()->addDays($plan->duration_days),
                'status'        => 'active',
                'trial_ends_at' => null,
                'payment_method'=> 'sakk',
            ]);

            // Link payment to subscription
            $payment->update([
                'status'                  => 'completed',
                'paid_at'                 => $data['paid_at'] ?? now(),
                'agency_subscription_id'  => $subscription->id,
                'notes'                   => array_merge(
                    $notes,
                    ['webhook_payload' => $data]
                ),
            ]);

            DB::commit();

            Log::info('SAKK payment completed — subscription created and activated', [
                'uuid'            => $uuid,
                'payment_id'      => $payment->id,
                'subscription_id' => $subscription->id,
                'agency_id'       => $subscription->agency_id,
                'plan_id'         => $subscription->plan_id,
            ]);

            return response()->json(['status' => 'activated']);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('SAKK webhook: transaction failed', [
                'uuid'       => $uuid,
                'payment_id' => $payment->id,
                'error'      => $e->getMessage(),
            ]);
            return response()->json(['status' => 'error'], 500);
        }
    }
}
