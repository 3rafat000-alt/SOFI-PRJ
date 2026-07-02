<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    public function getQrCodeUrl(User $user, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl(
            'صكك Wallet',
            $user->email,
            $secret
        );
    }

    public function verify(string $secret, string $code): bool
    {
        try {
            return $this->google2fa->verifyKey($secret, $code);
        } catch (\Exception) {
            return false;
        }
    }

    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(
                implode('-', [
                    Str::random(4),
                    Str::random(4),
                    Str::random(4),
                ])
            );
        }
        return $codes;
    }

    public function enable(User $user): array
    {
        $secret = $this->generateSecret();
        $recoveryCodes = $this->generateRecoveryCodes();

        // two_factor_* are guarded (not in $fillable) — set via forceFill at this
        // trusted site, otherwise mass-assignment silently drops them and the
        // secret is never persisted (2FA can then never be confirmed).
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $recoveryCodes,
            'two_factor_enabled' => false,
        ])->save();

        return [
            'secret' => $secret,
            'qr_code_url' => $this->getQrCodeUrl($user, $secret),
            'recovery_codes' => $recoveryCodes,
        ];
    }

    public function confirm(User $user, string $code): bool
    {
        if (!$user->two_factor_secret) {
            return false;
        }

        if (!$this->verify($user->two_factor_secret, $code)) {
            return false;
        }

        $user->forceFill(['two_factor_enabled' => true])->save();
        return true;
    }

    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_enabled' => false,
        ])->save();
    }

    public function verifyCode(User $user, string $code): bool
    {
        // Fail closed: if 2FA is not configured, verification fails.
        // The caller (AuthController) checks two_factor_enabled before
        // calling this method, so enforcement happens at the controller level.
        if (!$user->two_factor_enabled || !$user->two_factor_secret) {
            return false;
        }

        if ($this->verify($user->two_factor_secret, $code)) {
            return true;
        }

        return $this->verifyRecoveryCode($user, $code);
    }

    private function verifyRecoveryCode(User $user, string $code): bool
    {
        return DB::transaction(function () use ($user, $code) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->firstOrFail();

            $codes = $lockedUser->two_factor_recovery_codes ?? [];
            $index = array_search($code, $codes);

            if ($index === false) {
                return false;
            }

            unset($codes[$index]);
            $lockedUser->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

            return true;
        });
    }

    public function getRecoveryCodes(User $user): array
    {
        return $user->two_factor_recovery_codes ?? [];
    }

    public function regenerateRecoveryCodes(User $user): array
    {
        $codes = $this->generateRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $codes])->save();
        return $codes;
    }
}
