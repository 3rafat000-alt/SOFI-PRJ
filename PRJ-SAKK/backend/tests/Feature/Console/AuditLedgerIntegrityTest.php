<?php

use App\Enums\TransactionCategory;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\AdminAlert;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Support\LedgerHaltGuard;
use Illuminate\Support\Facades\Cache;

// Seeds a deliberate ledger drift: wallet.balance disagrees with the sum of
// its COMPLETED transactions by more than the FinancialVerificationAgent's
// auto-repair threshold, so the agent classifies it CRITICAL (escalated,
// not auto-settled) and AuditLedgerIntegrity::handle() reacts to it.
function seedCriticalDrift(): Wallet
{
    config(['agents.auto_repair_threshold' => 100]); // SYP-denominated in this codebase's default, but comparison is currency-agnostic in the drift check

    $user = User::factory()->create();
    $wallet = $user->wallets()->where('currency', 'USD')->first();

    // Ledger says +50 was credited (one completed transaction)...
    Transaction::create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'type' => TransactionType::DEPOSIT,
        'category' => TransactionCategory::WALLET,
        'currency' => 'USD',
        'amount' => 50,
        'fee' => 0,
        'net_amount' => 50,
        'balance_before' => 0,
        'balance_after' => 50,
        'status' => TransactionStatus::COMPLETED,
        'title' => 'Deposit',
        'completed_at' => now(),
    ]);

    // ...but the stored wallet balance was corrupted to 5000 directly
    // (simulating a mid-batch crash that wrote a debit/credit without its
    // matching ledger row) — drift = 4950, far above the 100 threshold.
    $wallet->update(['balance' => 5000, 'available_balance' => 5000]);

    return $wallet->fresh();
}

beforeEach(function () {
    Cache::flush();
    LedgerHaltGuard::release();
});

it('detects a critical drift, logs it, and notifies admins without halting in a non-production environment', function () {
    expect(app()->environment())->not->toBe('production');

    seedCriticalDrift();

    $this->artisan('audit:ledger')->assertSuccessful();

    // Admin alert fired via AdminNotificationService::systemError.
    expect(AdminAlert::where('type', 'error')->count())->toBeGreaterThan(0);

    // Alert-only: the halt flag must NOT be engaged in staging/testing.
    expect(LedgerHaltGuard::isHalted())->toBeFalse();
});

it('engages the disbursement halt in production on a critical drift', function () {
    // Simulate production for this test only — restored in `finally` so a
    // failed assertion above can never leak the env into later tests.
    app()['env'] = 'production';

    try {
        seedCriticalDrift();

        $this->artisan('audit:ledger')->assertFailed();

        expect(LedgerHaltGuard::isHalted())->toBeTrue();
        expect(AdminAlert::where('type', 'error')->count())->toBeGreaterThan(0);
    } finally {
        app()['env'] = 'testing';
        LedgerHaltGuard::release();
    }
});

it('does not halt or alert when the ledger is clean', function () {
    $user = User::factory()->create();
    $wallet = $user->wallets()->where('currency', 'USD')->first();

    Transaction::create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'type' => TransactionType::DEPOSIT,
        'category' => TransactionCategory::WALLET,
        'currency' => 'USD',
        'amount' => 20,
        'fee' => 0,
        'net_amount' => 20,
        'balance_before' => 0,
        'balance_after' => 20,
        'status' => TransactionStatus::COMPLETED,
        'title' => 'Deposit',
        'completed_at' => now(),
    ]);
    $wallet->update(['balance' => 20, 'available_balance' => 20]);

    $this->artisan('audit:ledger')->assertSuccessful();

    expect(LedgerHaltGuard::isHalted())->toBeFalse();
    expect(AdminAlert::where('type', 'error')->count())->toBe(0);
});

it('refuses a P2P transfer while the disbursement halt is engaged', function () {
    // engage() is production-gated by design (never trips outside prod) —
    // simulate production for this test only, and always restore the
    // environment afterwards so state can't leak into later tests.
    app()['env'] = 'production';

    try {
        LedgerHaltGuard::engage('test halt');

        $sender = User::factory()->create();
        $sender->wallets()->where('currency', 'USD')->first()->update(['balance' => 100, 'available_balance' => 100]);
        $recipient = User::factory()->create();

        expect(fn () => app(\App\Services\TransferService::class)->transfer($sender, $recipient, 10, 'USD'))
            ->toThrow(\RuntimeException::class);
    } finally {
        app()['env'] = 'testing';
        LedgerHaltGuard::release();
    }
});

it('allows a P2P transfer once the halt is released', function () {
    LedgerHaltGuard::release();

    $sender = User::factory()->create(['kyc_level' => 2]);
    $senderWallet = $sender->wallets()->where('currency', 'USD')->first();
    $senderWallet->update(['balance' => 100, 'available_balance' => 100]);
    $recipient = User::factory()->create();

    $result = app(\App\Services\TransferService::class)->transfer($sender, $recipient, 10, 'USD');

    expect($result['amount'])->toEqual(10);
});
