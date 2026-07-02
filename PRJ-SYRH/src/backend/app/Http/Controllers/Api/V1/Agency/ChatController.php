<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Agency;

use App\Events\NewMessage;
use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Payment;
use App\Services\SakkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    /**
     * Wrap broadcast event in try-catch so Reverb failure never crashes HTTP response.
     * Message already saved — broadcast is best-effort.
     */
    private function tryBroadcast($event): void
    {
        try {
            event($event);
        } catch (\Pusher\PusherException $e) {
            logger()->warning('Broadcast failed (message saved, real-time delayed): ' . $e->getMessage());
        } catch (\Throwable $e) {
            logger()->warning('Broadcast failed: ' . $e->getMessage());
        }
    }

    private function getAgency(Request $request): \App\Models\Agency
    {
        $user = $request->user();
        $agency = $user->agency;
        if (!$agency && $user->isAgencyOwner()) {
            $agency = $user->agent?->agency;
        }
        abort_if(!$agency, 403, 'No agency found');
        return $agency;
    }

    public function conversations(Request $request): JsonResponse
    {
        $agency = $this->getAgency($request);

        $conversations = Conversation::where('agency_id', $agency->id)
            ->with(['latestMessage', 'property:id,slug,title_ar,title_en,price,currency,purpose,status', 'user:id,name,avatar_url'])
            ->withCount(['unreadClientMessages'])
            ->orderByDesc('updated_at')
            ->paginate(20);

        return response()->json(['data' => $conversations->items(), 'meta' => [
            'total'        => $conversations->total(),
            'per_page'     => $conversations->perPage(),
            'current_page' => $conversations->currentPage(),
            'last_page'    => $conversations->lastPage(),
        ]]);
    }

    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        $agency = $this->getAgency($request);
        abort_if($conversation->agency_id !== $agency->id, 403);

        $messages = ChatMessage::where('conversation_id', $conversation->id)
            ->orderBy('created_at')
            ->paginate(50);

        return response()->json(['data' => $messages->items(), 'meta' => [
            'total'        => $messages->total(),
            'per_page'     => $messages->perPage(),
            'current_page' => $messages->currentPage(),
            'last_page'    => $messages->lastPage(),
        ]]);
    }

    public function storeMessage(Request $request, Conversation $conversation): JsonResponse
    {
        $agency = $this->getAgency($request);
        abort_if($conversation->agency_id !== $agency->id, 403);

        $validated = $request->validate([
            'message'      => 'nullable|string|max:5000',
            'attachment'   => 'nullable|file|mimes:jpeg,png,webp,gif,pdf,doc,docx|max:10240',
            'attachments'  => 'nullable|array',
            'attachments.*' => 'file|mimes:jpeg,png,webp,gif,pdf,doc,docx|max:10240',
        ]);

        if (!$request->has('message') && !$request->hasFile('attachment') && !$request->hasFile('attachments')) {
            return response()->json(['message' => 'Message or attachment required'], 422);
        }

        $data = [
            'conversation_id' => $conversation->id,
            'sender_type'     => 'agency',
            'sender_id'       => $request->user()->id,
            'message'         => $validated['message'] ?? '',
        ];

        // Handle single attachment (backward compat)
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('chat-attachments', 'public');
            $data['attachment_path'] = $path;
            $data['attachment_type'] = $file->getMimeType();
            $data['attachment_name'] = $file->getClientOriginalName();
            $data['attachment_size'] = $file->getSize();
        }

        // Handle multiple attachments
        if ($request->hasFile('attachments')) {
            $attachments = [];
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('chat-attachments', 'public');
                $attachments[] = [
                    'path' => $path,
                    'type' => $file->getMimeType(),
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                ];
            }
            $data['attachments'] = $attachments;
        }

        $msg = ChatMessage::create($data);
        $this->tryBroadcast(new NewMessage($msg, $conversation->id, 'agency'));
        $conversation->touch();

        return response()->json(['data' => $msg->fresh()], 201);
    }

    public function markAsRead(Request $request, Conversation $conversation): JsonResponse
    {
        $agency = $this->getAgency($request);
        abort_if($conversation->agency_id !== $agency->id, 403);

        ChatMessage::where('conversation_id', $conversation->id)
            ->where('sender_type', 'client')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Messages marked as read']);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $agency = $this->getAgency($request);

        $count = ChatMessage::whereIn('conversation_id', 
            Conversation::where('agency_id', $agency->id)->select('id')
        )->where('sender_type', 'client')->whereNull('read_at')->count();

        return response()->json(['data' => ['unread_count' => $count]]);
    }

    /**
     * Send a SAKK payment request in chat (escrow).
     * Agency sends a payment link to the client.
     */
    public function sendPaymentRequest(Request $request, Conversation $conversation): JsonResponse
    {
        $agency = $this->getAgency($request);
        abort_if($conversation->agency_id !== $agency->id, 403);

        // Security: validate agency has SAKK account connected
        if (!$agency->sakk_merchant_id || !$agency->sakk_verified) {
            return response()->json(['message' => 'SAKK account not connected or verified. Link your SAKK account first.'], 403);
        }

        // Security: validate property belongs to agency if set
        if ($conversation->property_id) {
            $property = $conversation->property;
            abort_if($property && $property->agency_id !== $agency->id, 403);
        }

        $validated = $request->validate([
            'amount'      => 'required|numeric|min:1|max:999999999.99',
            'currency'    => 'required|string|max:5',
            'escrow_type' => 'required|in:sale,rent,rental_operation',
            'note'        => 'nullable|string|max:500',
        ]);

        // Create payment record (notes as array — cast handles JSON encoding)
        $payment = Payment::create([
            'agency_id' => $agency->id,
            'amount'    => $validated['amount'],
            'currency'  => $validated['currency'],
            'payment_method' => 'sakk',
            'gateway'       => 'sakk',
            'status'        => 'pending',
            'notes'         => [
                'type'            => 'escrow',
                'escrow_type'     => $validated['escrow_type'],
                'conversation_id' => $conversation->id,
                'property_id'     => $conversation->property_id,
                'agency_id'       => $agency->id,
            ],
        ]);

        // Call SAKK API
        $sakk = app(SakkService::class);
        $result = $sakk->createPayment([
            'amount'       => (float) $validated['amount'],
            'currency'     => $validated['currency'],
            'description'  => 'Payment Request: ' . $agency->name . ' — ' . ($validated['note'] ?: 'Property deposit'),
            'callback_url' => route('sakk.webhook'),
            'reference_id' => (string) $payment->id,
        ]);

        if (!$result['success']) {
            $payment->delete();
            return response()->json(['message' => 'Payment initiation failed: ' . ($result['error'] ?? 'Unknown')], 502);
        }

        // Store SAKK transaction ID
        if (isset($result['transaction_id'])) {
            $payment->update(['transaction_id' => $result['transaction_id']]);
        }

        // Create system message in chat with payment request
        $msg = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type'     => 'agency',
            'sender_id'       => $request->user()->id,
            'message'         => $validated['note'] ?? '',
            'message_type'    => 'payment_request',
            'metadata'        => [
                'payment_id'      => $payment->id,
                'amount'          => (float) $validated['amount'],
                'currency'        => $validated['currency'],
                'escrow_type'     => $validated['escrow_type'],
                'pay_url'         => $result['payment_url'],
                'transaction_id'  => $result['transaction_id'] ?? null,
                'status'          => 'pending',
            ],
        ]);
        $this->tryBroadcast(new NewMessage($msg, $conversation->id, 'agency'));

        $conversation->touch();

        return response()->json(['data' => $msg->fresh()], 201);
    }

    // ──────────────────────────────────────────────
    //  Offer / Negotiation System
    // ──────────────────────────────────────────────

    /**
     * Send an offer from the agency to the client.
     */
    public function sendOffer(Request $request, Conversation $conversation): JsonResponse
    {
        $agency = $this->getAgency($request);
        abort_if($conversation->agency_id !== $agency->id, 403);

        $validated = $request->validate([
            'amount'   => 'required|numeric|min:1|max:999999999.99',
            'currency' => 'required|string|max:5',
            'note'     => 'nullable|string|max:2000',
        ]);

        $msg = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type'     => 'agency',
            'sender_id'       => $request->user()->id,
            'message'         => $validated['note'] ?? '',
            'message_type'    => 'offer',
            'metadata'        => [
                'amount'       => (float) $validated['amount'],
                'currency'     => $validated['currency'],
                'status'       => 'pending',
                'sender_role'  => 'agency',
            ],
        ]);
        $this->tryBroadcast(new NewMessage($msg, $conversation->id, 'agency'));

        $conversation->touch();
        return response()->json(['data' => $msg->fresh()], 201);
    }

    /**
     * Accept an offer (client's offer). Auto-creates payment request via SAKK.
     */
    public function acceptOffer(Request $request, Conversation $conversation): JsonResponse
    {
        $agency = $this->getAgency($request);
        abort_if($conversation->agency_id !== $agency->id, 403);

        $validated = $request->validate([
            'message_id' => 'required|exists:chat_messages,id',
        ]);

        $offerMsg = ChatMessage::where('id', $validated['message_id'])
            ->where('conversation_id', $conversation->id)
            ->where('message_type', 'offer')
            ->firstOrFail();

        $meta = $offerMsg->metadata;
        abort_if($meta['status'] !== 'pending' && $meta['status'] !== 'countered', 400, 'Offer is not pending');

        // Update offer status
        $offerMsg->update(['metadata' => array_merge($meta, ['status' => 'accepted', 'accepted_at' => now()->toIso8601String()])]);

        // Auto-create payment request if agency has SAKK
        if ($agency->sakk_merchant_id && $agency->sakk_verified) {
            $payment = Payment::create([
                'agency_id' => $agency->id,
                'amount'    => $meta['amount'],
                'currency'  => $meta['currency'] ?? 'USD',
                'payment_method' => 'sakk',
                'gateway'       => 'sakk',
                'status'        => 'pending',
                'notes'         => [
                    'type'            => 'escrow',
                    'escrow_type'     => 'sale',
                    'conversation_id' => $conversation->id,
                    'property_id'     => $conversation->property_id,
                    'agency_id'       => $agency->id,
                    'offer_message_id'=> $offerMsg->id,
                ],
            ]);

            $sakk = app(SakkService::class);
            $result = $sakk->createPayment([
                'amount'       => (float) $meta['amount'],
                'currency'     => $meta['currency'] ?? 'USD',
                'description'  => 'Accepted Offer: ' . $agency->name . ' — ' . number_format((float) $meta['amount']) . ' ' . ($meta['currency'] ?? 'USD'),
                'callback_url' => route('sakk.webhook'),
                'reference_id' => (string) $payment->id,
            ]);

            if ($result['success'] && isset($result['transaction_id'])) {
                $payment->update(['transaction_id' => $result['transaction_id']]);
            }

            // Send payment request message
            $payMsg = ChatMessage::create([
                'conversation_id' => $conversation->id,
                'sender_type'     => 'agency',
                'sender_id'       => $request->user()->id,
                'message'         => '✅ ' . __('offer.accepted_payment'),
                'message_type'    => 'payment_request',
                'metadata'        => [
                    'payment_id'     => $payment->id,
                    'amount'         => (float) $meta['amount'],
                    'currency'       => $meta['currency'] ?? 'USD',
                    'escrow_type'    => 'sale',
                    'pay_url'        => $result['payment_url'] ?? null,
                    'transaction_id' => $result['transaction_id'] ?? null,
                    'status'         => 'pending',
                    'offer_id'       => $offerMsg->id,
                ],
            ]);
            $this->tryBroadcast(new NewMessage($payMsg, $conversation->id, 'agency'));
        }

        // Send acceptance message
        $acceptMsg = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type'     => 'agency',
            'sender_id'       => $request->user()->id,
            'message'         => $validated['note'] ?? '✅ ' . __('offer.accepted'),
            'message_type'    => 'text',
        ]);
        $this->tryBroadcast(new NewMessage($acceptMsg, $conversation->id, 'agency'));

        $conversation->touch();
        return response()->json(['message' => 'Offer accepted', 'data' => $offerMsg->fresh()]);
    }

    /**
     * Reject an offer.
     */
    public function rejectOffer(Request $request, Conversation $conversation): JsonResponse
    {
        $agency = $this->getAgency($request);
        abort_if($conversation->agency_id !== $agency->id, 403);

        $validated = $request->validate([
            'message_id' => 'required|exists:chat_messages,id',
            'reason'     => 'nullable|string|max:1000',
        ]);

        $offerMsg = ChatMessage::where('id', $validated['message_id'])
            ->where('conversation_id', $conversation->id)
            ->where('message_type', 'offer')
            ->firstOrFail();

        $meta = $offerMsg->metadata;
        abort_if($meta['status'] !== 'pending', 400, 'Offer is not pending');

        $offerMsg->update(['metadata' => array_merge($meta, [
            'status'  => 'rejected',
            'reason'  => $validated['reason'] ?? null,
            'rejected_at' => now()->toIso8601String(),
        ])]);

        $rejectMsg = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type'     => 'agency',
            'sender_id'       => $request->user()->id,
            'message'         => '❌ ' . ($validated['reason'] ?? __('offer.rejected')),
            'message_type'    => 'text',
        ]);
        $this->tryBroadcast(new NewMessage($rejectMsg, $conversation->id, 'agency'));

        $conversation->touch();
        return response()->json(['message' => 'Offer rejected', 'data' => $offerMsg->fresh()]);
    }

    /**
     * Counter an offer (agency sends a new offer in response).
     */
    public function counterOffer(Request $request, Conversation $conversation): JsonResponse
    {
        $agency = $this->getAgency($request);
        abort_if($conversation->agency_id !== $agency->id, 403);

        $validated = $request->validate([
            'message_id'      => 'required|exists:chat_messages,id',
            'amount'          => 'required|numeric|min:1|max:999999999.99',
            'currency'        => 'required|string|max:5',
            'note'            => 'nullable|string|max:2000',
        ]);

        // Mark original offer as countered
        $originalMsg = ChatMessage::where('id', $validated['message_id'])
            ->where('conversation_id', $conversation->id)
            ->where('message_type', 'offer')
            ->firstOrFail();

        $origMeta = $originalMsg->metadata;
        $originalMsg->update(['metadata' => array_merge($origMeta, [
            'status'       => 'countered',
            'countered_at' => now()->toIso8601String(),
        ])]);

        // Send counter offer
        $msg = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type'     => 'agency',
            'sender_id'       => $request->user()->id,
            'message'         => $validated['note'] ?? '',
            'message_type'    => 'offer',
            'metadata'        => [
                'amount'       => (float) $validated['amount'],
                'currency'     => $validated['currency'],
                'status'       => 'pending',
                'sender_role'  => 'agency',
                'counter_to'   => $originalMsg->id,
            ],
        ]);
        $this->tryBroadcast(new NewMessage($msg, $conversation->id, 'agency'));

        $conversation->touch();
        return response()->json(['data' => $msg->fresh()], 201);
    }
}
