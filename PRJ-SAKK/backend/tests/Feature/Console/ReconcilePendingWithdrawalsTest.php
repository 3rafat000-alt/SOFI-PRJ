<?php

use App\Enums\TransactionCategory;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CCPaymentService;

// Simulates a hard process kill between Phase A (short-locked debit +
// reserve, tx PENDING / metadata.gateway_dispatched=false) and Phase B (the
// gateway HTTP call) of CCPaymentController::withdraw. The sweeper command
// must resolve the orphaned debit deterministically and be safe to re-run.

beforeEach(function () {
    $this->ccpaymentMock = Mockery::mock(CCPaymentService::class);
    $this->app->instance(CCPaymentService::class, $this->ccpaymentMock);
});

function makeStuckWithdrawal(User $user, float $amount = 10.0, int $minutesAgo = 15): Transaction
{
    $wallet = $user->wallets()->where('currency', 'USD')->first();

    // Mirror Phase A: debit already applied, tx reserved but never dispatched.
    $wallet->update([
        'balance' => $wallet->balance - $amount,
        'available_balance' => $wallet->available_balance - $amount,
    ]);

    // created_at/updated_at are not $fillable on Transaction (SEC-002 explicit
    // allowlist) — create() would silently drop the backdated timestamp, so
    // set it via a direct update after create instead.
    $tx = Transaction::create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'type' => TransactionType::WITHDRAWAL,
        'category' => TransactionCategory::CRYPTO,
        'status' => TransactionStatus::PENDING,
        'amount' => $amount,
        'title' => 'سحب كريبتو',
        'currency' => 'USDT',
        'reference' => 'sarva_wd_stuck_' . uniqid(),
        'description' => 'سحب CCPayment - TRC20',
        'metadata' => [
            'to_address' => 'TXaddressaddress123',
            'chain' => 'TRC20',
            'gateway_dispatched' => false,
        ],
    ]);

    $tx->timestamps = false;
    $tx->created_at = now()->subMinutes($minutesAgo);
    $tx->updated_at = now()->subMinutes($minutesAgo);
    $tx->save();

    return $tx->fresh();
}

it('refunds the wallet and marks FAILED when the gateway never received the order', function () {
    $user = User::factory()->create(['kyc_level' => 2]);
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    $wallet->update(['balance' => 100, 'available_balance' => 100]);

    $tx = makeStuckWithdrawal($user, 10.0);

    $this->ccpaymentMock->shouldReceive('getWithdrawRecord')
        ->once()
        ->with($tx->reference)
        ->andReturn([]); // gateway has no record — Phase B never ran

    $this->artisan('withdrawals:reconcile-pending')->assertSuccessful();

    $tx->refresh();
    $wallet->refresh();

    expect($tx->status)->toBe(TransactionStatus::FAILED);
    expect($tx->metadata['refunded'])->toBeTrue();
    expect($tx->metadata['gateway_dispatched'])->toBeFalse();
    expect((float) $wallet->balance)->toEqual(100.0); // 90 (post Phase-A debit) + 10 refund
});

it('marks dispatched and syncs status when the gateway DOES have the order', function () {
    $user = User::factory()->create(['kyc_level' => 2]);
    $tx = makeStuckWithdrawal($user, 10.0);

    $this->ccpaymentMock->shouldReceive('getWithdrawRecord')
        ->once()
        ->with($tx->reference)
        ->andReturn(['recordId' => 'ccp_rec_123', 'status' => 'Processing']);

    $this->artisan('withdrawals:reconcile-pending')->assertSuccessful();

    $tx->refresh();

    expect($tx->status)->toBe(TransactionStatus::PROCESSING);
    expect($tx->metadata['gateway_dispatched'])->toBeTrue();
    expect($tx->metadata['ccpayment_record_id'])->toBe('ccp_rec_123');
});

it('refunds when the gateway itself already failed the withdrawal', function () {
    $user = User::factory()->create(['kyc_level' => 2]);
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    $wallet->update(['balance' => 100, 'available_balance' => 100]);

    $tx = makeStuckWithdrawal($user, 10.0);

    $this->ccpaymentMock->shouldReceive('getWithdrawRecord')
        ->once()
        ->with($tx->reference)
        ->andReturn(['recordId' => 'ccp_rec_456', 'status' => 'Failed']);

    $this->artisan('withdrawals:reconcile-pending')->assertSuccessful();

    $tx->refresh();
    $wallet->refresh();

    expect($tx->status)->toBe(TransactionStatus::FAILED);
    expect($tx->metadata['refunded'])->toBeTrue();
    expect((float) $wallet->balance)->toEqual(100.0);
});

it('does not touch withdrawals younger than the age threshold', function () {
    $user = User::factory()->create(['kyc_level' => 2]);
    $tx = makeStuckWithdrawal($user, 10.0, minutesAgo: 2);

    $this->ccpaymentMock->shouldReceive('getWithdrawRecord')->never();

    $this->artisan('withdrawals:reconcile-pending')->assertSuccessful();

    $tx->refresh();
    expect($tx->status)->toBe(TransactionStatus::PENDING);
    expect($tx->metadata['gateway_dispatched'])->toBeFalse();
});

it('is idempotency-guarded against a concurrent resolution (no double refund)', function () {
    // Simulates the race the review flagged: a manual retry (or a racing
    // webhook / earlier sweep pass) resolves the row to FAILED+refunded
    // between the gateway lookup and the locked write. The command must
    // re-check status inside the lock and skip — never refund twice.
    $user = User::factory()->create(['kyc_level' => 2]);
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    $wallet->update(['balance' => 100, 'available_balance' => 100]);

    $tx = makeStuckWithdrawal($user, 10.0);

    $this->ccpaymentMock->shouldReceive('getWithdrawRecord')
        ->once()
        ->with($tx->reference)
        ->andReturnUsing(function () use ($tx, $wallet) {
            // Race window: another process already refunded + closed this
            // row out while our gateway HTTP call was in flight.
            $wallet->refresh()->credit(10.0);
            $tx->update([
                'status' => TransactionStatus::FAILED,
                'metadata' => array_merge($tx->metadata, [
                    'refunded' => true,
                    'gateway_dispatched' => false,
                ]),
            ]);

            return [];
        });

    $this->artisan('withdrawals:reconcile-pending')->assertSuccessful();

    $wallet->refresh();
    // Only the one refund from inside the mock fired — the command's own
    // in-lock re-check (status !== PENDING) must have skipped its branch.
    expect((float) $wallet->balance)->toEqual(100.0);
});
