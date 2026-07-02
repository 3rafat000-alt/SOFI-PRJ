<?php

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

/**
 * Regression guard for the 2FA controls (the bypass fixed in TKT-011):
 *   - verifyCode() must FAIL CLOSED unless 2FA is fully configured + enabled.
 *   - a recovery code is single-use (consumed on first success).
 *   - confirm() needs both a stored secret and a valid TOTP before enabling.
 *   - disable() wipes secret, recovery codes and the enabled flag.
 */

function tfaUserWithSecret(string $secret, bool $enabled = true): User
{
    return User::factory()->create([
        'two_factor_secret' => $secret,
        'two_factor_enabled' => $enabled,
        'two_factor_recovery_codes' => ['AAAA-BBBB-CCCC', 'DDDD-EEEE-FFFF'],
    ]);
}

it('verifyCode fails closed when 2FA is not enabled (even with a valid code)', function () {
    $svc = new TwoFactorService();
    $secret = $svc->generateSecret();
    $validCode = (new Google2FA())->getCurrentOtp($secret);

    // Secret present, but two_factor_enabled = false → must still reject.
    $user = tfaUserWithSecret($secret, enabled: false);

    expect($svc->verifyCode($user, $validCode))->toBeFalse();
});

it('verifyCode fails closed when no secret is configured', function () {
    $svc = new TwoFactorService();
    $user = User::factory()->create([
        'two_factor_enabled' => true,
        'two_factor_secret' => null,
    ]);

    expect($svc->verifyCode($user, '000000'))->toBeFalse();
});

it('verifyCode accepts a valid TOTP when enabled and configured', function () {
    $svc = new TwoFactorService();
    $secret = $svc->generateSecret();
    $user = tfaUserWithSecret($secret);

    $validCode = (new Google2FA())->getCurrentOtp($secret);

    expect($svc->verifyCode($user, $validCode))->toBeTrue();
});

it('verifyCode rejects an invalid TOTP', function () {
    $svc = new TwoFactorService();
    $secret = $svc->generateSecret();
    $user = tfaUserWithSecret($secret);

    expect($svc->verifyCode($user, '123456'))->toBeFalse();
});

it('verifyCode accepts a recovery code once, then never again (single-use)', function () {
    $svc = new TwoFactorService();
    $user = tfaUserWithSecret($svc->generateSecret());

    // First use of a valid recovery code succeeds.
    expect($svc->verifyCode($user, 'AAAA-BBBB-CCCC'))->toBeTrue();

    // It is consumed — a replay of the same code fails.
    expect($svc->verifyCode($user->fresh(), 'AAAA-BBBB-CCCC'))->toBeFalse();

    // The other code is still valid.
    expect($svc->verifyCode($user->fresh(), 'DDDD-EEEE-FFFF'))->toBeTrue();
});

it('confirm requires a stored secret and a valid code before enabling', function () {
    $svc = new TwoFactorService();

    // No secret → cannot confirm.
    $noSecret = User::factory()->create(['two_factor_secret' => null, 'two_factor_enabled' => false]);
    expect($svc->confirm($noSecret, '000000'))->toBeFalse();

    // Secret set but wrong code → cannot confirm, stays disabled.
    $secret = $svc->generateSecret();
    $user = User::factory()->create([
        'two_factor_secret' => $secret,
        'two_factor_enabled' => false,
    ]);
    expect($svc->confirm($user, '123456'))->toBeFalse();
    expect($user->fresh()->two_factor_enabled)->toBeFalse();

    // Valid code → confirmed + enabled.
    $validCode = (new Google2FA())->getCurrentOtp($secret);
    expect($svc->confirm($user, $validCode))->toBeTrue();
    expect($user->fresh()->two_factor_enabled)->toBeTrue();
});

it('disable wipes the secret, recovery codes and the enabled flag', function () {
    $svc = new TwoFactorService();
    $user = tfaUserWithSecret($svc->generateSecret());

    $svc->disable($user);

    $fresh = $user->fresh();
    expect($fresh->two_factor_enabled)->toBeFalse();
    expect($fresh->two_factor_secret)->toBeNull();
    expect($fresh->two_factor_recovery_codes)->toBeNull();
});
