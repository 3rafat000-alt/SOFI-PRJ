<?php

use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/**
 * Biometric verify bypass (closed P0.13) — regression lock.
 *
 * The biometric routes live under auth:sanctum at prefix /api/v1/auth/biometric/*
 * (routes/api.php:66,87-93): POST .../challenge issues a single-use server challenge,
 * POST .../verify presents a device signature over it.
 *
 * THE BYPASS THESE TESTS LOCK SHUT:
 *   BiometricController::verify previously accepted ANY non-empty `signature` as long as
 *   a challenge was cached — it never loaded the device, never checked the signature
 *   against the registered public_key, and minted a NEVER-EXPIRING Sanctum token
 *   (config/sanctum.php 'expiration' => null). Any holder of a session token could mint a
 *   permanent privileged token with `signature=<anything>`.
 *
 * THE FIX (verified here): verify() now delegates to the canonical fail-closed verifier
 * App\Http\Controllers\Concerns\VerifiesTransactionAuth::verifyBiometricToken — Ed25519
 * (ext-sodium) / RSA-EC (ext-openssl) signature verification over the cached challenge,
 * single-use challenge consume, trusted-device requirement — and mints a SHORT-LIVED
 * token (createToken('biometric-auth', ['*'], now()->addMinutes(15))) only on success.
 *
 * Each test asserts the security contract directly:
 *   - a forged / replayed / wrong-message signature, an unknown device, or a missing
 *     challenge => 4xx, success=false, and NO Sanctum personal_access_token is created
 *     ("no privileged action unlocked");
 *   - a real Ed25519 signature over the exact issued challenge => 200 + a short-lived,
 *     single-use token.
 *
 * Signatures are minted in-process with ext-sodium (verified available: ed25519 pk=32B,
 * sig=64B) — no new runtime deps, real cryptography, no mocking of the verifier.
 */

/**
 * Generate a fresh Ed25519 keypair for a test device.
 *
 * @return array{0:string,1:string} [base64 public key, raw secret key]
 */
function bioKeypair(): array
{
    $kp = sodium_crypto_sign_keypair();

    return [
        base64_encode(sodium_crypto_sign_publickey($kp)),
        sodium_crypto_sign_secretkey($kp),
    ];
}

/**
 * Register a trusted signing device for the user (the only thing verifyBiometricToken
 * requires: is_trusted=true + a non-empty public_key). The biometric routes do NOT use
 * EnsureDeviceCanTransact, so the 48h transaction hold is irrelevant here.
 */
function bioDevice(User $user, string $deviceId, string $publicKeyB64): Device
{
    return $user->devices()->create([
        'device_id' => $deviceId,
        'device_name' => 'Phone',
        'device_type' => 'ios',
        'public_key' => $publicKeyB64,
        'is_trusted' => true,
        'status' => Device::STATUS_APPROVED,
    ]);
}

/**
 * Hit the real challenge endpoint and return the issued challenge string.
 * challenge() requires a trusted device with this device_id else 404 'الجهاز غير معروف'.
 */
function bioChallenge($test, string $deviceId): string
{
    $res = $test->postJson('/api/v1/auth/biometric/challenge', ['device_id' => $deviceId]);
    $res->assertStatus(200);

    return (string) $res->json('data.challenge');
}

// ──────────────── T4.1 — forged signature is rejected, no token minted ────────────────

it('rejects verify with a forged signature and mints no token', function () {
    [$pkB64] = bioKeypair();
    $user = User::factory()->create();
    bioDevice($user, 'dev-bio-1', $pkB64);

    Sanctum::actingAs($user);
    bioChallenge($this, 'dev-bio-1'); // a real challenge is cached…

    // …but the signature is garbage (valid hex so it decodes, but is not a real signature).
    $res = $this->postJson('/api/v1/auth/biometric/verify', [
        'device_id' => 'dev-bio-1',
        'signature' => 'deadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeef',
    ]);

    expect($res->status())->toBeGreaterThanOrEqual(400);
    $res->assertJsonPath('success', false);
    expect($res->json('data.token'))->toBeNull();

    // "No privileged action unlocked": the bypass did NOT silently mint a PAT.
    expect($user->tokens()->count())->toBe(0);
});

// ──────────────── T4.2 — no challenge issued → rejected ────────────────

