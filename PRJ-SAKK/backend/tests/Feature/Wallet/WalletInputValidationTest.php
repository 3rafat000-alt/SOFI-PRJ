<?php

use App\Models\User;
use App\Models\Wallet;
use App\Enums\KycStatus;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

it('validates wallet creation - currency is required', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/wallets', []);

    $response->assertStatus(422)
        ->assertJsonPath('errors.currency.0', 'العملة مطلوبة.');
});

it('validates wallet creation - currency must be USD or SYP', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/wallets', ['currency' => 'EUR']);

    $response->assertStatus(422)
        ->assertJsonPath('errors.currency.0', 'العملة يجب أن تكون USD أو SYP.');
});

it('validates deposit - amount is required', function () {
    $wallet = Wallet::factory()->create();
    Sanctum::actingAs($wallet->user);

    $response = $this->postJson("/api/v1/wallets/{$wallet->id}/deposit", []);

    $response->assertStatus(422)
        ->assertJsonPath('errors.amount.0', 'المبلغ مطلوب.');
});

it('validates deposit - amount must be numeric', function () {
    $wallet = Wallet::factory()->create();
    Sanctum::actingAs($wallet->user);

    $response = $this->postJson("/api/v1/wallets/{$wallet->id}/deposit", [
        'amount' => 'abc'
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.amount.0', 'المبلغ يجب أن يكون رقماً.');
});

it('validates deposit - amount must be positive', function () {
    $wallet = Wallet::factory()->create();
    Sanctum::actingAs($wallet->user);

    $response = $this->postJson("/api/v1/wallets/{$wallet->id}/deposit", [
        'amount' => -100
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.amount.0', 'المبلغ يجب أن يكون 0.01 على الأقل.');
});

it('validates deposit - amount must not exceed max', function () {
    $wallet = Wallet::factory()->create();
    Sanctum::actingAs($wallet->user);

    $response = $this->postJson("/api/v1/wallets/{$wallet->id}/deposit", [
        'amount' => 100001
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.amount.0', 'المبلغ يجب أن لا يتجاوز 100,000.');
});

it('validates withdraw - PIN is required', function () {
    $wallet = Wallet::factory()->create(['balance' => 1000]);
    Sanctum::actingAs($wallet->user);

    $response = $this->postJson("/api/v1/wallets/{$wallet->id}/withdraw", [
        'amount' => 100
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.pin.0', 'رمز PIN مطلوب.');
});

it('validates withdraw - PIN must be 6 digits', function () {
    $user = User::factory()->create(['pin_code' => Hash::make('123456')]);
    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 1000]);
    Sanctum::actingAs($user);

    $response = $this->postJson("/api/v1/wallets/{$wallet->id}/withdraw", [
        'amount' => 100,
        'pin' => '12345' // 5 digits
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.pin.0', 'رمز PIN يجب أن يكون 6 أرقام.');
});

it('validates withdraw - PIN must be numeric', function () {
    $user = User::factory()->create(['pin_code' => Hash::make('123456')]);
    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 1000]);
    Sanctum::actingAs($user);

    $response = $this->postJson("/api/v1/wallets/{$wallet->id}/withdraw", [
        'amount' => 100,
        'pin' => 'abcdef'
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.pin.0', 'رمز PIN يجب أن يحتوي على أرقام فقط.');
});

it('validates convert - from_currency and to_currency must differ', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/wallets/convert', [
        'from_currency' => 'USD',
        'to_currency' => 'USD',
        'amount' => 100
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.to_currency.0', 'يجب أن تختلف عملة المصدر عن الهدف.');
});

it('validates convert - amount must be positive', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/wallets/convert', [
        'from_currency' => 'USD',
        'to_currency' => 'SYP',
        'amount' => 0
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.amount.0', 'المبلغ يجب أن يكون 0.01 على الأقل.');
});
