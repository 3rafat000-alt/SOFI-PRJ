<?php

use App\Models\User;
use App\Models\Wallet;
use App\Enums\KycStatus;
use App\Enums\UserStatus;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

it('GET /api/v1/wallets - returns user wallets', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);
    // Update the auto-created USD wallet
    $user->wallets()->where('currency', 'USD')->update(['balance' => 100, 'available_balance' => 100]);
    Sanctum::actingAs($user);
    
    $response = $this->getJson('/api/v1/wallets');
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'currency', 'balance', 'available_balance', 'is_default']
            ]
        ]);
});

it('POST /api/v1/wallets - creates new wallet', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);
    // First force-delete the auto-created USD wallet so we can test creating it
    $user->wallets()->where('currency', 'USD')->forceDelete();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/wallets', [
        'currency' => 'USD',
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('wallets', [
        'user_id' => $user->id,
        'currency' => 'USD',
    ]);
});

it('POST /api/v1/wallets - prevents duplicate currency', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);
    // User model auto-creates USD wallet on creation
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/wallets', [
        'currency' => 'USD',
    ]);

    $response->assertStatus(422);
});

it('GET /api/v1/wallets/{wallet} - shows wallet details', function () {
    $wallet = Wallet::factory()->create(['balance' => 5000]);
    $user = $wallet->user;
    $user->update(['kyc_status' => KycStatus::VERIFIED]);
    Sanctum::actingAs($user);
    
    $response = $this->getJson("/api/v1/wallets/{$wallet->id}");
    
    $response->assertStatus(200)
        ->assertJsonPath('data.currency', $wallet->currency);
});

it('GET /api/v1/wallets/{wallet}/balance - returns balance', function () {
    $wallet = Wallet::factory()->create([
        'balance' => 5000,
        'available_balance' => 4800,
        'pending_balance' => 200,
    ]);
    Sanctum::actingAs($wallet->user);
    
    $response = $this->getJson("/api/v1/wallets/{$wallet->id}/balance");
    
    $response->assertStatus(200)
        ->assertJsonPath('data.balance', 5000)
        ->assertJsonPath('data.available_balance', 4800);
});

it('POST /api/v1/wallets/{wallet}/deposit - deposits to wallet', function () {
    $user = User::factory()->create(['pin_code' => Hash::make('123456'), 'kyc_status' => KycStatus::VERIFIED]);
    $wallet = Wallet::factory()->create([
        'user_id' => $user->id,
        'balance' => 100,
    ]);
    Sanctum::actingAs($user);

    $response = $this->postJson("/api/v1/wallets/{$wallet->id}/deposit", [
        'amount' => 500,
        'pin' => '123456',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('transactions', [
        'wallet_id' => $wallet->id,
        'type' => 'deposit',
        'amount' => 500,
        'status' => 'completed',
    ]);
});

it('POST /api/v1/wallets/{wallet}/withdraw - requires PIN validation', function () {
    $wallet = Wallet::factory()->create(['balance' => 1000]);
    Sanctum::actingAs($wallet->user);

    $response = $this->postJson("/api/v1/wallets/{$wallet->id}/withdraw", [
        'amount' => 100,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'رمز PIN مطلوب.');
});

it('POST /api/v1/wallets/{wallet}/withdraw - rejects invalid PIN', function () {
    $user = User::factory()->create(['pin_code' => Hash::make('123456'), 'kyc_status' => KycStatus::VERIFIED]);
    $wallet = Wallet::factory()->create([
        'user_id' => $user->id,
        'balance' => 1000,
        'available_balance' => 1000,
    ]);
    Sanctum::actingAs($user);

    $response = $this->postJson("/api/v1/wallets/{$wallet->id}/withdraw", [
        'amount' => 100,
        'pin' => '654321',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'رمز PIN غير صحيح');
});

it('POST /api/v1/wallets/{wallet}/withdraw - processes withdrawal with valid PIN', function () {
    $user = User::factory()->create(['pin_code' => Hash::make('123456'), 'kyc_status' => KycStatus::VERIFIED]);
    $wallet = Wallet::factory()->create([
        'user_id' => $user->id,
        'balance' => 1000,
        'available_balance' => 1000,
    ]);
    Sanctum::actingAs($user);

    $response = $this->postJson("/api/v1/wallets/{$wallet->id}/withdraw", [
        'amount' => 100,
        'pin' => '123456',
    ]);

    expect($response->status())->toBe(200);
});



it('GET /api/v1/wallets/{wallet}/stats - returns wallet stats', function () {
    $wallet = Wallet::factory()->create();
    Sanctum::actingAs($wallet->user);
    
    $response = $this->getJson("/api/v1/wallets/{$wallet->id}/stats");
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['balance', 'limits', 'totals', 'today', 'this_month']
        ]);
});

it('GET /api/v1/wallets/{wallet}/transactions - returns filtered transactions', function () {
    $wallet = Wallet::factory()->create();
    Sanctum::actingAs($wallet->user);
    
    $response = $this->getJson("/api/v1/wallets/{$wallet->id}/transactions");
    
    $response->assertStatus(200);
});
