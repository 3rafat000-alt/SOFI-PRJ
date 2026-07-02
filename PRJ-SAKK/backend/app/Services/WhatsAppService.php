<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp gateway client — talks to a self-hosted OpenWA REST gateway
 * (https://github.com/rmyndharis/OpenWA) to send plain-text messages.
 *
 * Used as the OTP delivery channel for phone verification. All config lives
 * under config/services.php ('whatsapp'); when disabled or misconfigured every
 * call is a safe no-op that returns false (the caller keeps working).
 */
class WhatsAppService
{
    /** Gateway is usable only when explicitly enabled and fully configured. */
    public function enabled(): bool
    {
        $c = config('services.whatsapp');

        return (bool) ($c['enabled'] ?? false)
            && !empty($c['base_url'])
            && !empty($c['session_id']);
    }

    /**
     * Normalize a stored phone number into a WhatsApp chatId (`<digits>@c.us`).
     *
     * Handles every stored format:
     *  - E.164 with +        (+963912345678   -> 963912345678@c.us)
     *  - International 00     (00963933111222  -> 963933111222@c.us)
     *  - Local trunk zero     (0982183111      -> 963982183111@c.us)
     *  - Bare national        (982183111       -> 963982183111@c.us)
     *  - Already prefixed      (963966878924    -> 963966878924@c.us)
     * Returns null when the input has no digits.
     */
    public function chatId(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits === '') {
            return null;
        }

        $cc = (string) config('services.whatsapp.default_country', '963');

        if (str_starts_with($digits, '00')) {
            // International access prefix — the rest already carries a country code.
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            // Local trunk zero — strip it, country code is prepended below.
            $digits = ltrim($digits, '0');
        }

        if ($cc !== '' && !str_starts_with($digits, $cc)) {
            $digits = $cc . $digits;
        }

        return $digits . '@c.us';
    }

    /**
     * Send a plain-text WhatsApp message. Returns true on apparent success.
     * Never throws — failures are logged and reported as a false return so the
     * surrounding flow (e.g. OTP issuance) is never blocked by gateway issues.
     */
    public function sendText(string $phone, string $text): bool
    {
        if (!$this->enabled()) {
            Log::warning('WhatsApp gateway disabled — message not sent');
            return false;
        }

        $chatId = $this->chatId($phone);
        if ($chatId === null) {
            Log::warning('WhatsApp send skipped — empty/invalid phone');
            return false;
        }

        $base = rtrim((string) config('services.whatsapp.base_url'), '/');
        $session = config('services.whatsapp.session_id');

        try {
            $res = Http::withHeaders(['X-API-Key' => (string) config('services.whatsapp.api_key')])
                ->timeout((int) config('services.whatsapp.timeout', 15))
                ->acceptJson()
                ->post("{$base}/api/sessions/{$session}/messages/send-text", [
                    'chatId' => $chatId,
                    'text' => $text,
                ]);

            if ($res->successful()) {
                Log::info('WhatsApp message sent', ['chatId' => $this->mask($chatId)]);
                return true;
            }

            Log::error('WhatsApp send failed', [
                'status' => $res->status(),
                'body' => mb_substr($res->body(), 0, 500),
                'chatId' => $this->mask($chatId),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('WhatsApp send exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /** Mask the numeric part of a chatId for logs (keep first 4 / last 2). */
    private function mask(string $chatId): string
    {
        $d = explode('@', $chatId)[0];
        if (strlen($d) <= 6) {
            return $d;
        }
        return substr($d, 0, 4) . str_repeat('*', strlen($d) - 6) . substr($d, -2);
    }
}
