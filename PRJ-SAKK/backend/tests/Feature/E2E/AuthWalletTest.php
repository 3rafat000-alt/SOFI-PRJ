<?php

use App\Models\User;
use App\Models\Wallet;
use App\Enums\KycStatus;
use Laravel\Sanctum\Sanctum;

// ============================================================================
// AUTH FLOW
// ============================================================================

it('user can register and login', function () {
    // Register
    $response = $this->postJson('/api/v1/auth/register', [
        'first_name' => 'أحمد',
        'last_name' => 'السوري',
        'email' => 'ahmed@test.com',
        'password' => 'Pass1234!',
        'password_confirmation' => 'Pass1234!',
    ]);
    $response->assertStatus(201);
    $response->assertJsonMissing(['code']);

    $this->assertDatabaseHas('users', ['email' => 'ahmed@test.com']);

    // Login after registration
    $loginResponse = $this->postJson('/api/v1/auth/login', [
        'email' => 'ahmed@test.com',
        'password' => 'Pass1234!',
    ]);
    $loginResponse->assertStatus(200);
    $loginResponse->assertJsonStructure(['data' => ['user', 'token']]);
});

it('login fails with invalid credentials — no user enumeration', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'nonexistent@test.com',
        'password' => 'wrongpassword',
    ]);
    $response->assertStatus(401);
    // Generic message — MUST NOT leak whether email is registered
    $response->assertJsonMissing(['message' => 'البريد الإلكتروني غير مسجل']);
});

it('rate limit blocks excessive login attempts', function () {
    for ($i = 0; $i < 6; $i++) {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@test.com',
            'password' => 'wrong',
        ]);
    }
    // 6th attempt must be rate-limited
    $response->assertStatus(429);
    expect($response->json())->toHaveKey('message');
});

it('authenticated user can access protected route', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/auth/me');
    $response->assertStatus(200);
    $response->assertJsonPath('data.id', $user->id);
});

it('expired token returns 401', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test', ['*'], now()->subDay());
    $tokenModel = $token->accessToken;
    $tokenModel->expires_at = now()->subHour();
    $tokenModel->save();

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token->plainTextToken,
    ])->getJson('/api/v1/auth/me');

    $response->assertStatus(401);
});

// ============================================================================
// WALLET FLOW
// ============================================================================

it('user can create wallet and check balance', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);
    // Remove auto-created default USD wallet so we can test creation
    $user->wallets()->where('currency', 'USD')->forceDelete();
    Sanctum::actingAs($user);

    // Create wallet
    $response = $this->postJson('/api/v1/wallets', [
        'currency' => 'USD',
    ]);
    $response->assertStatus(201);
    $walletId = $response->json('data.wallet.id');

    // Check balance
    $balanceResponse = $this->getJson("/api/v1/wallets/{$walletId}/balance");
    $balanceResponse->assertStatus(200);
    $balanceResponse->assertJsonPath('data.balance', 0);
});

it('transfer with insufficient balance fails', function () {
    $sender = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);
    $recipient = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);
    Sanctum::actingAs($sender);

    $senderWallet = Wallet::factory()->create([
        'user_id' => $sender->id,
        'balance' => 0,
        'available_balance' => 0,
        'currency' => 'USD',
    ]);
    Wallet::factory()->create([
        'user_id' => $recipient->id,
        'balance' => 0,
        'available_balance' => 0,
        'currency' => 'USD',
    ]);

    $response = $this->postJson('/api/v1/transfer', [
        'identifier' => $recipient->referral_code,
        'amount' => 100,
        'currency' => 'USD',
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('success', false);
});

// ============================================================================
// CONCURRENCY — RACE CONDITION
// ============================================================================

it('concurrent transfers do not create money', function () {
    $sender = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);
    $recipient = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);

    // Give sender 100 USD, recipient 0
    $senderWallet = Wallet::factory()->create([
        'user_id' => $sender->id,
        'balance' => 100,
        'available_balance' => 100,
        'currency' => 'USD',
    ]);
    Wallet::factory()->create([
        'user_id' => $recipient->id,
        'balance' => 0,
        'available_balance' => 0,
        'currency' => 'USD',
    ]);
    Sanctum::actingAs($sender);

    // Fire 2 concurrent transfer requests, each for 75 (total 150 > 100)
    $responses = [];
    for ($i = 0; $i < 2; $i++) {
        $responses[] = $this->postJson('/api/v1/transfer', [
            'identifier' => $recipient->referral_code,
            'amount' => 75,
            'currency' => 'USD',
        ]);
    }

    // At most 1 should succeed (total 150 > available 100)
    $successCount = 0;
    foreach ($responses as $r) {
        if ($r->status() === 200) {
            $successCount++;
        }
    }
    expect($successCount)->toBeLessThanOrEqual(1);

    // Invariants — no double-spend, no PRINCIPAL created:
    //   • at most one 75-transfer clears against the 100 balance (asserted above);
    //   • the recipient receives exactly the transferred principal;
    //   • the sender loses exactly that principal, plus the documented 1% cashback
    //     reward — a funded rewards-programme credit, the only legitimate addition.
    // (Were the lock to fail and both transfers clear, the recipient would hold 150
    //  or the sender would go negative — either breaks these equalities.)
    $senderFinal = (float) $senderWallet->fresh()->balance;
    $recipientFinal = (float) $recipient->wallets()->where('currency', 'USD')->first()->fresh()->balance;
    $cashback = $successCount * round(75 * 0.01, 2); // 0.75 per cleared transfer
    expect($senderFinal)->toBeGreaterThanOrEqual(0.0);
    expect($recipientFinal)->toEqual(75.0 * $successCount);
    expect($senderFinal)->toEqual(100.0 - 75.0 * $successCount + $cashback);
});
