<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Events\NewMessage;
use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserChatController extends Controller
{
    /**
     * Wrap broadcast event in try-catch so Reverb failure never crashes the HTTP response.
     * Message is already saved to DB — broadcast is best-effort.
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
    /**
     * List the authenticated user's conversations.
     * Query params: ?tab=inbox|archived|trash
     */
    public function conversations(Request $request): JsonResponse
    {
        $user = $request->user();
        $tab = $request->query('tab', 'inbox');

        $query = match ($tab) {
            'archived' => Conversation::where('user_id', $user->id)
                ->whereNotNull('archived_at')
                ->withTrashed(),
            'trash' => Conversation::where('user_id', $user->id)
                ->onlyTrashed(),
            default => Conversation::where('user_id', $user->id)
                ->inbox(),
        };

        $conversations = $query
            ->with(['latestMessage', 'property:id,slug,title_ar,title_en', 'agency:id,name,slug,logo_path'])
            ->orderByDesc('updated_at')
            ->paginate(20);

        return response()->json([
            'data' => $conversations->items(),
            'meta' => [
                'total'        => $conversations->total(),
                'per_page'     => $conversations->perPage(),
                'current_page' => $conversations->currentPage(),
                'last_page'    => $conversations->lastPage(),
            ],
        ]);
    }

    /**
     * Create or return existing conversation with an agency about a property.
     */
    public function startConversation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agency_id'   => 'required|exists:agencies,id',
            'property_id' => 'nullable|exists:properties,id',
            'message'     => 'required|string|max:2000',
        ]);

        $user = $request->user();

        // Check if conversation already exists
        $existing = Conversation::where('user_id', $user->id)
            ->where('agency_id', $validated['agency_id'])
            ->when($validated['property_id'] ?? null, function ($q, $pid) {
                $q->where('property_id', $pid);
            }, function ($q) {
                $q->whereNull('property_id');
            })
            ->first();

        if ($existing) {
            // Add message to existing conversation
            $msg = ChatMessage::create([
                'conversation_id' => $existing->id,
                'sender_type'     => 'client',
                'sender_id'       => $user->id,
                'message'         => $validated['message'],
            ]);
            $this->tryBroadcast(new NewMessage($msg, $existing->id, 'user'));
            $existing->touch();

            return response()->json([
                'data' => [
                    'conversation' => $existing->fresh()->load('agency:id,name,slug,logo_path'),
                    'message'      => $msg,
                ],
            ], 201);
        }

        // Create new conversation
        $conversation = Conversation::create([
            'user_id'      => $user->id,
            'agency_id'    => $validated['agency_id'],
            'property_id'  => $validated['property_id'] ?? null,
            'client_name'  => $user->name,
            'client_phone' => $user->phone,
            'client_email' => $user->email,
        ]);

        $msg = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type'     => 'client',
            'sender_id'       => $user->id,
            'message'         => $validated['message'],
        ]);
        $this->tryBroadcast(new NewMessage($msg, $conversation->id, 'user'));

        return response()->json([
            'data' => [
                'conversation' => $conversation->load('agency:id,name,slug,logo_path'),
                'message'      => $msg,
            ],
        ], 201);
    }

    /**
     * Get messages for a conversation (must belong to user).
     */
    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        abort_if($conversation->user_id !== $request->user()->id, 403);

        $messages = ChatMessage::where('conversation_id', $conversation->id)
            ->orderBy('created_at')
            ->paginate(50);

        return response()->json([
            'data' => $messages->items(),
            'meta' => [
                'total'        => $messages->total(),
                'per_page'     => $messages->perPage(),
                'current_page' => $messages->currentPage(),
                'last_page'    => $messages->lastPage(),
            ],
        ]);
    }

    /**
     * Send a message in a conversation.
     */
    public function storeMessage(Request $request, Conversation $conversation): JsonResponse
    {
        abort_if($conversation->user_id !== $request->user()->id, 403);

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
            'sender_type'     => 'client',
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
        $this->tryBroadcast(new NewMessage($msg, $conversation->id, 'user'));
        $conversation->touch();

        return response()->json(['data' => $msg->fresh()], 201);
    }

    /**
     * Mark agency messages as read.
     */
    public function markAsRead(Request $request, Conversation $conversation): JsonResponse
    {
        abort_if($conversation->user_id !== $request->user()->id, 403);

        ChatMessage::where('conversation_id', $conversation->id)
            ->where('sender_type', 'agency')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Messages marked as read']);
    }

    /**
     * Get unread count for the user (messages from agencies).
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();

        $count = ChatMessage::whereIn('conversation_id',
            Conversation::where('user_id', $user->id)->inbox()->select('id')
        )->where('sender_type', 'agency')->whereNull('read_at')->count();

        return response()->json(['data' => ['unread_count' => $count]]);
    }

    // ──────────────────────────────────────────────
    //  Archive / Trash / Restore
    // ──────────────────────────────────────────────

    /**
     * Archive a conversation (hide from inbox, keep in archive).
     */
    public function archive(Request $request, Conversation $conversation): JsonResponse
    {
        abort_if($conversation->user_id !== $request->user()->id, 403);
        abort_if($conversation->trashed(), 400, 'Cannot archive a trashed conversation.');

        $conversation->update(['archived_at' => now()]);

        return response()->json(['message' => 'Conversation archived']);
    }

    /**
     * Unarchive a conversation (move back to inbox).
     * Uses explicit id to find archived + trashed models.
     */
    public function unarchive(Request $request, int $id): JsonResponse
    {
        $conversation = Conversation::withTrashed()->findOrFail($id);
        abort_if($conversation->user_id !== $request->user()->id, 403);

        $conversation->update(['archived_at' => null]);

        return response()->json(['message' => 'Conversation restored to inbox']);
    }

    /**
     * Soft-delete a conversation (move to trash).
     */
    public function trash(Request $request, Conversation $conversation): JsonResponse
    {
        abort_if($conversation->user_id !== $request->user()->id, 403);

        $conversation->delete(); // soft delete

        return response()->json(['message' => 'Conversation moved to trash']);
    }

    /**
     * Restore a trashed conversation back to inbox (unarchived).
     */
    public function restore(Request $request, int $id): JsonResponse
    {
        $conversation = Conversation::onlyTrashed()->findOrFail($id);
        abort_if($conversation->user_id !== $request->user()->id, 403);

        $conversation->restore();
        $conversation->update(['archived_at' => null]);

        return response()->json(['message' => 'Conversation restored']);
    }

    /**
     * Permanently delete a trashed conversation.
     */
    public function forceDelete(Request $request, int $id): JsonResponse
    {
        $conversation = Conversation::onlyTrashed()->findOrFail($id);
        abort_if($conversation->user_id !== $request->user()->id, 403);

        // Delete associated messages first
        ChatMessage::where('conversation_id', $conversation->id)->delete();
        $conversation->forceDelete();

        return response()->json(['message' => 'Conversation permanently deleted']);
    }

    // ──────────────────────────────────────────────
    //  Offer / Negotiation System (Client side)
    // ──────────────────────────────────────────────

    /**
     * Send an offer from the client to the agency.
     */
    public function sendOffer(Request $request, Conversation $conversation): JsonResponse
    {
        abort_if($conversation->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'amount'   => 'required|numeric|min:1|max:999999999.99',
            'currency' => 'required|string|max:5',
            'note'     => 'nullable|string|max:2000',
        ]);

        $msg = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type'     => 'client',
            'sender_id'       => $request->user()->id,
            'message'         => $validated['note'] ?? '',
            'message_type'    => 'offer',
            'metadata'        => [
                'amount'      => (float) $validated['amount'],
                'currency'    => $validated['currency'],
                'status'      => 'pending',
                'sender_role' => 'client',
            ],
        ]);
        $this->tryBroadcast(new NewMessage($msg, $conversation->id, 'user'));

        $conversation->touch();
        return response()->json(['data' => $msg->fresh()], 201);
    }

    /**
     * Accept an agency's offer.
     */
    public function acceptOffer(Request $request, Conversation $conversation): JsonResponse
    {
        abort_if($conversation->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'message_id' => 'required|exists:chat_messages,id',
        ]);

        $offerMsg = ChatMessage::where('id', $validated['message_id'])
            ->where('conversation_id', $conversation->id)
            ->where('message_type', 'offer')
            ->firstOrFail();

        $meta = $offerMsg->metadata;
        abort_if($meta['status'] !== 'pending', 400, 'Offer is not pending');

        // Update offer status
        $offerMsg->update(['metadata' => array_merge($meta, ['status' => 'accepted', 'accepted_at' => now()->toIso8601String()])]);

        // Notify
        $acceptMsg = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type'     => 'client',
            'sender_id'       => $request->user()->id,
            'message'         => '✅ ' . __('offer.accepted'),
            'message_type'    => 'text',
        ]);
        $this->tryBroadcast(new NewMessage($acceptMsg, $conversation->id, 'user'));

        $conversation->touch();
        return response()->json(['message' => 'Offer accepted', 'data' => $offerMsg->fresh()]);
    }

    /**
     * Reject an agency's offer.
     */
    public function rejectOffer(Request $request, Conversation $conversation): JsonResponse
    {
        abort_if($conversation->user_id !== $request->user()->id, 403);

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
            'status'      => 'rejected',
            'reason'      => $validated['reason'] ?? null,
            'rejected_at' => now()->toIso8601String(),
        ])]);

        $rejectMsg = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type'     => 'client',
            'sender_id'       => $request->user()->id,
            'message'         => '❌ ' . ($validated['reason'] ?? __('offer.rejected')),
            'message_type'    => 'text',
        ]);
        $this->tryBroadcast(new NewMessage($rejectMsg, $conversation->id, 'user'));

        $conversation->touch();
        return response()->json(['message' => 'Offer rejected', 'data' => $offerMsg->fresh()]);
    }

    /**
     * Counter an agency's offer.
     */
    public function counterOffer(Request $request, Conversation $conversation): JsonResponse
    {
        abort_if($conversation->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'message_id'      => 'required|exists:chat_messages,id',
            'amount'          => 'required|numeric|min:1|max:999999999.99',
            'currency'        => 'required|string|max:5',
            'note'            => 'nullable|string|max:2000',
        ]);

        $originalMsg = ChatMessage::where('id', $validated['message_id'])
            ->where('conversation_id', $conversation->id)
            ->where('message_type', 'offer')
            ->firstOrFail();

        $origMeta = $originalMsg->metadata;
        $originalMsg->update(['metadata' => array_merge($origMeta, [
            'status'       => 'countered',
            'countered_at' => now()->toIso8601String(),
        ])]);

        $msg = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type'     => 'client',
            'sender_id'       => $request->user()->id,
            'message'         => $validated['note'] ?? '',
            'message_type'    => 'offer',
            'metadata'        => [
                'amount'      => (float) $validated['amount'],
                'currency'    => $validated['currency'],
                'status'      => 'pending',
                'sender_role' => 'client',
                'counter_to'  => $originalMsg->id,
            ],
        ]);
        $this->tryBroadcast(new NewMessage($msg, $conversation->id, 'user'));

        $conversation->touch();
        return response()->json(['data' => $msg->fresh()], 201);
    }
}
