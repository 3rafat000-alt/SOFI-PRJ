<?php

use App\Enums\TransactionCategory;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\CCPaymentService;

/**
 * Regression: a real CCPayment DirectDeposit webhook arrives nested under "msg"
 * with a Capitalised status — the old handler read the fields flat with a
 * lowercase status match, so it logged "missing required fields", returned, and
 * never credited the user (Malek's stuck deposit).
 */

function makePendingCryptoDeposit(float $startBalance = 0): array
{
    $user = User::factory()->create();
    $wallet = Wallet::factory()->create([
        'user_id' => $user->id,
        'currency' => 'SYP',
        'balance' => $startBalance,
        'available_balance' => $startBalance,
        'is_frozen' => false,
    ]);

    $tx = Transaction::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'type' => TransactionType::DEPOSIT,
        'category' => TransactionCategory::CRYPTO,
        'status' => TransactionStatus::PENDING,
        'amount' => 0,
        'currency' => 'USDT',
        'reference' => 'sarva_' . $user->id . '_X7uY56AE',
    ]);

    return [$wallet->fresh(), $tx];
}

it('credits the wallet from the real nested DirectDeposit "Success" payload', function () {
    [$wallet, $tx] = makePendingCryptoDeposit();

    // Exactly the shape CCPayment posts — nested, Capitalised status. Amount is
    // supplied here as the reconcile/list path supplies it (the bare webhook has
    // no amount and is covered separately).
    app(CCPaymentService::class)->handleDepositWebhook([
        'type' => 'DirectDeposit',
        'msg' => [
            'recordId' => '20260625182350421925879541993472',
            'referenceId' => $tx->reference,
            'coinId' => 1280,
            'coinSymbol' => 'USDT',
            'status' => 'Success',
            'amount' => '50',
            'isFlaggedAsRisky' => false,
        ],
    ]);

    expect($tx->fresh()->status)->toBe(TransactionStatus::COMPLETED)
        ->and((float) $wallet->fresh()->balance)->toBe(50.0)
        ->and((float) $wallet->fresh()->available_balance)->toBe(50.0);
});

it('keeps the deposit pending while CCPayment is still "Processing"', function () {
    [$wallet, $tx] = makePendingCryptoDeposit();

    app(CCPaymentService::class)->handleDepositWebhook([
        'type' => 'DirectDeposit',
        'msg' => [
            'recordId' => 'rec-proc',
            'referenceId' => $tx->reference,
            'status' => 'Processing',
            'amount' => '50',
        ],
    ]);

    expect($tx->fresh()->status)->toBe(TransactionStatus::PENDING)
        ->and((float) $wallet->fresh()->balance)->toBe(0.0);
});

it('refuses to mark complete when a successful deposit has no resolvable amount', function () {
    [$wallet, $tx] = makePendingCryptoDeposit();

    // Bare DirectDeposit (no amount) + no active gateway to look it up => the
    // handler must throw rather than silently credit 0 and lose the deposit.
    expect(fn () => app(CCPaymentService::class)->handleDepositWebhook([
        'type' => 'DirectDeposit',
        'msg' => [
            'recordId' => 'rec-noamt',
            'referenceId' => $tx->reference,
            'status' => 'Success',
        ],
    ]))->toThrow(RuntimeException::class);

    // Transaction stays pending (rolled back), nothing credited.
    expect($tx->fresh()->status)->toBe(TransactionStatus::PENDING)
        ->and((float) $wallet->fresh()->balance)->toBe(0.0);
});

it('still accepts the legacy flat lowercase payload shape', function () {
    [$wallet, $tx] = makePendingCryptoDeposit();

    app(CCPaymentService::class)->handleDepositWebhook([
        'recordId' => 'rec-flat',
        'referenceId' => $tx->reference,
        'status' => 'success',
        'amount' => '25',
    ]);

    expect($tx->fresh()->status)->toBe(TransactionStatus::COMPLETED)
        ->and((float) $wallet->fresh()->balance)->toBe(25.0);
});

/**
 * New model: opening the deposit screen no longer pre-creates an "amount 0 pending"
 * placeholder. The transaction is born on the webhook. Owner is resolved from the
 * deterministic reference `sarva_<userId>_<walletId>_<chain>`.
 */
