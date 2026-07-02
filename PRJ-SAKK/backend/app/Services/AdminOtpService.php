<?php

namespace App\Services;

use App\Mail\VerificationCodeMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AdminOtpService
{
    private const OTP_TTL = 300;
    private const PENDING_TTL = 600;

    public function generate(): string
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put($this->otpKey(), $otp, self::OTP_TTL);
        return $otp;
    }

    public function send(string $email): void
    {
        $otp = $this->generate();
        Mail::to($email)->send(new VerificationCodeMail($otp));
    }

    public function verify(string $code): bool
    {
        $stored = Cache::get($this->otpKey());
        if (!$stored || $stored !== $code) {
            return false;
        }
        Cache::forget($this->otpKey());
        return true;
    }

    public function hasValidOtp(): bool
    {
        return Cache::has($this->otpKey());
    }

    public function storePending(string $token, array $data): void
    {
        Cache::put($this->pendingKey($token), $data, self::PENDING_TTL);
    }

    public function getPending(string $token): ?array
    {
        return Cache::get($this->pendingKey($token));
    }

    public function clearPending(string $token): void
    {
        Cache::forget($this->pendingKey($token));
    }

    public function generateToken(): string
    {
        return Str::random(40);
    }

    private function otpKey(): string
    {
        return 'admin_otp:' . auth()->id();
    }

    private function pendingKey(string $token): string
    {
        return 'admin_pending:' . auth()->id() . ':' . $token;
    }
}
