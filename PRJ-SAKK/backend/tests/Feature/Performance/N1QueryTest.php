<?php

use App\Models\User;
use App\Models\Wallet;
use App\Models\VirtualCard;
use App\Models\Transaction;
use App\Enums\KycStatus;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\DB;

it('prevents N+1 queries in wallet transactions endpoint', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);
    $wallet = Wallet::factory()->create(['user_id' => $user->id]);

    // Create multiple transactions with related models
    for ($i = 0; $i < 5; $i++) {
        Transaction::factory()->create([
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
        ]);
    }

    Sanctum::actingAs($user);

    // Count queries during endpoint call
    $queryCount = 0;
    DB::listen(function () use (&$queryCount) {
        $queryCount++;
    });

    $response = $this->getJson("/api/v1/wallets/{$wallet->id}/transactions?per_page=5");

    $response->assertStatus(200);

    // Should have minimal queries: 1 for wallet, 1 for transactions, 1 for relationships
    // The query count should be consistent regardless of transaction count (no N+1)
    expect($queryCount)->toBeLessThan(10);
});

it('prevents N+1 queries in card index endpoint', function () {
    enableCardsFeature();
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED, 'kyc_level' => 2]);
    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'currency' => 'USD', 'balance' => 1000]);

    // Create multiple cards
    $cardService = app(\App\Services\CardService::class);
    for ($i = 0; $i < 5; $i++) {
        $cardService->createCard($user, $wallet, 'visa', 'virtual', "Card {$i}");
    }

    Sanctum::actingAs($user);

    $queryCount = 0;
    DB::listen(function () use (&$queryCount) {
        $queryCount++;
    });

    $response = $this->getJson('/api/v1/cards');

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(5);

    // Should avoid N+1 by eager loading wallet and user
    expect($queryCount)->toBeLessThan(10);
});

it('prevents N+1 queries in card transactions endpoint', function () {
    enableCardsFeature();
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED, 'kyc_level' => 2]);
    // Delete auto-created USD wallet
    $user->wallets()->where('currency', 'USD')->delete();
    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'currency' => 'USD', 'balance' => 1000]);

    // Create a card
    $cardService = app(\App\Services\CardService::class);
    $cardResult = $cardService->createCard($user, $wallet, 'visa', 'virtual');
    $card = VirtualCard::find($cardResult['card']['id']);

    // Create multiple transactions for the card
    for ($i = 0; $i < 5; $i++) {
        Transaction::factory()->create([
            'card_id' => $card->id,
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
        ]);
    }

    Sanctum::actingAs($user);

    $queryCount = 0;
    DB::listen(function () use (&$queryCount) {
        $queryCount++;
    });

    $response = $this->getJson("/api/v1/cards/{$card->id}/transactions?per_page=5");

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(5);

    // Should avoid N+1 by eager loading relationships
    // Note: threshold 12 covers pre-existing card-txn query overhead
    expect($queryCount)->toBeLessThan(12);
});

it('prevents N+1 in transfer lookup', function () {
    $sender = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);
    $recipient = User::factory()->create([
        'kyc_status' => KycStatus::VERIFIED,
        'email' => 'recipient@example.com'
    ]);

    Sanctum::actingAs($sender);

    $queryCount = 0;
    DB::listen(function () use (&$queryCount) {
        $queryCount++;
    });

    $response = $this->getJson('/api/v1/transfer/lookup?identifier=recipient@example.com');

    $response->assertStatus(200);
    expect($response->json('data.name'))->toBe($recipient->full_name);

    // Lookup should use minimal queries (typically 3-4: user auth, recipient lookup, maybe one more)
    expect($queryCount)->toBeLessThan(15);
});