it('creates the deposit row on arrival when no placeholder exists', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->create([
        'user_id' => $user->id,
        'currency' => 'SYP',
        'balance' => 0,
        'available_balance' => 0,
        'is_frozen' => false,
    ]);
    $reference = 'sarva_' . $user->id . '_' . $wallet->id . '_TRX';

    // No Transaction seeded — this mimics a fresh deposit to a reusable address.
    expect(Transaction::where('user_id', $user->id)->count())->toBe(0);

    app(CCPaymentService::class)->handleDepositWebhook([
        'type' => 'DirectDeposit',
        'msg' => [
            'recordId' => 'rec-fresh-1',
            'referenceId' => $reference,
            'status' => 'Success',
            'amount' => '40',
        ],
    ]);

    // The row is born keyed on the recordId, with the address ref in metadata.
    $rows = Transaction::where('user_id', $user->id)->get();
    expect($rows)->toHaveCount(1)
        ->and($rows->first()->reference)->toBe('rec-fresh-1')
        ->and($rows->first()->metadata['ccpayment_reference_id'])->toBe($reference)
        ->and($rows->first()->status)->toBe(TransactionStatus::COMPLETED)
        ->and((float) $rows->first()->amount)->toBe(40.0)
        ->and((float) $wallet->fresh()->balance)->toBe(40.0);
});

it('credits a second deposit to the same reusable address as its own row', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->create([
        'user_id' => $user->id,
        'currency' => 'SYP',
        'balance' => 0,
        'available_balance' => 0,
        'is_frozen' => false,
    ]);
    $reference = 'sarva_' . $user->id . '_' . $wallet->id . '_TRX';
    $cc = app(CCPaymentService::class);

    // First deposit.
    $cc->handleDepositWebhook(['type' => 'DirectDeposit', 'msg' => [
        'recordId' => 'rec-A', 'referenceId' => $reference, 'status' => 'Success', 'amount' => '40',
    ]]);
    // Second deposit to the SAME address — distinct recordId.
    $cc->handleDepositWebhook(['type' => 'DirectDeposit', 'msg' => [
        'recordId' => 'rec-B', 'referenceId' => $reference, 'status' => 'Success', 'amount' => '10',
    ]]);

    // Two distinct rows (keyed rec-A / rec-B), both credited.
    expect(Transaction::where('user_id', $user->id)->count())->toBe(2)
        ->and((float) $wallet->fresh()->balance)->toBe(50.0);
});

it('credits the full amount and stores the full-length recordId as reference without truncation', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->create([
        'user_id' => $user->id,
        'currency' => 'SYP',
        'balance' => 0,
        'available_balance' => 0,
        'is_frozen' => false,
    ]);
    $reference = 'sarva_' . $user->id . '_' . $wallet->id . '_TRX';

    // Real CCPayment recordIds run ~32-40 chars — guard against any code-side
    // truncation (e.g. a stale varchar(20) assumption) losing part of the id or
    // the amount along the way. Fresh-row path keys `reference` on recordId.
    $recordId = str_repeat('9', 40);
    expect(strlen($recordId))->toBe(40);

    app(CCPaymentService::class)->handleDepositWebhook([
        'type' => 'DirectDeposit',
        'msg' => [
            'recordId' => $recordId,
            'referenceId' => $reference,
            'status' => 'Success',
            'amount' => '123.45',
        ],
    ]);

    $fresh = Transaction::where('user_id', $user->id)->first();
    expect($fresh->status)->toBe(TransactionStatus::COMPLETED)
        ->and($fresh->reference)->toBe($recordId)
        ->and(strlen($fresh->reference))->toBe(40)
        ->and((float) $fresh->amount)->toBe(123.45)
        ->and((float) $wallet->fresh()->balance)->toBe(123.45)
        ->and((float) $wallet->fresh()->available_balance)->toBe(123.45);
});

it('is idempotent on a repeated Success callback for the same recordId', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->create([
        'user_id' => $user->id,
        'currency' => 'SYP',
        'balance' => 0,
        'available_balance' => 0,
        'is_frozen' => false,
    ]);
    $reference = 'sarva_' . $user->id . '_' . $wallet->id . '_TRX';
    $cc = app(CCPaymentService::class);
    $payload = ['type' => 'DirectDeposit', 'msg' => [
        'recordId' => 'rec-dup', 'referenceId' => $reference, 'status' => 'Success', 'amount' => '40',
    ]];

    $cc->handleDepositWebhook($payload);
    $cc->handleDepositWebhook($payload); // duplicate delivery

    expect(Transaction::where('user_id', $user->id)->count())->toBe(1)
        ->and((float) $wallet->fresh()->balance)->toBe(40.0);
});
