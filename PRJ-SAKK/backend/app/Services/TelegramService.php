<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Telegram Bot API client — a second OTP delivery channel alongside WhatsApp.
 *
 * A bot cannot message a user who has not started it, so delivery requires a
 * one-time link: the user opens `t.me/<bot>?start=<token>`, the bot receives
 * `/start <token>` on its webhook, and we bind that chat id to the account
 * (see TelegramController::webhook). Once bound, OTPs go to the chat id.
 *
 * All config lives under config/services.php ('telegram'); when disabled or
 * misconfigured every send is a safe no-op returning false, so the caller
 * (OTP issuance) is never blocked. Mirrors WhatsAppService's contract.
 */
class TelegramService
{
    /** Cache key prefix + TTL for the short-lived account-link tokens. */
    private const LINK_PREFIX = 'tg_link:';
    private const LINK_TTL_MINUTES = 15;

    /** True when a bot token is present — enough to talk to the Bot API. */
    public function configured(): bool
    {
        return !empty(config('services.telegram.bot_token'));
    }

    /** Sending OTP is allowed only when explicitly enabled and configured. */
    public function enabled(): bool
    {
        return (bool) config('services.telegram.enabled', false) && $this->configured();
    }

    /**
     * Send a plain-text Telegram message to a chat id. Returns true on success.
     * Never throws — failures are logged and reported as false so OTP issuance
     * is never blocked by a Telegram outage.
     */
    public function sendMessage(string $chatId, string $text, ?string $parseMode = null): bool
    {
        if (!$this->enabled()) {
            Log::warning('Telegram channel disabled — message not sent');
            return false;
        }
        if (trim($chatId) === '') {
            Log::warning('Telegram send skipped — empty chat id');
            return false;
        }

        $res = $this->call('sendMessage', array_filter([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode,
            'disable_web_page_preview' => true,
        ], fn ($v) => $v !== null));

        if ($res !== null && ($res['ok'] ?? false)) {
            Log::info('Telegram message sent', ['chat' => $this->mask($chatId)]);
            return true;
        }

        Log::error('Telegram send failed', [
            'chat' => $this->mask($chatId),
            'error' => $res['description'] ?? 'unreachable',
        ]);
        return false;
    }

    /** Bot identity (getMe) for admin/status — null when unreachable. */
    public function getMe(): ?array
    {
        $res = $this->call('getMe');
        return ($res['ok'] ?? false) ? ($res['result'] ?? null) : null;
    }

    /** Register the webhook URL (with a secret token Telegram echoes back). */
    public function setWebhook(string $url): array
    {
        $res = $this->call('setWebhook', array_filter([
            'url' => $url,
            'secret_token' => (string) config('services.telegram.webhook_secret'),
            'allowed_updates' => json_encode(['message']),
        ]));
        return $res ?? ['ok' => false, 'description' => 'unreachable'];
    }

    /** Current webhook registration (getWebhookInfo) — null when unreachable. */
    public function getWebhookInfo(): ?array
    {
        $res = $this->call('getWebhookInfo');
        return ($res['ok'] ?? false) ? ($res['result'] ?? null) : null;
    }

    // ── Bot branding (one-shot, run via `php artisan telegram:brand`) ──

    /** Display name shown in the chat header (setMyName). */
    public function setMyName(string $name): bool
    {
        return (bool) ($this->call('setMyName', ['name' => $name])['ok'] ?? false);
    }

    /** Long "About" text on the empty-chat / profile screen before /start. */
    public function setMyDescription(string $description): bool
    {
        return (bool) ($this->call('setMyDescription', ['description' => $description])['ok'] ?? false);
    }

    /** One-line blurb shown in search / shared links (setMyShortDescription). */
    public function setMyShortDescription(string $text): bool
    {
        return (bool) ($this->call('setMyShortDescription', ['short_description' => $text])['ok'] ?? false);
    }

    /**
     * Command menu (the "/" button). $commands = [['command'=>'start','description'=>'…'], …].
     */
    public function setMyCommands(array $commands): bool
    {
        return (bool) ($this->call('setMyCommands', ['commands' => json_encode($commands)])['ok'] ?? false);
    }

    /**
     * Build the deep link a user taps to bind their Telegram chat to this
     * account. Returns null when the bot username is not configured.
     */
    public function deepLink(User $user): ?string
    {
        $username = (string) config('services.telegram.bot_username');
        if ($username === '') {
            return null;
        }
        $token = $this->makeLinkToken($user);
        return 'https://t.me/' . ltrim($username, '@') . '?start=' . $token;
    }

    /**
     * Mint a one-time link token bound to the user. Token is alphanumeric and
     * <=64 chars to satisfy Telegram's `start` deep-link parameter rules.
     */
    public function makeLinkToken(User $user): string
    {
        $token = Str::random(40);
        Cache::put(self::LINK_PREFIX . $token, $user->id, now()->addMinutes(self::LINK_TTL_MINUTES));
        return $token;
    }

    /** Atomically consume a link token, returning the bound user id or null. */
    public function consumeLinkToken(string $token): ?int
    {
        $id = Cache::pull(self::LINK_PREFIX . $token);
        return $id ? (int) $id : null;
    }

    /** Low-level Bot API call. Returns decoded JSON, or null on transport error. */
    private function call(string $method, array $params = []): ?array
    {
        $token = (string) config('services.telegram.bot_token');
        if ($token === '') {
            return null;
        }
        $base = rtrim((string) config('services.telegram.api_base', 'https://api.telegram.org'), '/');

        try {
            return Http::timeout((int) config('services.telegram.timeout', 15))
                ->acceptJson()
                ->asForm()
                ->post("{$base}/bot{$token}/{$method}", $params)
                ->json();
        } catch (\Throwable $e) {
            Log::error('Telegram API exception', ['method' => $method, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /** Mask a chat id for logs (keep first 3 / last 2). */
    private function mask(string $chatId): string
    {
        if (strlen($chatId) <= 5) {
            return $chatId;
        }
        return substr($chatId, 0, 3) . str_repeat('*', strlen($chatId) - 5) . substr($chatId, -2);
    }
}
