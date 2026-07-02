<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Agent-side live chat (admin panel). Polling transport — the inbox and the
 * open thread refresh over AJAX every few seconds. Standalone module, unrelated
 * to the SupportTicket desk.
 */
class LiveChatController extends Controller
{
    /** Inbox — conversations newest-activity first, with unread counts. */
    public function index(Request $request): View
    {
        $conversations = $this->inboxQuery($request)->get()->map(fn ($c) => $this->presentRow($c));

        return view('admin.chat.index', [
            'conversations' => $conversations,
            'filter' => $request->query('filter', 'all'),
            'unreadTotal' => $this->unreadTotal(),
        ]);
    }

    /** JSON inbox feed for live refresh + the sidebar unread badge. */
    public function inboxFeed(Request $request): JsonResponse
    {
        $conversations = $this->inboxQuery($request)->get()->map(fn ($c) => $this->presentRow($c));

        return response()->json([
            'success' => true,
            'data' => [
                'unread_total' => $this->unreadTotal(),
                'conversations' => $conversations->values()->all(),
            ],
        ]);
    }

    /** Open one thread. Claims it for the current agent and marks user msgs read. */
    public function show(Conversation $conversation): View
    {
        $conversation->load('user');

        // First agent to open the thread claims it and greets the customer once.
        if ($conversation->agent_id === null) {
            $conversation->forceFill(['agent_id' => auth()->id()])->save();
            $conversation->messages()->create([
                'sender_type' => 'system',
                'sender_id' => null,
                'body' => 'تم ربطك بأحد موظفي الدعم. تفضّل بطرح استفسارك.',
            ]);
        }
        $this->markUserMessagesRead($conversation);

        $messages = $conversation->messages()->orderBy('id')->get()
            ->map(fn ($m) => $this->presentMessage($m));

        return view('admin.chat.show', compact('conversation', 'messages'));
    }

    /** Poll new messages in an open thread (after id) + mark user msgs read. */
    public function poll(Request $request, Conversation $conversation): JsonResponse
    {
        $after = (int) $request->query('after', 0);

        $messages = $conversation->messages()
            ->when($after > 0, fn ($q) => $q->where('id', '>', $after))
            ->orderBy('id')->limit(100)->get();

        $this->markUserMessagesRead($conversation);

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $conversation->status,
                'messages' => $messages->map(fn ($m) => $this->presentMessage($m))->all(),
            ],
        ]);
    }

    /** Agent reply. */
    public function reply(Request $request, Conversation $conversation): JsonResponse
    {
        $data = $request->validate(['body' => 'required|string|max:4000'], [], ['body' => 'الرسالة']);

        if ($conversation->agent_id === null) {
            $conversation->agent_id = auth()->id();
        }
        if ($conversation->status === 'closed') {
            $conversation->status = 'open';
        }

        $message = $conversation->messages()->create([
            'sender_type' => 'agent',
            'sender_id' => auth()->id(),
            'body' => $data['body'],
        ]);

        $conversation->forceFill(['last_message_at' => $message->created_at])->save();

        return response()->json(['success' => true, 'data' => $this->presentMessage($message)], 201);
    }

    /** Close / reopen the thread. */
    public function setStatus(Request $request, Conversation $conversation): RedirectResponse
    {
        $status = $request->input('status') === 'closed' ? 'closed' : 'open';
        $conversation->forceFill(['status' => $status])->save();

        return back()->with('success', $status === 'closed' ? 'تم إغلاق المحادثة' : 'تمت إعادة فتح المحادثة');
    }

    // ──────────────────────────── helpers ────────────────────────────

    private function inboxQuery(Request $request)
    {
        $filter = $request->query('filter', 'all');

        return Conversation::query()
            ->with(['user', 'latestMessage'])
            ->withCount(['messages as unread_count' => fn ($q) => $q->where('sender_type', 'user')->whereNull('read_at')])
            ->when($filter === 'open', fn ($q) => $q->where('status', 'open'))
            ->when($filter === 'closed', fn ($q) => $q->where('status', 'closed'))
            ->when($filter === 'unread', fn ($q) => $q->whereHas('messages', fn ($m) => $m->where('sender_type', 'user')->whereNull('read_at')))
            ->orderByDesc('last_message_at')
            ->limit(200);
    }

    private function unreadTotal(): int
    {
        return ChatMessage::where('sender_type', 'user')->whereNull('read_at')->count();
    }

    private function markUserMessagesRead(Conversation $conversation): void
    {
        $conversation->messages()
            ->where('sender_type', 'user')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    private function presentRow(Conversation $c): array
    {
        $last = $c->latestMessage;

        return [
            'id' => $c->id,
            'status' => $c->status,
            'unread' => (int) ($c->unread_count ?? 0),
            'user_name' => $this->customerName($c),
            'user_phone' => $c->user?->phone ?? '',
            'last_body' => $last ? mb_substr($last->body, 0, 60) : '',
            'last_at' => optional($c->last_message_at)->diffForHumans(),
            'url' => route('admin.chat.show', $c),
        ];
    }

    /** User has first_name/last_name (no `name` column) — derive a display name. */
    private function customerName(Conversation $c): string
    {
        $name = trim((string) ($c->user?->full_name ?? ''));

        return $name !== '' ? $name : ('#' . $c->user_id);
    }

    private function presentMessage(ChatMessage $m): array
    {
        return [
            'id' => $m->id,
            'sender_type' => $m->sender_type,
            'body' => $m->body,
            'time' => $m->created_at->format('H:i'),
            'created_at' => $m->created_at->toIso8601String(),
        ];
    }
}
