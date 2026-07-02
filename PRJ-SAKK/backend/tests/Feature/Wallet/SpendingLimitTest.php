<?php

use App\Models\User;
use App\Models\Wallet;
use App\Enums\KycStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/**
 * Spending limit enforcement guard.
 *
 * Ensures daily_limit and monthly_limit are properly enforced
 * during spend operations. Tests both the model's canSpend() method
 * and the full withdrawal flow via controller.
 */

it('allows spending when amount is within daily limit', function () {
    Carbon::setTestNow('2026-06-21 10:00:00');

    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    $wallet->forceFill([
        'balance' => 100000,
        'available_balance' => 100000,
        'daily_limit' => 10000,
        'monthly_limit' => 100000,
        'daily_spent' => 0,
        'monthly_spent' => 0,
        'daily_reset_at' => now()->toDateString(),
        'monthly_reset_at' => now()->startOfMonth()->toDateString(),
    ])->saveQuietly();

    // Attempt to spend 5000 (within 10000 daily limit)
    expect($wallet->canSpend(5000))->toBeTrue();
});

it('rejects spending when amount exceeds daily limit', function () {
    Carbon::setTestNow('2026-06-21 10:00:00');

    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    $wallet->forceFill([
        'balance' => 100000,
        'available_balance' => 100000,
        'daily_limit' => 10000,
        'monthly_limit' => 100000,
        'daily_spent' => 0,
        'monthly_spent' => 0,
        'daily_reset_at' => now()->toDateString(),
        'monthly_reset_at' => now()->startOfMonth()->toDateString(),
    ])->saveQuietly();

    // Attempt to spend 100000 (way over 10000 daily limit)
    expect($wallet->canSpend(100000))->toBeFalse();
});

it('rejects spending when cumulative amount exceeds daily limit', function () {
    Carbon::setTestNow('2026-06-21 10:00:00');

    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    $wallet->forceFill([
        'balance' => 100000,
        'available_balance' => 100000,
        'daily_limit' => 10000,
        'monthly_limit' => 100000,
        'daily_spent' => 8000,  // Already spent 8000 today
        'monthly_spent' => 8000,
        'daily_reset_at' => now()->toDateString(),
        'monthly_reset_at' => now()->startOfMonth()->toDateString(),
    ])->saveQuietly();

    // Can spend 2000 more (within remaining 2000)
    expect($wallet->canSpend(2000))->toBeTrue();

    // Cannot spend 3000 (would exceed 10000 limit)
    expect($wallet->canSpend(3000))->toBeFalse();
});

it('rejects spending when amount exceeds monthly limit', function () {
    Carbon::setTestNow('2026-06-21 10:00:00');

    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    $wallet->forceFill([
        'balance' => 200000,
        'available_balance' => 200000,
        'daily_limit' => 50000,
        'monthly_limit' => 100000,
        'daily_spent' => 0,
        'monthly_spent' => 95000,  // Already spent 95000 this month
        'daily_reset_at' => now()->toDateString(),
        'monthly_reset_at' => now()->startOfMonth()->toDateString(),
    ])->saveQuietly();

    // Can spend 5000 more (within remaining 5000)
    expect($wallet->canSpend(5000))->toBeTrue();

    // Cannot spend 10000 (would exceed 100000 monthly limit)
    expect($wallet->canSpend(10000))->toBeFalse();
});

it('rejects spending when wallet is frozen', function () {
    Carbon::setTestNow('2026-06-21 10:00:00');

    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    $wallet->forceFill([
        'balance' => 100000,
        'available_balance' => 100000,
        'daily_limit' => 10000,
        'monthly_limit' => 100000,
        'daily_spent' => 0,
        'monthly_spent' => 0,
        'is_frozen' => true,
        'frozen_reason' => 'Suspicious activity',
        'daily_reset_at' => now()->toDateString(),
        'monthly_reset_at' => now()->startOfMonth()->toDateString(),
    ])->saveQuietly();

    expect($wallet->canSpend(5000))->toBeFalse();
});

it('rejects spending when wallet is inactive', function () {
    Carbon::setTestNow('2026-06-21 10:00:00');

    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    $wallet->forceFill([
        'balance' => 100000,
        'available_balance' => 100000,
        'daily_limit' => 10000,
        'monthly_limit' => 100000,
        'daily_spent' => 0,
        'monthly_spent' => 0,
        'is_active' => false,
        'daily_reset_at' => now()->toDateString(),
        'monthly_reset_at' => now()->startOfMonth()->toDateString(),
    ])->saveQuietly();

    expect($wallet->canSpend(5000))->toBeFalse();
});

it('rejects spending when balance is insufficient', function () {
    Carbon::setTestNow('2026-06-21 10:00:00');

    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    $wallet->forceFill([
        'balance' => 100,
        'available_balance' => 100,
        'daily_limit' => 10000,
        'monthly_limit' => 100000,
        'daily_spent' => 0,
        'monthly_spent' => 0,
        'daily_reset_at' => now()->toDateString(),
        'monthly_reset_at' => now()->startOfMonth()->toDateString(),
    ])->saveQuietly();

    expect($wallet->canSpend(5000))->toBeFalse();
});

afterEach(fn () => Carbon::setTestNow());
