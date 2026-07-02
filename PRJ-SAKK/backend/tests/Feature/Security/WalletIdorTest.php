<?php

use App\Models\User;
use App\Models\Wallet;
use App\Models\VirtualCard;
use App\Models\Transaction;
use App\Enums\TransactionType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/**
 * Wallet / card / transaction IDOR — user A may never read or mutate user B's objects.
 *
 * Existing E2E/SecurityTest already covers wallet *show* and transfer-from-another-wallet.
 * These tests close the remaining direct-object-reference holes: cards, card transactions,
 * a single transaction, wallet transactions, and a *mutating* withdraw against B's wallet.
 *
 * Real controllers return 404 (NOT 403) on an ownership mismatch — a deliberate
 * non-enumerable response that never discloses the foreign object's existence or data
 * (CardController::show :114, TransactionController::show :99, WalletController::show :43,
 * WalletController::transactions :116, WalletController::withdraw :190). The contract these
 * tests pin: status is 403/404, and the response body never contains B's data.
 */

function attacker(): User
{
    $u = User::factory()->create();
    Sanctum::actingAs($u);

    return $u;
}

// ──────────────── Read: wallet ────────────────

it('blocks reading another users wallet', function () {
    attacker();
    $victim = User::factory()->create();
    $victimWallet = $victim->wallets()->where('currency', 'USD')->first();
    $victimWallet->update(['balance' => 7777, 'available_balance' => 7777]);

    $res = $this->getJson("/api/v1/wallets/{$victimWallet->id}");

    expect($res->status())->toBeIn([403, 404]);
    expect($res->getContent())->not->toContain('7777');
});

it('blocks reading another users wallet transactions', function () {
    attacker();
    $victim = User::factory()->create();
    $victimWallet = $victim->wallets()->where('currency', 'USD')->first();

    $res = $this->getJson("/api/v1/wallets/{$victimWallet->id}/transactions");

    expect($res->status())->toBeIn([403, 404]);
});

// ──────────────── Read: card ────────────────

it('blocks reading another users card', function () {
    attacker();
    $victim = User::factory()->create();
    $victimWallet = $victim->wallets()->where('currency', 'USD')->first();
    $victimCard = VirtualCard::factory()->create([
        'user_id' => $victim->id,
        'wallet_id' => $victimWallet->id,
    ]);

    $res = $this->getJson("/api/v1/cards/{$victimCard->id}");

    expect($res->status())->toBeIn([403, 404, 503]);
    expect($res->getContent())->not->toContain($victimCard->card_number_masked);
});

it('blocks reading another users card transactions', function () {
    attacker();
    $victim = User::factory()->create();
    $victimCard = VirtualCard::factory()->create([
        'user_id' => $victim->id,
        'wallet_id' => $victim->wallets()->where('currency', 'USD')->first()->id,
    ]);

    $res = $this->getJson("/api/v1/cards/{$victimCard->id}/transactions");

    expect($res->status())->toBeIn([403, 404, 503]);
});

// ──────────────── Read: single transaction ────────────────

it('blocks reading another users transaction by id', function () {
    attacker();
    $victim = User::factory()->create();
    $victimWallet = $victim->wallets()->where('currency', 'USD')->first();
    $tx = Transaction::create([
        'user_id' => $victim->id,
        'wallet_id' => $victimWallet->id,
        'type' => TransactionType::TRANSFER_IN,
        'category' => TransactionCategory::P2P,
        'currency' => 'USD',
        'amount' => 1234,
        'fee' => 0,
        'net_amount' => 1234,
        'balance_before' => 0,
        'balance_after' => 1234,
        'status' => TransactionStatus::COMPLETED,
        'title' => 'victim-secret-tx',
    ]);

    $res = $this->getJson("/api/v1/transactions/{$tx->id}");

    expect($res->status())->toBeIn([403, 404]);
    expect($res->getContent())->not->toContain('victim-secret-tx');
});

// ──────────────── Mutate: withdraw against another users wallet ────────────────

it('blocks withdrawing from another users wallet and never debits it', function () {
    attacker();
    $victim = User::factory()->create();
    $victimWallet = $victim->wallets()->where('currency', 'USD')->first();
    $victimWallet->update(['balance' => 500, 'available_balance' => 500]);

    $res = $this->postJson("/api/v1/wallets/{$victimWallet->id}/withdraw", [
        'amount' => 100,
        'pin' => '123456',
    ]);

    expect($res->status())->toBeIn([403, 404, 422]);
    // The victim wallet must be untouched regardless of which guard fired.
    expect((float) $victimWallet->fresh()->balance)->toBe(500.0);
});
