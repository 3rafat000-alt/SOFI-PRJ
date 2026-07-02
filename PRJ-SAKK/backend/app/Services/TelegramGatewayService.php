<?php

namespace App\Services;

use App\Support\PhoneNormalizer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Telegram Gateway API client — https://core.telegram.org/gateway/api
 *
 * Unlike the Bot API, the Gateway delivers a verification code straight to a
 * phone number's Telegram app WITHOUT the user pre-linking anything, and tells
 * us up-front whether that number even has Telegram (checkSendAbility). That is
 * exactly the "detect the app on the user's phone, send if present, else fall
 * back" behaviour — the auto-detect Telegram channel in the OTP chain.
 *
 * PAID: checkSendAbility/sendVerificationMessage are charged per number (free to
 * your own number). Gated behind TELEGRAM_GATEWAY_TOKEN — a no-op when absent.
 * Never throws; failures log + return false/null so OTP delivery falls through.
 */
class TelegramGatewayService
{
    private const BASE = 'https://gatewayapi.telegram.org';

    public function enabled(): bool
    {
        return (bool) config('services.telegram_gateway.enabled', false)
            && !empty(config('services.telegram_gateway.token'));
    }

    /**
     * Can this number receive a Telegram verification message (i.e. does the
     * user have Telegram)? Returns a request_id to reuse for a free send, or
     * null when the number is not on Telegram / the check failed.
     */
    public function checkSendAbility(string $phone): ?string
    {
        $e164 = $this->e164($phone);
        if ($e164 === null) {
            return null;
        }

        $res = $this->call('checkSendAbility', ['phone_number' => $e164]);
        if ($res !== null && ($res['ok'] ?? false)) {
            return $res['result']['request_id'] ?? null;
        }

        // ok:false simply means "not deliverable via Telegram" — expected, quiet.
        Log::info('Telegram Gateway: number not reachable', [
            'phone' => $this->mask($e164),
            'reason' => $res['error'] ?? 'unreachable',
        ]);
        return null;
    }

    /**
     * Deliver our own numeric code to the phone over Telegram. Pass the
     * request_id from checkSendAbility so the send is not charged twice.
     * Returns true when Telegram accepted the message for delivery.
     */
    public function sendCode(string $phone, string $code, ?string $requestId = null): bool
    {
        if (!$this->enabled()) {
            return false;
        }
        $e164 = $this->e164($phone);
        if ($e164 === null) {
            return false;
        }

        $res = $this->call('sendVerificationMessage', array_filter([
            'phone_number' => $e164,
            'request_id' => $requestId,
            'code' => $code, // our code (4-8 digits) — verification stays in our DB
            'ttl' => (int) config('services.telegram_gateway.ttl', 600),
        ]));

        if ($res !== null && ($res['ok'] ?? false)) {
            Log::info('Telegram Gateway code sent', ['phone' => $this->mask($e164)]);
            return true;
        }

        Log::error('Telegram Gateway send failed', [
            'phone' => $this->mask($e164),
            'error' => $res['error'] ?? 'unreachable',
        ]);
        return false;
    }

    /** Low-level Gateway call. Returns decoded JSON, or null on transport error. */
    private function call(string $method, array $params): ?array
    {
        $token = (string) config('services.telegram_gateway.token');
        if ($token === '') {
            return null;
        }
        try {
            return Http::withToken($token)
                ->timeout((int) config('services.telegram_gateway.timeout', 15))
                ->acceptJson()
                ->asForm()
                ->post(self::BASE . '/' . $method, $params)
                ->json();
        } catch (\Throwable $e) {
            Log::error('Telegram Gateway exception', ['method' => $method, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /** Stored phone → E.164 (`+<cc><national>`), or null when it has no digits. */
    private function e164(string $phone): ?string
    {
        $canonical = PhoneNormalizer::canonical($phone);
        return $canonical === '' ? null : '+' . $canonical;
    }

    private function mask(string $phone): string
    {
        return strlen($phone) <= 5
            ? $phone
            : substr($phone, 0, 4) . str_repeat('*', strlen($phone) - 6) . substr($phone, -2);
    }
}
