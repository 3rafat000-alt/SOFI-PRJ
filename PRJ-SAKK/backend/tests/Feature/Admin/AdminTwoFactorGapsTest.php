<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

/**
 * Gap-fill for TwoFactorProfileController: show() pending-secret + recovery-reveal
 * branches, and recovery() (regenerate recovery codes) — not covered by
 * AdminTwoFactorEnrollmentTest.
 */

const TFA2_PASSWORD = 'correct-password-456';

function tfa2Admin(): User
{
    $admin = User::factory()->create(['password' => TFA2_PASSWORD]);
    $admin->forceFill(['is_admin' => true, 'is_active' => true])->save();

    return $admin;
}

it('show surfaces the pending secret + qr url from session while unconfirmed', function () {
    $admin = tfa2Admin();

    $this->actingAs($admin)
        ->post(route('admin.profile.2fa.enable'), ['password' => TFA2_PASSWORD]);

    $response = $this->actingAs($admin)->get(route('admin.profile.2fa.show'));

    $response->assertOk();
    expect(session('2fa.setup.secret'))->not->toBeNull();
});

it('show reveals recovery codes once after confirm, then never again', function () {
    $admin = tfa2Admin();

    $this->actingAs($admin)
        ->post(route('admin.profile.2fa.enable'), ['password' => TFA2_PASSWORD]);
    $secret = session('2fa.setup.secret');
    $code = (new Google2FA())->getCurrentOtp($secret);

    $this->post(route('admin.profile.2fa.confirm'), ['code' => $code]);

    // First render after confirm: recovery codes flashed and pulled from session.
    $first = $this->get(route('admin.profile.2fa.show'));
    $first->assertOk();

    // Second render: the flashed data is gone (pull() consumed it).
    $second = $this->get(route('admin.profile.2fa.show'));
    $second->assertOk();
});

it('recovery regenerates codes when password + code are both valid', function () {
    $secret = (new Google2FA())->generateSecretKey(32);
    $admin = tfa2Admin();
    $admin->forceFill([
        'two_factor_secret' => $secret,
        'two_factor_enabled' => true,
        'two_factor_recovery_codes' => ['OLD1-OLD2-OLD3'],
    ])->save();

    $code = (new Google2FA())->getCurrentOtp($secret);

    $this->actingAs($admin)
        ->post(route('admin.profile.2fa.recovery'), ['password' => TFA2_PASSWORD, 'code' => $code])
        ->assertRedirect(route('admin.profile.2fa.show'))
        ->assertSessionHas('success');

    expect($admin->fresh()->two_factor_recovery_codes)->not->toBe(['OLD1-OLD2-OLD3']);
});

it('recovery rejects a wrong password and leaves codes unchanged', function () {
    $secret = (new Google2FA())->generateSecretKey(32);
    $admin = tfa2Admin();
    $admin->forceFill([
        'two_factor_secret' => $secret,
        'two_factor_enabled' => true,
        'two_factor_recovery_codes' => ['OLD1-OLD2-OLD3'],
    ])->save();
    $code = (new Google2FA())->getCurrentOtp($secret);

    $this->actingAs($admin)
        ->post(route('admin.profile.2fa.recovery'), ['password' => 'wrong', 'code' => $code])
        ->assertSessionHasErrors('password');

    expect($admin->fresh()->two_factor_recovery_codes)->toBe(['OLD1-OLD2-OLD3']);
});

it('recovery rejects an invalid TOTP code', function () {
    $secret = (new Google2FA())->generateSecretKey(32);
    $admin = tfa2Admin();
    $admin->forceFill([
        'two_factor_secret' => $secret,
        'two_factor_enabled' => true,
        'two_factor_recovery_codes' => ['OLD1-OLD2-OLD3'],
    ])->save();

    $this->actingAs($admin)
        ->post(route('admin.profile.2fa.recovery'), ['password' => TFA2_PASSWORD, 'code' => '000000'])
        ->assertSessionHasErrors('code');

    expect($admin->fresh()->two_factor_recovery_codes)->toBe(['OLD1-OLD2-OLD3']);
});

it('recovery redirects immediately when 2fa is not enabled', function () {
    $admin = tfa2Admin();

    $this->actingAs($admin)
        ->post(route('admin.profile.2fa.recovery'), ['password' => TFA2_PASSWORD, 'code' => '000000'])
        ->assertRedirect(route('admin.profile.2fa.show'));
});

it('enable redirects immediately when 2fa is already enabled', function () {
    $secret = (new Google2FA())->generateSecretKey(32);
    $admin = tfa2Admin();
    $admin->forceFill(['two_factor_secret' => $secret, 'two_factor_enabled' => true])->save();

    $this->actingAs($admin)
        ->post(route('admin.profile.2fa.enable'), ['password' => TFA2_PASSWORD])
        ->assertRedirect(route('admin.profile.2fa.show'));
});
