<?php

use App\Models\User;
use App\Models\Wallet;
use App\Enums\KycStatus;
use Laravel\Sanctum\Sanctum;

beforeEach(fn () => enableCardsFeature());

it('validates card creation - wallet_id is required', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED_LEVEL_2]);
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/cards', [
        'brand' => 'visa'
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.wallet_id.0', 'المحفظة مطلوبة.');
});

it('validates card creation - brand is required', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED_LEVEL_2]);
    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'currency' => 'USD']);
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/cards', [
        'wallet_id' => $wallet->id
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.brand.0', 'نوع البطاقة مطلوب.');
});

it('validates card creation - brand must be visa or mastercard', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED_LEVEL_2]);
    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'currency' => 'USD']);
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/cards', [
        'wallet_id' => $wallet->id,
        'brand' => 'amex'
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.brand.0', 'نوع البطاقة يجب أن يكون visa أو mastercard.');
});

it('validates card creation - color must be hex format', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED_LEVEL_2]);
    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'currency' => 'USD', 'balance' => 100]);
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/cards', [
        'wallet_id' => $wallet->id,
        'brand' => 'visa',
        'color' => 'red'
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.color.0', 'لون البطاقة يجب أن يكون بصيغة HEX (#RRGGBB).');
});

it('validates card creation - spending_limit must be in range', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED_LEVEL_2]);
    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'currency' => 'USD', 'balance' => 100]);
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/cards', [
        'wallet_id' => $wallet->id,
        'brand' => 'visa',
        'spending_limit' => 50
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.spending_limit.0', 'حد الإنفاق يجب أن يكون 100 على الأقل.');
});

it('validates load card - amount is required', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED_LEVEL_2]);
    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'currency' => 'USD', 'balance' => 100]);
    $card = app(\App\Services\CardService::class)->createCard($user, $wallet, 'visa', 'virtual')['card'];
    Sanctum::actingAs($user);

    $response = $this->postJson("/api/v1/cards/{$card['id']}/load", []);

    $response->assertStatus(422)
        ->assertJsonPath('errors.amount.0', 'المبلغ مطلوب.');
});

it('validates load card - amount must be numeric', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED_LEVEL_2]);
    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'currency' => 'USD', 'balance' => 100]);
    $card = app(\App\Services\CardService::class)->createCard($user, $wallet, 'visa', 'virtual')['card'];
    Sanctum::actingAs($user);

    $response = $this->postJson("/api/v1/cards/{$card['id']}/load", [
        'amount' => 'abc'
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.amount.0', 'المبلغ يجب أن يكون رقماً.');
});

it('validates load card - amount must not exceed max', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED_LEVEL_2]);
    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'currency' => 'USD', 'balance' => 100000]);
    $card = app(\App\Services\CardService::class)->createCard($user, $wallet, 'visa', 'virtual')['card'];
    Sanctum::actingAs($user);

    $response = $this->postJson("/api/v1/cards/{$card['id']}/load", [
        'amount' => 10001
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.amount.0', 'المبلغ يجب أن لا يتجاوز 10,000.');
});

it('validates update card - nickname must not exceed length', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED_LEVEL_2]);
    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'currency' => 'USD', 'balance' => 100]);
    $card = app(\App\Services\CardService::class)->createCard($user, $wallet, 'visa', 'virtual')['card'];
    Sanctum::actingAs($user);

    $longName = str_repeat('a', 51);
    $response = $this->putJson("/api/v1/cards/{$card['id']}", [
        'nickname' => $longName
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.nickname.0', 'الاسم المستعار يجب أن لا يتجاوز 50 حرف.');
});

it('validates update card - daily_limit must be in range', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED_LEVEL_2]);
    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'currency' => 'USD', 'balance' => 100]);
    $card = app(\App\Services\CardService::class)->createCard($user, $wallet, 'visa', 'virtual')['card'];
    Sanctum::actingAs($user);

    $response = $this->putJson("/api/v1/cards/{$card['id']}", [
        'daily_limit' => 50
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.daily_limit.0', 'الحد اليومي يجب أن يكون 100 على الأقل.');
});
