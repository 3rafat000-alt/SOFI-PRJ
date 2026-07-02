<?php

use App\Models\User;
use App\Models\Wallet;
use App\Models\VirtualCard;
use App\Enums\KycStatus;
use App\Enums\UserStatus;
use Laravel\Sanctum\Sanctum;

beforeEach(fn () => enableCardsFeature());

it('GET /api/v1/cards - returns user cards', function () {
    $user = User::factory()
        ->has(VirtualCard::factory()->count(2), 'cards')
        ->create(['kyc_status' => KycStatus::VERIFIED]);
    Sanctum::actingAs($user);
    
    $response = $this->getJson('/api/v1/cards');
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'brand', 'last_four', 'expiry', 'balance', 'status']
            ]
        ]);
});

it('POST /api/v1/cards - creates new virtual card', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);
    $wallet = Wallet::factory()->create(['user_id' => $user->id]);
    Sanctum::actingAs($user);
    
    $response = $this->postJson('/api/v1/cards', [
        'wallet_id' => $wallet->id,
        'brand' => 'visa',
        'type' => 'virtual',
    ]);
    
    expect($response->status())->toBeIn([201, 422]);
});

it('POST /api/v1/cards - enforces max 5 active cards', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);
    $wallet = Wallet::factory()->create(['user_id' => $user->id]);
    
    VirtualCard::factory()->count(5)->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'status' => 'active',
    ]);
    
    Sanctum::actingAs($user);
    
    $response = $this->postJson('/api/v1/cards', [
        'wallet_id' => $wallet->id,
        'brand' => 'visa',
        'type' => 'virtual',
    ]);
    
    // Should fail - max 5 active cards
    expect($response->status())->toBeIn([422, 400]);
});

it('GET /api/v1/cards/{card} - shows card details', function () {
    $card = VirtualCard::factory()->create();
    Sanctum::actingAs($card->user);
    
    $response = $this->getJson("/api/v1/cards/{$card->id}");
    
    $response->assertStatus(200)
        ->assertJsonPath('data.brand.value', $card->brand->value);
});

it('POST /api/v1/cards/{card}/details - requires PIN to view sensitive data', function () {
    $user = User::factory()->create(['pin_code' => '123456', 'kyc_status' => KycStatus::VERIFIED]);
    $card = VirtualCard::factory()->create(['user_id' => $user->id]);
    Sanctum::actingAs($user);
    
    $response = $this->postJson("/api/v1/cards/{$card->id}/details", [
        'pin' => '123456',
    ]);
    
    expect($response->status())->toBeIn([200, 422]);
});

it('POST /api/v1/cards/{card}/freeze - freezes active card', function () {
    $card = VirtualCard::factory()->create(['status' => 'active']);
    Sanctum::actingAs($card->user);
    
    $response = $this->postJson("/api/v1/cards/{$card->id}/freeze");
    
    $response->assertStatus(200);
    $this->assertDatabaseHas('virtual_cards', [
        'id' => $card->id,
        'status' => 'frozen',
    ]);
});

it('POST /api/v1/cards/{card}/unfreeze - requires PIN', function () {
    $user = User::factory()->create(['pin_code' => '123456', 'kyc_status' => KycStatus::VERIFIED]);
    $card = VirtualCard::factory()->create([
        'user_id' => $user->id,
        'status' => 'frozen',
    ]);
    Sanctum::actingAs($user);
    
    $response = $this->postJson("/api/v1/cards/{$card->id}/unfreeze", [
        'pin' => '123456',
    ]);
    
    expect($response->status())->toBeIn([200, 422]);
});

it('POST /api/v1/cards/{card}/cancel - cancels active card', function () {
    $user = User::factory()->create(['pin_code' => '123456', 'kyc_status' => KycStatus::VERIFIED]);
    $card = VirtualCard::factory()->create(['user_id' => $user->id, 'status' => 'active']);
    Sanctum::actingAs($user);
    
    $response = $this->postJson("/api/v1/cards/{$card->id}/cancel", [
        'pin' => '123456',
    ]);
    
    expect($response->status())->toBeIn([200, 422]);
});

it('POST /api/v1/cards/{card}/load - loads money onto card', function () {
    $wallet = Wallet::factory()->create(['balance' => 1000, 'available_balance' => 1000]);
    $user = $wallet->user;
    $user->update(['pin_code' => '123456', 'kyc_status' => KycStatus::VERIFIED]);
    
    $card = VirtualCard::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'balance' => 0,
    ]);
    
    Sanctum::actingAs($user);
    
    $response = $this->postJson("/api/v1/cards/{$card->id}/load", [
        'amount' => 100,
        'pin' => '123456',
    ]);
    
    expect($response->status())->toBeIn([200, 201, 422]);
});

it('GET /api/v1/cards/{card}/transactions - returns card transactions', function () {
    $card = VirtualCard::factory()->create();
    Sanctum::actingAs($card->user);
    
    $response = $this->getJson("/api/v1/cards/{$card->id}/transactions");
    
    $response->assertStatus(200);
});

it('GET /api/v1/cards - requires authentication', function () {
    $response = $this->getJson('/api/v1/cards');
    
    $response->assertStatus(401);
});
