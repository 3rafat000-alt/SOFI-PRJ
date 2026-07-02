<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Second-factor authorization for money-moving endpoints.
 *
 * Historically these endpoints accepted a `biometric_token` field whose mere
 * presence skipped the PIN check entirely — but nothing ever validated that
 * token (the mobile client sent the literal string "biometric"). That made the
 * "second factor" a no-op: any holder of a session token could move funds with
 * `biometric_token=<anything>` and never prove possession of the device or PIN.
 *
 * This trait restores a real second factor that FAILS CLOSED:
 *   - If a biometric signature is presented, it is cryptographically verified
 *     against the user's registered device public key over a fresh, single-use,
 *     server-issued challenge (the same challenge BiometricController issues).
 *   - Otherwise a valid PIN is required.
 *   - If NEITHER is validly presented, the request is rejected — money never
 *     moves on an unverified factor.
 *
 * No new runtime dependencies: signature verification uses ext-openssl
 * (RSA / EC PEM keys) with an ext-sodium Ed25519 fallback, both bundled with PHP.
 */
trait VerifiesTransactionAuth
{
    /**
     * Verify that the request carries a valid second factor (verified biometric
     * signature OR correct PIN). Returns true on success.
     *
     * Fails closed: returns false unless exactly one factor is validly presented
     * and passes verification.
     */
    protected function verifyTransactionFactor(Request $request, User $user): bool
    {
        // Prefer an explicitly presented PIN.
        if ($request->filled('pin')) {
            return $user->verifyPin((string) $request->input('pin'));
        }

        // Otherwise require a cryptographically verified biometric signature.
        if ($request->filled('biometric_token')) {
            return $this->verifyBiometricToken(
                $user,
                (string) $request->input('biometric_token'),
                $request->header('X-Device-Id'),
            );
        }

        // Neither factor presented → reject.
        return false;
    }

    /**
     * Verify a biometric authorization token: a base64/hex signature produced by
     * the device signing the server-issued challenge with the private key whose
     * public half was registered via BiometricController::registerDevice.
     *
     * The challenge is the one cached by BiometricController::challenge under
     * "biometric_challenge:{user_id}" (5-minute TTL). It is SINGLE-USE: a
     * successful verification consumes it, so a captured signature cannot be
     * replayed.
     *
     * Fails closed on any missing/invalid input — returns false, never throws.
     */
    protected function verifyBiometricToken(User $user, ?string $token, ?string $deviceId): bool
    {
        $token = is_string($token) ? trim($token) : '';
        $deviceId = is_string($deviceId) ? trim($deviceId) : '';

        if ($token === '' || $deviceId === '') {
            return false;
        }

        // The signing device must be one of the user's registered, trusted devices.
        $device = $user->devices()
            ->where('device_id', $deviceId)
            ->where('is_trusted', true)
            ->first();

        if (!$device || empty($device->public_key)) {
            return false;
        }

        // A fresh challenge must have been issued (and not yet consumed).
        $cacheKey = 'biometric_challenge:' . $user->id;
        $challenge = cache()->get($cacheKey);
        if (!is_string($challenge) || $challenge === '') {
            return false;
        }

        $signature = $this->decodeSignature($token);
        if ($signature === null) {
            return false;
        }

        if (!$this->signatureMatches($device->public_key, $challenge, $signature)) {
            return false;
        }

        // Single-use: consume the challenge so the signature cannot be replayed.
        cache()->forget($cacheKey);

        // Touch the device for audit/last-seen parity with the rest of the app.
        $device->forceFill(['last_used_at' => now()])->save();

        return true;
    }

    /**
     * Decode a signature that may arrive base64-encoded (preferred) or hex.
     * Returns the raw binary signature, or null if it is neither.
     */
    private function decodeSignature(string $token): ?string
    {
        // Hex (even length, hex chars only).
        if (preg_match('/^[0-9a-fA-F]+$/', $token) && strlen($token) % 2 === 0) {
            $bin = @hex2bin($token);
            if ($bin !== false && $bin !== '') {
                return $bin;
            }
        }

        // Base64 / base64url.
        $normalized = strtr($token, '-_', '+/');
        $bin = base64_decode($normalized, true);
        if ($bin !== false && $bin !== '') {
            return $bin;
        }

        return null;
    }

    /**
     * Verify $signature over $challenge using $publicKey.
     *
     * Supports PEM public keys via ext-openssl (RSA + ECDSA) and raw 32-byte
     * Ed25519 keys (base64/hex) via ext-sodium. Constant-effort, fail-closed.
     */
    private function signatureMatches(string $publicKey, string $challenge, string $signature): bool
    {
        $publicKey = trim($publicKey);

        // PEM-encoded key → OpenSSL (RSA / EC). Try the common digests.
        if (str_contains($publicKey, '-----BEGIN')) {
            $key = @openssl_pkey_get_public($publicKey);
            if ($key === false) {
                return false;
            }
            foreach ([OPENSSL_ALGO_SHA256, OPENSSL_ALGO_SHA512] as $algo) {
                $result = @openssl_verify($challenge, $signature, $key, $algo);
                if ($result === 1) {
                    return true;
                }
            }
            return false;
        }

        // Otherwise treat it as a raw Ed25519 public key (32 bytes, base64/hex).
        if (function_exists('sodium_crypto_sign_verify_detached')) {
            $rawKey = $this->decodeSignature($publicKey);
            if ($rawKey !== null
                && strlen($rawKey) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
                && strlen($signature) === SODIUM_CRYPTO_SIGN_BYTES) {
                try {
                    return sodium_crypto_sign_verify_detached($signature, $challenge, $rawKey);
                } catch (\SodiumException) {
                    return false;
                }
            }
        }

        return false;
    }
}
