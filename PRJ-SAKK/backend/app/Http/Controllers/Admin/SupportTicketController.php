<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SupportTicketMail;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Admin support-ticket desk (Gate 4).
 *
 * Lists customer tickets, shows the full thread (including internal notes that
 * the customer never sees), lets an agent reply / change status / assign. Every
 * outward reply notifies the customer by push + in-app + email (Reply-To support).
 */
class SupportTicketController extends Controller
{
    private const STATUSES = ['open', 'in_progress', 'waiting_customer', 'resolved', 'closed'];

    public function index(Request $request): View
    {
        $filters = [
            'status' => $request->query('status'),
            'priority' => $request->query('priority'),
            'category' => $request->query('category'),
            'q' => trim((string) $request->query('q', '')),
        ];

        $tickets = SupportTicket::with('user')
            ->withCount('messages')
            ->when($filters['status'], fn ($q, $v) => $q->where('status', $v))
            ->when($filters['priority'], fn ($q, $v) => $q->where('priority', $v))
            ->when($filters['category'], fn ($q, $v) => $q->where('category', $v))
            ->when($filters['q'], function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('ticket_number', 'like', "%{$v}%")
                        ->orWhere('subject', 'like', "%{$v}%")
                        ->orWhereHas('user', fn ($u) => $u->where('first_name', 'like', "%{$v}%")
                            ->orWhere('last_name', 'like', "%{$v}%")
                            ->orWhere('email', 'like', "%{$v}%"));
                });
            })
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        $kpis = [
            'open' => SupportTicket::where('status', 'open')->count(),
            'in_progress' => SupportTicket::where('status', 'in_progress')->count(),
            'waiting_customer' => SupportTicket::where('status', 'waiting_customer')->count(),
            'urgent' => SupportTicket::whereIn('status', ['open', 'in_progress'])->where('priority', 'urgent')->count(),
            'total' => SupportTicket::count(),
        ];

        return view('admin.support.index', compact('tickets', 'filters', 'kpis'));
    }

    public function show(SupportTicket $ticket): View
    {
        $ticket->load(['user', 'assignedTo', 'messages.user']);

        return view('admin.support.show', [
            'ticket' => $ticket,
            'statuses' => self::STATUSES,
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket, NotificationService $notifications): RedirectResponse
    {
        $data = $request->validate([
            'message' => 'required|string|max:4000',
            'is_internal' => 'nullable|boolean',
        ]);

        $isInternal = (bool) ($data['is_internal'] ?? false);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $data['message'],
            'is_internal' => $isInternal,
        ]);

        // A public reply moves an untouched ticket into progress and pings the customer.
        if (!$isInternal) {
            if ($ticket->status === 'open') {
                $ticket->status = 'in_progress';
            }
            $ticket->save();
            $ticket->touch();

            $this->notifyCustomer($ticket, $notifications, $data['message']);
        } else {
            $ticket->touch();
        }

        return back()->with('success', $isInternal ? 'تمت إضافة ملاحظة داخلية' : 'تم إرسال الرد للعميل');
    }

    public function updateStatus(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'status' => 'required|in:' . implode(',', self::STATUSES),
        ]);

        $ticket->status = $data['status'];
        $ticket->resolved_at = in_array($data['status'], ['resolved', 'closed'], true) ? now() : null;
        $ticket->save();

        return back()->with('success', 'تم تحديث حالة التذكرة');
    }

    public function assign(Request $request, SupportTicket $ticket): RedirectResponse
    {
        // Assign to the acting admin (claim) or release.
        $ticket->assigned_to = $request->boolean('release') ? null : $request->user()->id;
        $ticket->save();

        return back()->with('success', $request->boolean('release') ? 'تم إلغاء الإسناد' : 'تم إسناد التذكرة إليك');
    }

    // ──────────────────────────────────────────────────────────────

    private function notifyCustomer(SupportTicket $ticket, NotificationService $notifications, string $message): void
    {
        $user = $ticket->user;
        if (!$user) {
            return;
        }

        // Push + in-app (best-effort, swallows its own errors).
        $notifications->supportTicketReplied($user, $ticket);

        // Email.
        if ($user->email) {
            try {
                Mail::to($user->email)->send(new SupportTicketMail(
                    'رد من الدعم الفني',
                    $ticket->ticket_number,
                    [
                        "مرحباً {$user->first_name}،",
                        "وصلك رد جديد من فريق الدعم بخصوص تذكرتك: {$ticket->subject}",
                        $message,
                        'يمكنك الرد من داخل التطبيق لمتابعة المحادثة.',
                    ],
                ));
            } catch (\Throwable $e) {
                Log::error('Support customer mail failed', ['ticket' => $ticket->ticket_number, 'error' => $e->getMessage()]);
            }
        }

        // Telegram — push the reply back to the support-bot chat that opened it.
        if ($ticket->telegram_chat_id) {
            $text = "💬 <b>رد من دعم صكّ</b> — تذكرة <b>{$ticket->ticket_number}</b>\n\n" . e($message);
            app(\App\Services\TelegramSupportService::class)->sendMessage((string) $ticket->telegram_chat_id, $text);
        }
    }
}
