<?php

use App\Models\User;
use App\Models\Wallet;
use App\Enums\KycStatus;
use App\Services\KycService;
use App\Services\WalletService;

/**
 * Velocity-cap coverage for the withdraw path (close the KYC velocity leak):
 * WalletService::withdraw now enforces the SAME identity-based KYC caps that
 * TransferService already enforced — single, and cumulative daily/monthly across
 * BOTH outbound channels (transfer_out + withdrawal) — under the wallet lock.
 *
 * Caps are injected via a bound KycService double so the amounts are deterministic
 * regardless of config/kyc.php; the WalletService debit + the cumulative sum query
 * run for real.
 */
function velUser(float $syp): User
{
    $user = User::factory()->create([
        'kyc_status' => KycStatus::VERIFIED,
        'pin_code' => Hash::make('123456'),
    ]);

    $wallet = $user->wallets()->where('currency', 'SYP')->first()
        ?? Wallet::create(['user_id' => $user->id, 'currency' => 'SYP']);
    $wallet->update(['balance' => $syp, 'available_balance' => $syp, 'pending_balance' => 0]);

    return $user;
}

function bindCaps(float $single, float $daily, float $monthly, bool $canWithdraw = true): void
{
    $mock = Mockery::mock(KycService::class)->makePartial();
    $mock->shouldReceive('permissionsForUser')
        ->andReturn(['can_withdraw' => $canWithdraw, 'can_transfer' => true]);
    $mock->shouldReceive('limitsForUser')
        ->andReturn(['SYP' => ['single' => $single, 'daily' => $daily, 'monthly' => $monthly]]);
    app()->instance(KycService::class, $mock);
}

it('allows a withdrawal within the KYC caps and debits the wallet', function () {
    $user = velUser(2_000_000);
    bindCaps(single: 500_000, daily: 1_000_000, monthly: 10_000_000);
    $wallet = $user->wallets()->where('currency', 'SYP')->first();

    app(WalletService::class)->withdraw($wallet, 400_000);

    expect((float) $wallet->fresh()->balance)->toBe(1_600_000.0);
});

it('blocks a withdrawal over the single-transaction limit without debiting', function () {
    $user = velUser(2_000_000);
    bindCaps(single: 500_000, daily: 1_000_000, monthly: 10_000_000);
    $wallet = $user->wallets()->where('currency', 'SYP')->first();

    expect(fn () => app(WalletService::class)->withdraw($wallet, 600_000))
        ->toThrow(\RuntimeException::class);

    // guard throws before debit → the DB::transaction rolls back → balance intact
    expect((float) $wallet->fresh()->balance)->toBe(2_000_000.0);
});

it('blocks a second withdrawal that breaches the cumulative daily cap', function () {
    $user = velUser(2_000_000);
    bindCaps(single: 1_000_000, daily: 1_000_000, monthly: 10_000_000);
    $wallet = $user->wallets()->where('currency', 'SYP')->first();

    // first withdrawal succeeds (700k ≤ 1M daily) and records a WITHDRAWAL row
    app(WalletService::class)->withdraw($wallet, 700_000);
    expect((float) $wallet->fresh()->balance)->toBe(1_300_000.0);

    // second (700k already spent + 400k = 1.1M > 1M) must be blocked, no debit
    expect(fn () => app(WalletService::class)->withdraw($wallet->fresh(), 400_000))
        ->toThrow(\RuntimeException::class);
    expect((float) $wallet->fresh()->balance)->toBe(1_300_000.0);
});

it('blocks a withdrawal when the KYC level lacks can_withdraw', function () {
    $user = velUser(2_000_000);
    bindCaps(single: 500_000, daily: 1_000_000, monthly: 10_000_000, canWithdraw: false);
    $wallet = $user->wallets()->where('currency', 'SYP')->first();

    expect(fn () => app(WalletService::class)->withdraw($wallet, 100_000))
        ->toThrow(\RuntimeException::class);
    expect((float) $wallet->fresh()->balance)->toBe(2_000_000.0);
});
