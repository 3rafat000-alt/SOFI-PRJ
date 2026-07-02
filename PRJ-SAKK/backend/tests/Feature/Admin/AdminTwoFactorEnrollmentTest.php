<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

/**
 * Web admin 2FA self-enrollment (admin.profile.2fa.*). Mirrors the mobile
 * API flow (AuthController@twoFactorSetup/Confirm/Disable) but gated behind
 * ['auth','admin'] and requires the admin's current password as re-auth
 * before enabling/disabling/regenerating recovery codes.
 */

const TFA_PASSWORD = 'correct-password-123';

function tfaAdmin(): User
{
    $admin = User::factory()->create(['password' => TFA_PASSWORD]);
    $admin->forceFill(['is_admin' => true, 'is_active' => true])->save();

    return $admin;
}

it('blocks a non-admin from the enrollment page', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('admin.profile.2fa.show'))
        ->assertRedirect();
});

it('lets an admin enroll: enable (password) -> confirm (TOTP) sets two_factor_enabled', function () {
    $admin = tfaAdmin();

    $this->actingAs($admin)
        ->post(route('admin.profile.2fa.enable'), ['password' => TFA_PASSWORD])
        ->assertRedirect(route('admin.profile.2fa.show'));

    $secret = session('2fa.setup.secret');
    expect($secret)->not->toBeNull();

    $code = (new Google2FA())->getCurrentOtp($secret);

    $this->post(route('admin.profile.2fa.confirm'), ['code' => $code])
        ->assertRedirect(route('admin.profile.2fa.show'));

    expect($admin->fresh()->two_factor_enabled)->toBeTrue();
});

it('rejects enable when the password is wrong', function () {
    $admin = tfaAdmin();

    $this->actingAs($admin)
        ->post(route('admin.profile.2fa.enable'), ['password' => 'totally-wrong'])
        ->assertSessionHasErrors('password');

    expect($admin->fresh()->two_factor_enabled)->toBeFalse();
    expect(session('2fa.setup.secret'))->toBeNull();
});

it('rejects confirm with a wrong TOTP code', function () {
    $admin = tfaAdmin();

    $this->actingAs($admin)
        ->post(route('admin.profile.2fa.enable'), ['password' => TFA_PASSWORD]);

    $this->post(route('admin.profile.2fa.confirm'), ['code' => '000000'])
        ->assertSessionHasErrors('code');

    expect($admin->fresh()->two_factor_enabled)->toBeFalse();
});

it('disable requires both password and a valid 2FA code', function () {
    $secret = (new Google2FA())->generateSecretKey(32);
    $admin = tfaAdmin();
    $admin->forceFill([
        'two_factor_secret' => $secret,
        'two_factor_enabled' => true,
        'two_factor_recovery_codes' => ['AAAA-BBBB-CCCC'],
    ])->save();

    $code = (new Google2FA())->getCurrentOtp($secret);

    // Wrong password, valid code -> rejected, still enabled.
    $this->actingAs($admin)
        ->post(route('admin.profile.2fa.disable'), ['password' => 'wrong', 'code' => $code])
        ->assertSessionHasErrors('password');
    expect($admin->fresh()->two_factor_enabled)->toBeTrue();

    // Correct password, wrong code -> rejected, still enabled.
    $this->post(route('admin.profile.2fa.disable'), ['password' => TFA_PASSWORD, 'code' => '000000'])
        ->assertSessionHasErrors('code');
    expect($admin->fresh()->two_factor_enabled)->toBeTrue();

    // Both correct -> disabled.
    $this->post(route('admin.profile.2fa.disable'), ['password' => TFA_PASSWORD, 'code' => $code])
        ->assertRedirect(route('admin.profile.2fa.show'));
    expect($admin->fresh()->two_factor_enabled)->toBeFalse();
});
