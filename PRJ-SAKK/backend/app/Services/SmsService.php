<?php

namespace App\Services;

use App\Support\PhoneNormalizer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SMS OTP channel — last link in the delivery chain (Telegram → WhatsApp → SMS).
 *
 * Provider-agnostic stub: wired to a generic HTTP gateway via config so a real
 * provider (Twilio, a local Syrian SMS aggregator, etc.) drops in later without
 * touching the dispatcher. Disabled until SMS_OTP_ENABLED=true + an endpoint is
 * set, so today it is a safe no-op returning false ("soon"). Never throws.
 */
class SmsService
{
    public function enabled(): bool
    {
        return (bool) config('services.sms.enabled', false)
            && !empty(config('services.sms.endpoint'));
    }

    /** Send a plain-text SMS. Returns true on apparent success. */
    public function sendText(string $phone, string $text): bool
    {
        if (!$this->enabled()) {
            return false; // "soon" — no provider configured yet
        }

        $to = PhoneNormalizer::canonical($phone);
        if ($to === '') {
            Log::warning('SMS send skipped — empty/invalid phone');
            return false;
        }

        try {
            $res = Http::withToken((string) config('services.sms.token'))
                ->timeout((int) config('services.sms.timeout', 15))
                ->asForm()
                ->post((string) config('services.sms.endpoint'), [
                    'to' => $to,
                    'text' => $text,
                    'from' => config('services.sms.sender', 'SAKK'),
                ]);

            if ($res->successful()) {
                Log::info('SMS sent', ['phone' => $this->mask($to)]);
                return true;
            }
            Log::error('SMS send failed', ['status' => $res->status(), 'phone' => $this->mask($to)]);
            return false;
        } catch (\Throwable $e) {
            Log::error('SMS send exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function mask(string $phone): string
    {
        return strlen($phone) <= 5
            ? $phone
            : substr($phone, 0, 4) . str_repeat('*', strlen($phone) - 6) . substr($phone, -2);
    }
}
