<?php

use App\Models\User;
use App\Models\Device;
use App\Enums\KycStatus;
use App\Services\KycService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/**
 * Withdraw / cash-out gating for an unverified user.
 *
 * AUDIT FINDING (drives the assertions below — verified against source, not the spec):
 *   - config/kyc.php L0 (unverified): can_transfer = TRUE, can_withdraw = FALSE
 *     (config/kyc.php:47-48).
 *   - P2P transfer for an unverified user is therefore NOT blocked by permission — it is
 *     only LIMIT-gated (TransferService::assertWithinKycLimits, :270), and that is already
 *     covered by tests/Feature/Kyc/KycVerificationTest.php. Not re-tested here.
 *   - The withdraw money-path guards that DO fire today are: PIN second factor
 *     (WalletController::withdraw :198 => 422), balance (:206 => 422), ownership (:190 => 404),
 *     and the EnsureDeviceCanTransact device-hold middleware (api.php:130-131).
 *
 * These tests pin the REAL enforced behavior of the withdraw path. The unenforced
 * `can_withdraw` KYC permission is documented as a skipped reproduction at the bottom and
 * routed to @hazem (backend) — QA does not assert a gate the production code does not
 * implement.
 */

function unverifiedUser(array $attrs = []): User
{
    return User::factory()->create(array_merge([
        'kyc_level' => 0,
        'kyc_status' => KycStatus::PENDING,
        'email_verified_at' => null,
        'phone_verified_at' => null,
    ], $attrs));
}

// ──────────────── Sanity: the permission model says unverified cannot withdraw ────────────────

it('confirms the unverified KYC level denies the can_withdraw permission', function () {
    $user = unverifiedUser();
    $perms = app(KycService::class)->permissionsForUser($user);

    expect($perms['can_withdraw'] ?? null)->toBeFalse();
    expect($perms['can_transfer'] ?? null)->toBeTrue(); // transfer is limit-gated, not blocked
});

// ──────────────── Real withdraw-path guards (these are enforced) ────────────────

it('blocks a withdraw with an incorrect PIN before any balance moves', function () {
    $user = unverifiedUser();
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    $wallet->update(['balance' => 500, 'available_balance' => 500]);
    Sanctum::actingAs($user);

    $res = $this->postJson("/api/v1/wallets/{$wallet->id}/withdraw", [
        'amount' => 100,
        'pin' => '000000',
    ]);

    // Either an invalid-PIN 422 or a validation 422 — never a 2xx success.
    expect($res->status())->toBeGreaterThanOrEqual(400);
    expect((float) $wallet->fresh()->balance)->toBe(500.0);
});

it('blocks a withdraw that exceeds the wallet balance', function () {
    $user = unverifiedUser();
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    $wallet->update(['balance' => 10, 'available_balance' => 10]);
    Sanctum::actingAs($user);

    $res = $this->postJson("/api/v1/wallets/{$wallet->id}/withdraw", [
        'amount' => 1000,
        'pin' => '123456',
    ]);

    expect($res->status())->toBeGreaterThanOrEqual(400);
    expect((float) $wallet->fresh()->balance)->toBe(10.0);
});

it('blocks a withdraw from a device that is still in its post-approval security hold', function () {
    $user = unverifiedUser();
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    $wallet->update(['balance' => 500, 'available_balance' => 500]);

    // A trusted-but-transaction-locked device => EnsureDeviceCanTransact denies (403).
    $user->devices()->create([
        'device_id' => 'dev-locked-1',
        'device_name' => 'Test Phone',
            'device_type' => 'ios',
            'public_key' => 'test-public-key',
        'status' => Device::STATUS_APPROVED,
        'is_trusted' => true,
        'transactions_locked_until' => now()->addHours(24),
    ]);

    Sanctum::actingAs($user);

    $res = $this->withHeader('X-Device-Id', 'dev-locked-1')
        ->postJson("/api/v1/wallets/{$wallet->id}/withdraw", [
            'amount' => 100,
            'pin' => '123456',
        ]);

    $res->assertStatus(403)->assertJsonPath('code', 'device_locked');
    expect((float) $wallet->fresh()->balance)->toBe(500.0);
});

it('rejects an unauthenticated withdraw attempt', function () {
    $user = unverifiedUser();
    $wallet = $user->wallets()->where('currency', 'USD')->first();

    $this->postJson("/api/v1/wallets/{$wallet->id}/withdraw", [
        'amount' => 100,
        'pin' => '123456',
    ])->assertStatus(401);
});

// ──────────────── KYC withdraw gate (now ENFORCED — gap closed) ────────────────

it('blocks an unverified user from withdrawing on the can_withdraw permission', function () {
    // GAP CLOSED: WalletController::withdraw + CCPaymentController::withdraw now read
    // permissionsForUser()['can_withdraw'] (fail-closed) and return 403 'kyc_required'.
    $user = unverifiedUser();
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    $wallet->update(['balance' => 500, 'available_balance' => 500]);
    Sanctum::actingAs($user);

    // Valid PIN format, sufficient balance, no device hold — KYC is the ONLY blocker.
    $res = $this->postJson("/api/v1/wallets/{$wallet->id}/withdraw", [
        'amount' => 100,
        'pin' => '123456',
    ]);

    $res->assertStatus(403)->assertJsonPath('code', 'kyc_required');
    expect((float) $wallet->fresh()->balance)->toBe(500.0); // never debited
});

// ──────────────── Savings withdraw gate (Maram blocker fix) ────────────────

it('blocks an unverified user from withdrawing savings into the spendable wallet', function () {
    // SavingsController::withdraw moves saved funds into the spendable USD wallet,
    // so it now honors the same can_withdraw gate (was an ungated path).
    $user = unverifiedUser();
    Sanctum::actingAs($user);
    $goal = \App\Models\SavingsGoal::create([
        'user_id' => $user->id,
        'name' => 'هدف',
        'saved_amount' => 400,
        'currency' => 'USD',
        'status' => 'active',
    ]);

    $res = $this->postJson("/api/v1/savings/{$goal->id}/withdraw", [
        'amount' => 150,
        'pin' => '123456', // valid PIN; KYC gate fires first
    ]);

    $res->assertStatus(403)->assertJsonPath('code', 'kyc_required');
    expect((float) $goal->fresh()->saved_amount)->toBe(400.0); // never moved
});