it('rejects verify when no challenge has been issued', function () {
    [$pkB64] = bioKeypair();
    $user = User::factory()->create();
    bioDevice($user, 'dev-bio-1', $pkB64);

    Sanctum::actingAs($user);
    // Deliberately do NOT request a challenge.

    $res = $this->postJson('/api/v1/auth/biometric/verify', [
        'device_id' => 'dev-bio-1',
        'signature' => 'deadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeef',
    ]);

    expect($res->status())->toBeGreaterThanOrEqual(400);
    $res->assertJsonPath('success', false);
    expect($res->json('data.token'))->toBeNull();
    expect($user->tokens()->count())->toBe(0);
});

// ──────────────── T4.3 — valid sig over the WRONG challenge → rejected ────────────────

it('rejects verify with a valid signature over a different message (no replay/wrong-challenge)', function () {
    [$pkB64, $sk] = bioKeypair();
    $user = User::factory()->create();
    bioDevice($user, 'dev-bio-1', $pkB64);

    Sanctum::actingAs($user);
    bioChallenge($this, 'dev-bio-1'); // C1 is cached…

    // …but we sign a DIFFERENT message with the correct key. signatureMatches() binds to
    // the exact server challenge, so this must fail.
    $sig = sodium_crypto_sign_detached('not-the-challenge', $sk);

    $res = $this->postJson('/api/v1/auth/biometric/verify', [
        'device_id' => 'dev-bio-1',
        'signature' => base64_encode($sig),
    ]);

    expect($res->status())->toBeGreaterThanOrEqual(400);
    $res->assertJsonPath('success', false);
    expect($res->json('data.token'))->toBeNull();
    expect($user->tokens()->count())->toBe(0);
});

// ──────────────── T4.4 — unknown / untrusted device → rejected ────────────────

it('rejects verify for an unknown or untrusted device', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    // Seed a challenge directly to isolate the device check (challenge() itself refuses
    // to issue for an untrusted/unknown device, so we bypass it to prove verify() also
    // rejects when no trusted device matches the supplied device_id).
    cache()->put('biometric_challenge:' . $user->id, bin2hex(random_bytes(32)), now()->addMinutes(5));

    $res = $this->postJson('/api/v1/auth/biometric/verify', [
        'device_id' => 'ghost-device',
        'signature' => 'deadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeef',
    ]);

    expect($res->status())->toBeGreaterThanOrEqual(400);
    $res->assertJsonPath('success', false);
    expect($res->json('data.token'))->toBeNull();
    expect($user->tokens()->count())->toBe(0);
});

// ──────────────── T4.5 — valid signature mints a short-lived, single-use token ────────────────

it('mints a short-lived single-use token only on a valid signature over the issued challenge', function () {
    [$pkB64, $sk] = bioKeypair();
    $user = User::factory()->create();
    bioDevice($user, 'dev-bio-1', $pkB64);

    Sanctum::actingAs($user);
    $challenge = bioChallenge($this, 'dev-bio-1');

    // Sign the EXACT challenge the server issued.
    $sig = base64_encode(sodium_crypto_sign_detached($challenge, $sk));

    $res = $this->postJson('/api/v1/auth/biometric/verify', [
        'device_id' => 'dev-bio-1',
        'signature' => $sig,
    ]);

    $res->assertStatus(200);
    $res->assertJsonPath('success', true);
    expect($res->json('data.token'))->toBeString()->not->toBeEmpty();

    // Exactly one PAT, and it is SHORT-LIVED (not the never-expiring footgun).
    expect($user->tokens()->count())->toBe(1);
    $pat = $user->tokens()->first();
    expect($pat->name)->toBe('biometric-auth');
    expect($pat->expires_at)->not->toBeNull();
    expect($pat->expires_at->isFuture())->toBeTrue();

    // Single-use: replaying the SAME valid signature fails (challenge was consumed) and
    // does not mint a second token.
    $replay = $this->postJson('/api/v1/auth/biometric/verify', [
        'device_id' => 'dev-bio-1',
        'signature' => $sig,
    ]);

    expect($replay->status())->toBeGreaterThanOrEqual(400);
    $replay->assertJsonPath('success', false);
    expect($user->tokens()->count())->toBe(1);
});
