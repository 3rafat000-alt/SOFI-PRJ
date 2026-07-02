<?php

use App\Models\PaymentRequest;
use App\Models\User;
use App\Enums\KycStatus;
use Laravel\Sanctum\Sanctum;

/**
 * Payment requests: create a request, view it, and pay it via the P2P engine.
 */

function makePrUser(float $usd = 0): User
{
    $user = User::factory()->create([
        'kyc_status' => KycStatus::VERIFIED,
        'pin_code' => Hash::make('123456'),
    ]);
    $user->wallets()->where('currency', 'USD')->update([
        'balance' => $usd,
        'available_balance' => $usd,
        'daily_limit' => 1_000_000,
        'monthly_limit' => 100_000_000,
    ]);
    return $user;
}

it('creates a payment request', function () {
    $user = makePrUser();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/payment-requests', [
        'amount' => 25,
        'currency' => 'USD',
        'note' => 'مقابل الكتاب',
    ])->assertStatus(201)
      ->assertJsonPath('success', true)
      ->assertJsonPath('data.amount', 25)
      ->assertJsonPath('data.status', 'pending')
      ->assertJsonPath('data.is_mine', true);
});

it('lets another user view and pay a request', function () {
    $payee = makePrUser();
    $payer = makePrUser(usd: 100);

    Sanctum::actingAs($payee);
    $uuid = $this->postJson('/api/v1/payment-requests', ['amount' => 30, 'currency' => 'USD'])
        ->json('data.uuid');

    Sanctum::actingAs($payer);
    $this->getJson("/api/v1/payment-requests/$uuid")
        ->assertStatus(200)
        ->assertJsonPath('data.is_payable', true);

    $this->postJson("/api/v1/payment-requests/$uuid/pay", ['pin' => '123456'])
        ->assertStatus(200)
        ->assertJsonPath('success', true);

    // 100 - 30 = 70, + 1% cashback (0.30) = 70.30
    expect((float) $payer->wallets()->where('currency', 'USD')->first()->balance)->toBe(70.30);
    expect((float) $payee->wallets()->where('currency', 'USD')->first()->balance)->toBe(30.0);
    expect(PaymentRequest::where('uuid', $uuid)->first()->status)->toBe('paid');
});

it('rejects paying an already paid request', function () {
    $payee = makePrUser();
    $payer = makePrUser(usd: 100);

    Sanctum::actingAs($payee);
    $uuid = $this->postJson('/api/v1/payment-requests', ['amount' => 10, 'currency' => 'USD'])->json('data.uuid');

    Sanctum::actingAs($payer);
    $this->postJson("/api/v1/payment-requests/$uuid/pay", ['pin' => '123456'])->assertStatus(200);
    $this->postJson("/api/v1/payment-requests/$uuid/pay", ['pin' => '123456'])->assertStatus(422);
});

it('prevents paying your own request', function () {
    $user = makePrUser(usd: 100);
    Sanctum::actingAs($user);

    $uuid = $this->postJson('/api/v1/payment-requests', ['amount' => 10, 'currency' => 'USD'])->json('data.uuid');
    $this->postJson("/api/v1/payment-requests/$uuid/pay", ['pin' => '123456'])->assertStatus(422);
});

it('defaults to a 24-hour expiry', function () {
    $user = makePrUser();
    Sanctum::actingAs($user);

    $uuid = $this->postJson('/api/v1/payment-requests', ['amount' => 10, 'currency' => 'USD'])->json('data.uuid');
    $pr = PaymentRequest::where('uuid', $uuid)->first();

    expect($pr->expires_at)->not->toBeNull();
    expect($pr->expires_at->diffInHours(now()))->toBeLessThanOrEqual(24);
    expect($pr->expires_at->isFuture())->toBeTrue();
});

it('rejects paying an expired request and reports expired status', function () {
    $payee = makePrUser();
    $payer = makePrUser(usd: 100);

    Sanctum::actingAs($payee);
    $uuid = $this->postJson('/api/v1/payment-requests', ['amount' => 10, 'currency' => 'USD'])->json('data.uuid');

    // Force expiry into the past
    PaymentRequest::where('uuid', $uuid)->update(['expires_at' => now()->subHour()]);

    // View as the payee (owner) — non-directed requests are only visible to the creator
    $this->getJson("/api/v1/payment-requests/$uuid")
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'expired')
        ->assertJsonPath('data.is_payable', false);

    Sanctum::actingAs($payer);
    $this->postJson("/api/v1/payment-requests/$uuid/pay", ['pin' => '123456'])->assertStatus(422);
});
