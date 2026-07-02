<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\SupportTicketMail;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Services\AdminNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Customer-facing support tickets (mobile app).
 *
 * Mirrors the admin side (App\Http\Controllers\Admin\SupportTicketController):
 * a customer opens a ticket, both sides exchange messages, an agent resolves it.
 * Internal notes (is_internal) are NEVER exposed here.
 */
class SupportTicketController extends Controller
{
    private const CATEGORIES = ['general', 'transaction', 'card', 'kyc', 'technical', 'billing'];

    /** The authenticated user's tickets, newest activity first. */
    public function index(Request $request): JsonResponse
    {
        $tickets = SupportTicket::where('user_id', $request->user()->id)
            ->withCount(['messages' => fn ($q) => $q->where('is_internal', false)])
            ->latest('updated_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $tickets->getCollection()->map(fn (SupportTicket $t) => $this->summary($t))->all(),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'total' => $tickets->total(),
            ],
        ]);
    }

    /** Open a new ticket. The description becomes the first message. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject' => 'required|string|max:160',
            'description' => 'required|string|max:4000',
            'category' => 'nullable|in:' . implode(',', self::CATEGORIES),
            'priority' => 'nullable|in:low,medium,high,urgent',
            'related_transaction' => ['nullable', 'integer', Rule::exists('transactions', 'id')->where('user_id', auth()->id())],
        ], [
            'subject.required' => 'عنوان التذكرة مطلوب.',
            'description.required' => 'وصف المشكلة مطلوب.',
        ]);

        $user = $request->user();

        $ticket = DB::transaction(function () use ($data, $user) {
            $ticket = SupportTicket::create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $user->id,
                'ticket_number' => $this->generateNumber(),
                'subject' => $data['subject'],
                'description' => $data['description'],
                'category' => $data['category'] ?? 'general',
                'priority' => $data['priority'] ?? 'medium',
                'status' => 'open',
                'related_transaction' => $data['related_transaction'] ?? null,
            ]);

            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => $data['description'],
                'is_internal' => false,
            ]);

            return $ticket;
        });

        $this->notifySupportInbox($ticket, 'تذكرة دعم جديدة', [
            "فتح {$user->first_name} {$user->last_name} تذكرة دعم جديدة.",
            "الموضوع: {$ticket->subject}",
            "التصنيف: {$ticket->category} · الأولوية: {$ticket->priority}",
            $ticket->description,
        ]);
        AdminNotificationService::supportTicketOpened($ticket->loadMissing('user'));

        return response()->json([
            'success' => true,
            'message' => 'تم فتح التذكرة بنجاح',
            'data' => $this->detail($ticket->load('messages')),
        ], 201);
    }

    /** One ticket with its public message thread. */
    public function show(Request $request, string $uuid): JsonResponse
    {
        $ticket = $this->findOwned($request, $uuid);

        return response()->json([
            'success' => true,
            'data' => $this->detail($ticket->load(['messages' => fn ($q) => $q->where('is_internal', false)->oldest()])),
        ]);
    }

    /** Customer reply — re-opens a resolved/closed ticket. */
    public function reply(Request $request, string $uuid): JsonResponse
    {
        $data = $request->validate([
            'message' => 'required|string|max:4000',
        ], [
            'message.required' => 'نص الرسالة مطلوب.',
        ]);

        $ticket = $this->findOwned($request, $uuid);
        $user = $request->user();

        if (in_array($ticket->status, ['resolved', 'closed'], true)) {
            $ticket->status = 'open';
            $ticket->resolved_at = null;
            $ticket->save();
        }

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => $data['message'],
            'is_internal' => false,
        ]);
        $ticket->touch();

        $this->notifySupportInbox($ticket, 'رد جديد على تذكرة', [
            "رد {$user->first_name} {$user->last_name} على التذكرة.",
            "الموضوع: {$ticket->subject}",
            $data['message'],
        ]);
        AdminNotificationService::supportTicketReplied($ticket->loadMissing('user'));

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال ردّك',
            'data' => $this->detail($ticket->load(['messages' => fn ($q) => $q->where('is_internal', false)->oldest()])),
        ]);
    }

    /** Static metadata for the app's "new ticket" form. */
    public function categories(): JsonResponse
    {
        $labels = [
            'general' => 'استفسار عام',
            'transaction' => 'معاملة',
            'card' => 'بطاقة',
            'kyc' => 'التحقق KYC',
            'technical' => 'مشكلة تقنية',
            'billing' => 'الفواتير والرسوم',
        ];

        return response()->json([
            'success' => true,
            'data' => collect(self::CATEGORIES)->map(fn ($c) => ['value' => $c, 'label' => $labels[$c]])->all(),
        ]);
    }

    // ──────────────────────────────────────────────────────────────

    private function findOwned(Request $request, string $uuid): SupportTicket
    {
        return SupportTicket::where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
    }

    private function generateNumber(): string
    {
        do {
            $number = 'TK-' . now()->format('ymd') . '-' . strtoupper(Str::random(4));
        } while (SupportTicket::where('ticket_number', $number)->exists());

        return $number;
    }

    private function summary(SupportTicket $t): array
    {
        return [
            'uuid' => $t->uuid,
            'ticket_number' => $t->ticket_number,
            'subject' => $t->subject,
            'category' => $t->category,
            'priority' => $t->priority,
            'status' => $t->status,
            'messages_count' => $t->messages_count ?? $t->messages()->where('is_internal', false)->count(),
            'created_at' => $t->created_at,
            'updated_at' => $t->updated_at,
        ];
    }

    private function detail(SupportTicket $t): array
    {
        return array_merge($this->summary($t), [
            'description' => $t->description,
            'resolved_at' => $t->resolved_at,
            'messages' => $t->messages
                ->where('is_internal', false)
                ->values()
                ->map(fn (TicketMessage $m) => [
                    'id' => $m->id,
                    'message' => $m->message,
                    'is_mine' => $m->user_id === $t->user_id,
                    'sender' => $m->user_id === $t->user_id ? 'customer' : 'support',
                    'created_at' => $m->created_at,
                ])->all(),
        ]);
    }

    /** Best-effort email to the monitored support inbox. Never blocks the request. */
    private function notifySupportInbox(SupportTicket $ticket, string $heading, array $lines): void
    {
        try {
            Mail::to((string) config('mail.support_address', 'support@zanjour.com'))
                ->send(new SupportTicketMail($heading, $ticket->ticket_number, $lines));
        } catch (\Throwable $e) {
            Log::error('Support inbox mail failed', ['ticket' => $ticket->ticket_number, 'error' => $e->getMessage()]);
        }
    }
}
