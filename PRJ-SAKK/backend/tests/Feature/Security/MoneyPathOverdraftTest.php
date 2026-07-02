<?php

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Enums\KycStatus;
use App\Services\TransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Money-path overdraft / balance-integrity — real TransferService + Wallet models.
 *
 * The wallet is the ledger of record. A debit beyond available_balance must be refused
 * at the model layer (Wallet::debit, :101-119 returns false; never mutates) AND the
 * service layer (TransferService::transfer throws 'رصيد غير كافٍ', :136-145) inside the
 * lockForUpdate DB::transaction (:107) that serializes concurrent spenders. These tests
 * exercise the exact overdraft branch that lock protects, with no HTTP/mocking in the way.
 */

function verifiedSender(float $usd): User
{
    // kyc_level 2 => can_transfer true + large single/daily limits (config/kyc.php),
    // so the ONLY thing that can stop the transfer here is the balance check.
    $u = User::factory()->create([
        'kyc_level' => 2,
        'kyc_status' => KycStatus::VERIFIED,
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
    ]);
    $u->wallets()->where('currency', 'USD')->update([
        'balance' => $usd,
        'available_balance' => $usd,
    ]);

    return $u;
}

// ──────────────── Model layer: Wallet::debit fails closed ────────────────

it('refuses a debit beyond available balance and leaves the balance untouched', function () {
    $wallet = Wallet::factory()->create([
        'currency' => 'USD',
        'balance' => 100,
        'available_balance' => 100,
        'pending_balance' => 0,
        'is_frozen' => false,
    ]);

    expect($wallet->debit(150))->toBeFalse();

    $fresh = $wallet->fresh();
    expect((float) $fresh->balance)->toBe(100.0);
    expect((float) $fresh->available_balance)->toBe(100.0);
    expect((float) $fresh->balance)->toBeGreaterThanOrEqual(0.0);
});

it('refuses a debit when the wallet is frozen', function () {
    $wallet = Wallet::factory()->create([
        'currency' => 'USD',
        'balance' => 500,
        'available_balance' => 500,
        'is_frozen' => true,
    ]);

    expect($wallet->debit(10))->toBeFalse();
    expect((float) $wallet->fresh()->balance)->toBe(500.0);
});

it('refuses a non-positive debit', function () {
    $wallet = Wallet::factory()->create(['balance' => 100, 'available_balance' => 100]);

    expect($wallet->debit(0))->toBeFalse();
    expect($wallet->debit(-50))->toBeFalse();
    expect((float) $wallet->fresh()->balance)->toBe(100.0);
});

// ──────────────── Service layer: TransferService rejects overdraft ────────────────

it('rejects a transfer that exceeds the sender balance', function () {
    $sender = verifiedSender(100);
    $recipient = User::factory()->create();

    expect(fn () => app(TransferService::class)->transfer($sender, $recipient, 150, 'USD'))
        ->toThrow(RuntimeException::class, 'رصيد غير كافٍ');

    // Sender balance unchanged; recipient never credited.
    expect((float) $sender->wallets()->where('currency', 'USD')->first()->balance)->toBe(100.0);
    expect(Transaction::where('user_id', $recipient->id)->count())->toBe(0);
});

it('keeps balance integrity exact after a valid transfer (sum is conserved minus cashback math)', function () {
    $sender = verifiedSender(100);
    $recipient = User::factory()->create();

    app(TransferService::class)->transfer($sender, $recipient, 40, 'USD');

    $senderWallet = $sender->wallets()->where('currency', 'USD')->first();
    $recipientWallet = $recipient->wallets()->where('currency', 'USD')->first();

    // Sender: 100 - 40 + 1% cashback(0.40) = 60.40. Recipient: 0 + 40 = 40.
    expect((float) $senderWallet->balance)->toBe(60.40);
    expect((float) $recipientWallet->balance)->toBe(40.0);
    expect((float) $senderWallet->balance)->toBeGreaterThanOrEqual(0.0);
    expect((float) $recipientWallet->balance)->toBeGreaterThanOrEqual(0.0);
});

it('cannot be drained below zero by back-to-back transfers (second overdraft is refused)', function () {
    // Simulates the sequential drain the lockForUpdate serializes: the first transfer
    // succeeds, the second sees the reduced balance and is rejected — wallet never < 0.
    $sender = verifiedSender(50);
    $recipient = User::factory()->create();
    $svc = app(TransferService::class);

    $svc->transfer($sender, $recipient, 50, 'USD'); // drains spendable to ~0 (cashback 0.50 remains)

    expect(fn () => $svc->transfer($sender, $recipient, 50, 'USD'))
        ->toThrow(RuntimeException::class);

    $balance = (float) $sender->wallets()->where('currency', 'USD')->first()->balance;
    expect($balance)->toBeGreaterThanOrEqual(0.0);
    expect($balance)->toBeLessThan(50.0);
});

it('rejects a self-transfer before touching any balance', function () {
    $sender = verifiedSender(100);

    expect(fn () => app(TransferService::class)->transfer($sender, $sender, 10, 'USD'))
        ->toThrow(RuntimeException::class, 'لا يمكنك التحويل إلى نفسك');

    expect((float) $sender->wallets()->where('currency', 'USD')->first()->balance)->toBe(100.0);
});
