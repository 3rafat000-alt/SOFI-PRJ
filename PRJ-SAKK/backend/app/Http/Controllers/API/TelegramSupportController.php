<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\TelegramSupportService;
use App\Support\TelegramMenu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Inbound webhook for the support bot (@SakkSupportBot). A user's message
 * becomes a ticket message on the support desk; the matching user is found by
 * Telegram chat id (the stable value the OTP link already stored, so a linked
 * user is recognised with no extra step). Agent replies are pushed back from
 * SupportTicketController::notifyCustomer. Always returns 200.
 */
class TelegramSupportController extends Controller
{
    /** Statuses considered "still active" — a new message reuses such a ticket. */
    private const OPEN_STATUSES = ['open', 'in_progress', 'waiting_customer'];

    private const NEED_LINK = "👋 مرحباً بك في دعم <b>صكّ</b>.\n\nلفتح تذكرة دعم من تلجرام، اربط حسابك أولاً:\n<b>التطبيق ← الأمان ← ربط تلجرام</b>\n\nثم أعد إرسال رسالتك.";

    public function __construct(private readonly TelegramSupportService $support)
    {
    }

    public function webhook(Request $request): JsonResponse
    {
        // SEC M7: FAIL CLOSED — reject when the secret is unset OR mismatched, so the
        // support webhook is never reachable unauthenticated.
        $secret = (string) config('services.telegram_support.webhook_secret');
        if ($secret === '' || !hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token'))) {
            Log::warning('Telegram support webhook rejected — secret missing or invalid');
            return response()->json(['ok' => true]);
        }

        // Inline-button taps — navigate the menu in place.
        $callback = $request->input('callback_query');
        if (is_array($callback)) {
            $this->handleCallback($callback);
            return response()->json(['ok' => true]);
        }

        $message = $request->input('message', []);
        $chatId = $message['chat']['id'] ?? null;
        $text = trim((string) ($message['text'] ?? ''));

        if ($chatId === null || $text === '') {
            return response()->json(['ok' => true]);
        }

        // Slash commands & menu shortcuts — show a screen, never open a ticket.
        if (str_starts_with($text, '/')) {
            $this->handleCommand((string) $chatId, $text);
            return response()->json(['ok' => true]);
        }

        // Match the user by their stable Telegram chat id (set when they linked).
        $user = User::where('telegram_chat_id', (string) $chatId)->first();
        if (!$user) {
            $this->sendScreen((string) $chatId, TelegramMenu::main('ar'));
            $this->support->sendMessage((string) $chatId, self::NEED_LINK);
            return response()->json(['ok' => true]);
        }

        $ticket = $this->openTicketFor($user, (string) $chatId, $text);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => $text,
            'is_internal' => false,
        ]);

        // A customer reply re-opens a ticket that was waiting on them.
        if ($ticket->status === 'waiting_customer') {
            $ticket->status = 'open';
        }
        $ticket->save();
        $ticket->touch();

        $this->support->sendMessage(
            (string) $chatId,
            "✅ تم استلام رسالتك — تذكرة <b>{$ticket->ticket_number}</b>.\nسيرد عليك فريق <b>صكّ</b> قريباً."
        );

        return response()->json(['ok' => true]);
    }

    /** Map a slash command (optionally @mention-suffixed) to a menu screen. */
    private function handleCommand(string $chatId, string $text): void
    {
        // "/faq@SakkSupportBot extra" → "faq"
        $cmd = ltrim(strtolower(explode(' ', $text)[0]), '/');
        $cmd = explode('@', $cmd)[0];

        $screen = match ($cmd) {
            'start', 'menu' => TelegramMenu::main('ar'),
            'help', 'commands' => TelegramMenu::help('ar'),
            'faq' => TelegramMenu::faqList('ar'),
            'contact', 'support' => TelegramMenu::contact('ar'),
            'app', 'download' => TelegramMenu::app('ar'),
            'link' => TelegramMenu::link('ar'),
            default => TelegramMenu::main('ar'),
        };

        $this->sendScreen($chatId, $screen);
    }

    /** Navigate the menu in place on a button tap (callback-data routed). */
    private function handleCallback(array $callback): void
    {
        $callbackId = (string) ($callback['id'] ?? '');
        $chatId = $callback['message']['chat']['id'] ?? null;
        $messageId = $callback['message']['message_id'] ?? null;
        $data = (string) ($callback['data'] ?? '');

        if ($callbackId !== '') {
            $this->support->answerCallbackQuery($callbackId);
        }
        if ($chatId === null || $messageId === null || $data === '') {
            return;
        }

        $parts = explode(':', $data);
        $prefix = $parts[0] ?? 'm';
        $lang = $parts[1] ?? 'ar';

        $screen = match ($prefix) {
            'm' => TelegramMenu::main($lang),
            'h' => TelegramMenu::help($lang),
            'f' => TelegramMenu::faqList($lang),
            'q' => TelegramMenu::faqAnswer($lang, (int) ($parts[2] ?? 0)),
            'c' => TelegramMenu::contact($lang),
            'a' => TelegramMenu::app($lang),
            'k' => TelegramMenu::link($lang),
            default => TelegramMenu::main($lang),
        };

        // Edit in place; if Telegram refuses the edit, fall back to a fresh send.
        $edited = $this->support->editMessageText(
            (string) $chatId,
            (int) $messageId,
            $screen['text'],
            $screen['markup'] ?? null,
        );
        if (!$edited) {
            $this->sendScreen((string) $chatId, $screen);
        }
    }

    /** Send a TelegramMenu screen (text + inline keyboard). */
    private function sendScreen(string $chatId, array $screen): void
    {
        $this->support->sendMessage($chatId, $screen['text'], 'HTML', $screen['markup'] ?? null);
    }

    /** The user's active Telegram ticket, or a fresh one. */
    private function openTicketFor(User $user, string $chatId, string $firstText): SupportTicket
    {
        $existing = SupportTicket::where('telegram_chat_id', $chatId)
            ->whereIn('status', self::OPEN_STATUSES)
            ->latest()
            ->first();
        if ($existing) {
            return $existing;
        }

        return SupportTicket::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'ticket_number' => $this->generateNumber(),
            'subject' => 'تلجرام: ' . Str::limit($firstText, 60),
            'description' => $firstText,
            'category' => 'general',
            'priority' => 'medium',
            'status' => 'open',
            'telegram_chat_id' => $chatId,
        ]);
    }

    private function generateNumber(): string
    {
        do {
            $number = 'TK-' . now()->format('ymd') . '-' . strtoupper(Str::random(4));
        } while (SupportTicket::where('ticket_number', $number)->exists());

        return $number;
    }
}
