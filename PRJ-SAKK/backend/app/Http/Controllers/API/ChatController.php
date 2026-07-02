<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Customer-facing live chat (polling transport).
 *
 * The app opens the user's single active conversation, polls
 * GET /chat/messages?after=<id> every few seconds, and POSTs new messages.
 * No websocket infra required.
 */
class ChatController extends Controller
{
    /** Get (or lazily create) the caller's open conversation + its messages. */
    public function conversation(Request $request): JsonResponse
    {
        $conversation = $this->openConversationFor($request);

        $messages = $conversation->messages()->orderBy('id')->get();
        $this->markAgentMessagesRead($conversation);

        return response()->json([
            'success' => true,
            'data' => [
                'conversation' => $this->presentConversation($conversation),
                'messages' => $messages->map(fn ($m) => $this->presentMessage($m))->all(),
            ],
        ]);
    }

    /** Poll new messages after a given id. Also marks agent replies as read. */
    public function messages(Request $request): JsonResponse
    {
        $conversation = $this->openConversationFor($request);
        $after = (int) $request->query('after', 0);

        $messages = $conversation->messages()
            ->when($after > 0, fn ($q) => $q->where('id', '>', $after))
            ->orderBy('id')
            ->limit(100)
            ->get();

        $this->markAgentMessagesRead($conversation);

        return response()->json([
            'success' => true,
            'data' => [
                'conversation_id' => $conversation->id,
                'status' => $conversation->status,
                'messages' => $messages->map(fn ($m) => $this->presentMessage($m))->all(),
            ],
        ]);
    }

    /** Send a message from the customer. Reopens a closed thread. */
    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'body' => 'required|string|max:4000',
        ], [], ['body' => 'الرسالة']);

        $conversation = $this->openConversationFor($request);

        if ($conversation->status === 'closed') {
            $conversation->status = 'open';
        }

        $message = $conversation->messages()->create([
            'sender_type' => 'user',
            'sender_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        $conversation->forceFill(['last_message_at' => $message->created_at])->save();

        return response()->json([
            'success' => true,
            'data' => $this->presentMessage($message),
        ], 201);
    }

    // ──────────────────────────── helpers ────────────────────────────

    private function openConversationFor(Request $request): Conversation
    {
        $userId = $request->user()->id;

        return Conversation::where('user_id', $userId)
            ->where('status', 'open')
            ->latest('id')
            ->first()
            ?? Conversation::create([
                'user_id' => $userId,
                'status' => 'open',
                'last_message_at' => now(),
            ]);
    }

    private function markAgentMessagesRead(Conversation $conversation): void
    {
        $conversation->messages()
            ->where('sender_type', 'agent')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    private function presentConversation(Conversation $c): array
    {
        return [
            'id' => $c->id,
            'status' => $c->status,
            'subject' => $c->subject,
            'last_message_at' => optional($c->last_message_at)->toIso8601String(),
        ];
    }

    private function presentMessage(ChatMessage $m): array
    {
        return [
            'id' => $m->id,
            'sender_type' => $m->sender_type, // user | agent | system
            'body' => $m->body,
            'created_at' => $m->created_at->toIso8601String(),
        ];
    }
}
