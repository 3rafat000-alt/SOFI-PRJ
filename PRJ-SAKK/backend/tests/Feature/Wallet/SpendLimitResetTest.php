<?php

use App\Models\User;
use App\Models\Wallet;
use App\Enums\KycStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Regression guard for the spending-limit reset.
 *
 * Bug: daily_reset_at / monthly_reset_at carry a 'date' cast, so they read back
 * as Carbon objects. resetLimitsIfNeeded() compared them with !== against a
 * string (now()->toDateString()), which is ALWAYS true — so daily_spent /
 * monthly_spent reset to 0 on every canSpend()/checkSpendingLimits() call and
 * the declared caps were never actually enforced. These tests fail on the buggy
 * comparison and pass once it compares like-for-like (?->toDateString()).
 */
function walletForReset(): Wallet
{
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    // forceFill: daily_reset_at / monthly_reset_at are internal (non-fillable)
    // columns, so a mass-assign update() would silently drop them.
    $wallet->forceFill([
        'daily_limit'      => 1000,
        'monthly_limit'    => 10000,
        'daily_spent'      => 0,
        'monthly_spent'    => 0,
        'daily_reset_at'   => now()->toDateString(),
        'monthly_reset_at' => now()->startOfMonth()->toDateString(),
    ])->saveQuietly();

    return $wallet->fresh();
}

afterEach(fn () => Carbon::setTestNow());

it('does not reset daily_spent within the same day', function () {
    Carbon::setTestNow('2026-06-20 09:00:00');
    $wallet = walletForReset();
    $wallet->daily_spent = 300;
    $wallet->saveQuietly();

    Carbon::setTestNow('2026-06-20 21:00:00'); // same day, later
    $wallet = $wallet->fresh();
    $wallet->resetLimitsIfNeeded();

    expect((float) $wallet->fresh()->daily_spent)->toBe(300.0);
});

it('resets daily_spent on a new day', function () {
    Carbon::setTestNow('2026-06-20 09:00:00');
    $wallet = walletForReset();
    $wallet->daily_spent = 300;
    $wallet->saveQuietly();

    Carbon::setTestNow('2026-06-21 09:00:00'); // next day
    $wallet = $wallet->fresh();
    $wallet->resetLimitsIfNeeded();

    expect((float) $wallet->fresh()->daily_spent)->toBe(0.0);
});

it('does not reset monthly_spent within the same month', function () {
    Carbon::setTestNow('2026-06-10 09:00:00');
    $wallet = walletForReset();
    $wallet->monthly_spent = 5000;
    $wallet->saveQuietly();

    Carbon::setTestNow('2026-06-28 09:00:00'); // same month, later
    $wallet = $wallet->fresh();
    $wallet->resetLimitsIfNeeded();

    expect((float) $wallet->fresh()->monthly_spent)->toBe(5000.0);
});

it('resets monthly_spent on a new month', function () {
    Carbon::setTestNow('2026-06-20 09:00:00');
    $wallet = walletForReset();
    $wallet->monthly_spent = 5000;
    $wallet->saveQuietly();

    Carbon::setTestNow('2026-07-01 09:00:00'); // next month
    $wallet = $wallet->fresh();
    $wallet->resetLimitsIfNeeded();

    expect((float) $wallet->fresh()->monthly_spent)->toBe(0.0);
});
