<?php

use App\Models\User;
use App\Models\Wallet;
use App\Models\VirtualCard;
use App\Models\Transaction;
use App\Enums\KycStatus;
use App\Enums\TransactionType;
use App\Enums\CardStatus;
use App\Services\StripeIssuingService;
use Laravel\Sanctum\Sanctum;

beforeEach(fn () => enableCardsFeature());

/**
 * Fee is $10 by default (CardPricing has no active row in tests, so
 * CardService::getPricing() falls back to purchase_price=10.00, kyc_level_required=2).
 */
it('POST /api/v1/cards issues via Stripe, not the fake local-PAN path', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED, 'kyc_level' => 2]);
    $wallet = Wallet::factory()->create([
        'user_id' => $user->id,
        'currency' => 'USD',
        'balance' => 100,
        'available_balance' => 100,
    ]);

    // Mock the Stripe issuance to avoid a real network call, but exercise the
    // real store() control flow (fee charge -> issuance -> resource).
    $issuedCard = VirtualCard::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'provider' => 'stripe',
        'provider_card_id' => 'ic_test_123',
        'status' => CardStatus::ACTIVE,
    ]);

    $mock = Mockery::mock(StripeIssuingService::class);
    $mock->shouldReceive('isConfigured')->andReturn(true);
    $mock->shouldReceive('issueVirtualCard')->once()->andReturn([
        'success' => true,
        'card' => ['id' => $issuedCard->id, 'uuid' => $issuedCard->uuid],
    ]);
    $this->app->instance(StripeIssuingService::class, $mock);

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/cards', [
        'wallet_id' => $wallet->id,
        'brand' => 'visa',
    ]);

    $response->assertStatus(201);
    expect($response->json('data.id'))->toBe($issuedCard->id);

    // Purchase fee was charged (wallet debited, FEE transaction recorded).
    $wallet->refresh();
    expect((float) $wallet->balance)->toBe(90.0);
    $this->assertDatabaseHas('transactions', [
        'wallet_id' => $wallet->id,
        'type' => TransactionType::FEE->value,
        'amount' => -10,
    ]);
});

it('POST /api/v1/cards refunds the purchase fee when Stripe issuance fails', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED, 'kyc_level' => 2]);
    $wallet = Wallet::factory()->create([
        'user_id' => $user->id,
        'currency' => 'USD',
        'balance' => 100,
        'available_balance' => 100,
    ]);

    $mock = Mockery::mock(StripeIssuingService::class);
    $mock->shouldReceive('isConfigured')->andReturn(true);
    $mock->shouldReceive('issueVirtualCard')->once()->andReturn([
        'success' => false,
        'error' => 'Stripe API error',
    ]);
    $this->app->instance(StripeIssuingService::class, $mock);

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/cards', [
        'wallet_id' => $wallet->id,
        'brand' => 'visa',
    ]);

    $response->assertStatus(422);

    // Fee was charged then fully refunded -> net balance unchanged.
    $wallet->refresh();
    expect((float) $wallet->balance)->toBe(100.0);

    $this->assertDatabaseHas('transactions', [
        'wallet_id' => $wallet->id,
        'type' => TransactionType::FEE->value,
        'amount' => -10,
    ]);
    $this->assertDatabaseHas('transactions', [
        'wallet_id' => $wallet->id,
        'type' => TransactionType::FEE->value,
        'amount' => 10,
    ]);

    // No card was ever created for this failed attempt.
    expect(VirtualCard::where('user_id', $user->id)->count())->toBe(0);
});

it('POST /api/v1/cards returns 422 (not a fake card) when Stripe is not configured', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED, 'kyc_level' => 2]);
    $wallet = Wallet::factory()->create([
        'user_id' => $user->id,
        'currency' => 'USD',
        'balance' => 100,
        'available_balance' => 100,
    ]);

    $mock = Mockery::mock(StripeIssuingService::class);
    $mock->shouldReceive('isConfigured')->andReturn(false);
    $mock->shouldNotReceive('issueVirtualCard');
    $this->app->instance(StripeIssuingService::class, $mock);

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/cards', [
        'wallet_id' => $wallet->id,
        'brand' => 'visa',
    ]);

    $response->assertStatus(422);

    // No fee was ever charged, no card created.
    $wallet->refresh();
    expect((float) $wallet->balance)->toBe(100.0);
    expect(VirtualCard::where('user_id', $user->id)->count())->toBe(0);
});
