<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Telegram support bot client (@SakkSupportBot) — a SEPARATE bot/token from the
 * OTP bot. Two-way bridge to the support-ticket desk: inbound messages become
 * ticket messages (see TelegramSupportController), agent replies are pushed back
 * here. Config under config/services.telegram_support; safe no-op when disabled.
 */
class TelegramSupportService
{
    public function configured(): bool
    {
        return !empty(config('services.telegram_support.bot_token'));
    }

    public function enabled(): bool
    {
        return (bool) config('services.telegram_support.enabled', false) && $this->configured();
    }

    /**
     * Send a message to a chat, optionally with an inline keyboard ([reply_markup]).
     * Never throws; logs + returns false on failure.
     */
    public function sendMessage(string $chatId, string $text, ?string $parseMode = 'HTML', ?array $replyMarkup = null): bool
    {
        if (!$this->enabled() || trim($chatId) === '') {
            return false;
        }

        $res = $this->call('sendMessage', array_filter([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode,
            'disable_web_page_preview' => true,
            'reply_markup' => $replyMarkup !== null ? json_encode($replyMarkup) : null,
        ], fn ($v) => $v !== null));

        if ($res !== null && ($res['ok'] ?? false)) {
            return true;
        }
        Log::error('Telegram support send failed', ['error' => $res['description'] ?? 'unreachable']);
        return false;
    }

    /**
     * Edit a message in place (used for menu navigation so taps don't spam the
     * chat). Falls back gracefully — callers ignore the boolean.
     */
    public function editMessageText(string $chatId, int $messageId, string $text, ?array $replyMarkup = null, ?string $parseMode = 'HTML'): bool
    {
        if (!$this->enabled()) {
            return false;
        }

        $res = $this->call('editMessageText', array_filter([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => $parseMode,
            'disable_web_page_preview' => true,
            'reply_markup' => $replyMarkup !== null ? json_encode($replyMarkup) : null,
        ], fn ($v) => $v !== null));

        return (bool) ($res['ok'] ?? false);
    }

    /** Acknowledge a button tap so Telegram stops the loading spinner. */
    public function answerCallbackQuery(string $callbackId, ?string $text = null): bool
    {
        $res = $this->call('answerCallbackQuery', array_filter([
            'callback_query_id' => $callbackId,
            'text' => $text,
        ], fn ($v) => $v !== null));

        return (bool) ($res['ok'] ?? false);
    }

    public function getMe(): ?array
    {
        $res = $this->call('getMe');
        return ($res['ok'] ?? false) ? ($res['result'] ?? null) : null;
    }

    public function setWebhook(string $url): array
    {
        return $this->call('setWebhook', array_filter([
            'url' => $url,
            'secret_token' => (string) config('services.telegram_support.webhook_secret'),
            'allowed_updates' => json_encode(['message', 'callback_query']),
        ])) ?? ['ok' => false, 'description' => 'unreachable'];
    }

    public function getWebhookInfo(): ?array
    {
        $res = $this->call('getWebhookInfo');
        return ($res['ok'] ?? false) ? ($res['result'] ?? null) : null;
    }

    public function setMyName(string $name): bool
    {
        return (bool) ($this->call('setMyName', ['name' => $name])['ok'] ?? false);
    }

    public function setMyDescription(string $description): bool
    {
        return (bool) ($this->call('setMyDescription', ['description' => $description])['ok'] ?? false);
    }

    public function setMyShortDescription(string $text): bool
    {
        return (bool) ($this->call('setMyShortDescription', ['short_description' => $text])['ok'] ?? false);
    }

    public function setMyCommands(array $commands): bool
    {
        return (bool) ($this->call('setMyCommands', ['commands' => json_encode($commands)])['ok'] ?? false);
    }

    private function call(string $method, array $params = []): ?array
    {
        $token = (string) config('services.telegram_support.bot_token');
        if ($token === '') {
            return null;
        }
        $base = rtrim((string) config('services.telegram_support.api_base', 'https://api.telegram.org'), '/');
        try {
            return Http::timeout((int) config('services.telegram_support.timeout', 15))
                ->acceptJson()->asForm()
                ->post("{$base}/bot{$token}/{$method}", $params)
                ->json();
        } catch (\Throwable $e) {
            Log::error('Telegram support API exception', ['method' => $method, 'error' => $e->getMessage()]);
            return null;
        }
    }
}
