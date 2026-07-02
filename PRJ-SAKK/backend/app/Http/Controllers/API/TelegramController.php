<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Telegram OTP channel — account linking + inbound webhook.
 *
 *  - link/status/unlink : authenticated account endpoints (mobile/web).
 *  - webhook            : public; Telegram pushes `/start <token>` here so we
 *                         can bind the user's chat id. Secret-token guarded.
 */
class TelegramController extends Controller
{
    /** Branded bot replies (Telegram HTML parse mode). */
    private const WELCOME = "<b>صكّ · SAKK</b>\n\n👋 أهلاً بك في بوت رموز التحقق.\n\nلربط حسابك واستقبال رموز الدخول هنا:\n<b>التطبيق ← الأمان ← ربط تلجرام</b>\n\n🔒 لن نطلب منك كلمة المرور أو رمز التحقق أبداً.";

    private const LINKED = "✅ <b>تم ربط حسابك بنجاح</b>\n\nستصلك رموز التحقق الخاصة بـ <b>صكّ</b> هنا مباشرةً.\n\n🔒 لا تشارك أي رمز مع أحد — فريق صكّ لن يطلبه منك.";

    private const INVALID = "⚠️ <b>رابط غير صالح أو منتهي الصلاحية</b>\n\nروابط الربط تنتهي خلال 15 دقيقة.\nأنشئ رابطاً جديداً من:\n<b>التطبيق ← الأمان ← ربط تلجرام</b>";

    public function __construct(private readonly TelegramService $telegram)
    {
    }

    /** Deep link the user taps to bind their Telegram chat to this account. */
    public function link(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$this->telegram->configured()) {
            return response()->json(['message' => 'قناة تلجرام غير مُهيّأة'], 422);
        }

        $deepLink = $this->telegram->deepLink($user);
        if ($deepLink === null) {
            return response()->json(['message' => 'اسم بوت تلجرام غير مضبوط على الخادم'], 422);
        }

        return response()->json([
            'deep_link' => $deepLink,
            'bot_username' => config('services.telegram.bot_username'),
            'linked' => $user->telegram_chat_id !== null,
            'expires_in_minutes' => 15,
        ]);
    }

    /** Current link state for the authenticated user. */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'linked' => $user->telegram_chat_id !== null,
            'username' => $user->telegram_username,
            'linked_at' => $user->telegram_linked_at?->toIso8601String(),
        ]);
    }

    /** Unbind the Telegram chat from the account. */
    public function unlink(Request $request): JsonResponse
    {
        $request->user()->forceFill([
            'telegram_chat_id' => null,
            'telegram_username' => null,
            'telegram_linked_at' => null,
        ])->save();

        return response()->json(['message' => 'تم إلغاء ربط تلجرام']);
    }

    /**
     * Inbound Telegram webhook. Binds a chat to an account on `/start <token>`.
     * Always returns 200 so Telegram does not retry-storm on our errors.
     */
    public function webhook(Request $request): JsonResponse
    {
        // SEC M7: FAIL CLOSED. With no secret configured the webhook would be fully
        // unauthenticated (anyone could POST forged /start updates). Reject when the
        // secret is unset OR the header does not match. The admin "set webhook" flow
        // registers the secret with Telegram, so a configured channel always sends it.
        $secret = (string) config('services.telegram.webhook_secret');
        if ($secret === '' || !hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token'))) {
            Log::warning('Telegram webhook rejected — secret missing or invalid');
            return response()->json(['ok' => true]); // do not leak the reason
        }

        $message = $request->input('message', []);
        $chatId = $message['chat']['id'] ?? null;
        $text = trim((string) ($message['text'] ?? ''));

        if ($chatId === null) {
            return response()->json(['ok' => true]);
        }

        // Only the link command is actionable.
        if (!str_starts_with($text, '/start')) {
            return response()->json(['ok' => true]);
        }

        $token = trim(substr($text, strlen('/start')));
        if ($token === '') {
            $this->telegram->sendMessage((string) $chatId, self::WELCOME, 'HTML');
            return response()->json(['ok' => true]);
        }

        $userId = $this->telegram->consumeLinkToken($token);
        $user = $userId ? User::find($userId) : null;

        if (!$user) {
            $this->telegram->sendMessage((string) $chatId, self::INVALID, 'HTML');
            return response()->json(['ok' => true]);
        }

        $user->forceFill([
            'telegram_chat_id' => (string) $chatId,
            'telegram_username' => $message['from']['username'] ?? null,
            'telegram_linked_at' => now(),
        ])->save();

        Log::info('Telegram account linked', ['user_id' => $user->id]);
        $this->telegram->sendMessage((string) $chatId, self::LINKED, 'HTML');

        return response()->json(['ok' => true]);
    }
}
