<?php

use App\Models\User;
use App\Models\Wallet;
use App\Enums\KycStatus;
use App\Enums\TransactionType;
use Laravel\Sanctum\Sanctum;

/**
 * Peer-to-peer transfer between SAKK users.
 * Same-currency, instant, free. Recipient resolved by tag / email / phone.
 */

function makeUser(float $usd = 0): User
{
    $user = User::factory()->create([
        'kyc_status' => KycStatus::VERIFIED,
        'pin_code' => Hash::make('123456'),
    ]);

    // The User model auto-creates a USD wallet on creation — update it.
    $user->wallets()->where('currency', 'USD')->update([
        'balance' => $usd,
        'available_balance' => $usd,
        'pending_balance' => 0,
        'daily_limit' => 1_000_000,
        'monthly_limit' => 100_000_000,
    ]);

    return $user;
}

it('sends money to another user by SAKK tag', function () {
    $sender = makeUser(usd: 1000);
    $recipient = makeUser(usd: 0);
    Sanctum::actingAs($sender);

    $response = $this->postJson('/api/v1/transfer', [
        'identifier' => $recipient->referral_code,
        'amount' => 100,
        'currency' => 'USD',
        'note' => 'غداء',
        'pin' => '123456',
    ]);

    $response->assertStatus(200)->assertJsonPath('success', true);

    $senderUsd = $sender->wallets()->where('currency', 'USD')->first();
    $recipientUsd = $recipient->wallets()->where('currency', 'USD')->first();

    // 1000 - 100 = 900 (sender), but 1% cashback = 1 → 901
    $this->assertEqualsWithDelta(901.0, (float) $senderUsd->balance, 0.001);
    $this->assertEqualsWithDelta(100.0, (float) $recipientUsd->balance, 0.001);
});

it('creates TRANSFER_OUT and TRANSFER_IN transactions', function () {
    $sender = makeUser(usd: 500);
    $recipient = makeUser(usd: 0);
    Sanctum::actingAs($sender);

    $this->postJson('/api/v1/transfer', [
        'identifier' => $recipient->referral_code,
        'amount' => 50,
        'currency' => 'USD',
        'pin' => '123456',
    ])->assertStatus(200);

    expect($sender->transactions()->where('type', TransactionType::TRANSFER_OUT)->count())->toBe(1);
    expect($recipient->transactions()->where('type', TransactionType::TRANSFER_IN)->count())->toBe(1);
});

it('resolves a recipient by email via lookup', function () {
    $sender = makeUser(usd: 100);
    $recipient = makeUser(usd: 0);
    Sanctum::actingAs($sender);

    $this->getJson('/api/v1/transfer/lookup?identifier=' . urlencode($recipient->email))
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', $recipient->full_name)
        ->assertJsonPath('data.tag', $recipient->referral_code);
});

it('resolves a recipient by SAKK tag via lookup', function () {
    $sender = makeUser(usd: 100);
    $recipient = makeUser(usd: 0);
    Sanctum::actingAs($sender);

    $this->getJson('/api/v1/transfer/lookup?identifier=' . $recipient->referral_code)
        ->assertStatus(200)
        ->assertJsonPath('data.tag', $recipient->referral_code);
});

it('resolves a recipient by account number via lookup', function () {
    $sender = makeUser(usd: 100);
    $recipient = makeUser(usd: 0);
    Sanctum::actingAs($sender);

    $accountNumber = 'SK' . str_pad((string) $recipient->id, 8, '0', STR_PAD_LEFT);

    $this->getJson('/api/v1/transfer/lookup?identifier=' . $accountNumber)
        ->assertStatus(200)
        ->assertJsonPath('data.id', $recipient->id)
        ->assertJsonPath('data.account_number', $accountNumber);
});

it('sends money by account number', function () {
    $sender = makeUser(usd: 100);
    $recipient = makeUser(usd: 0);
    Sanctum::actingAs($sender);

    $accountNumber = 'SK' . str_pad((string) $recipient->id, 8, '0', STR_PAD_LEFT);

    $this->postJson('/api/v1/transfer', [
        'identifier' => $accountNumber,
        'amount' => 25,
        'currency' => 'USD',
        'pin' => '123456',
    ])->assertStatus(200)->assertJsonPath('success', true);

    expect((float) $recipient->wallets()->where('currency', 'USD')->first()->balance)->toBe(25.0);
});

it('returns 404 for an unknown recipient', function () {
    $sender = makeUser(usd: 100);
    Sanctum::actingAs($sender);

    $this->getJson('/api/v1/transfer/lookup?identifier=NONEXISTENT99')
        ->assertStatus(404)
        ->assertJsonPath('success', false);
});

it('rejects transfer with insufficient balance', function () {
    $sender = makeUser(usd: 10);
    $recipient = makeUser(usd: 0);
    Sanctum::actingAs($sender);

    $this->postJson('/api/v1/transfer', [
        'identifier' => $recipient->referral_code,
        'amount' => 100,
        'currency' => 'USD',
    ])->assertStatus(422)->assertJsonPath('success', false);

    $this->assertEqualsWithDelta(10.0, (float) $sender->wallets()->where('currency', 'USD')->first()->balance, 0.001);
});

it('rejects transfer to self', function () {
    $sender = makeUser(usd: 100);
    Sanctum::actingAs($sender);

    $this->postJson('/api/v1/transfer', [
        'identifier' => $sender->referral_code,
        'amount' => 10,
        'currency' => 'USD',
    ])->assertStatus(422);
});

it('auto-provisions the recipient USD wallet when missing', function () {
    $sender = makeUser(usd: 500);
    $recipient = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);
    // Force-delete the auto-created USD wallet so the recipient has no wallets
    $recipient->wallets()->forceDelete();
    Sanctum::actingAs($sender);

    $this->postJson('/api/v1/transfer', [
        'identifier' => $recipient->referral_code,
        'amount' => 100,
        'currency' => 'USD',
        'pin' => '123456',
    ])->assertStatus(200);

    $recipientUsd = $recipient->wallets()->where('currency', 'USD')->first();
    expect($recipientUsd)->not->toBeNull();
    $this->assertEqualsWithDelta(100.0, (float) $recipientUsd->balance, 0.001);
});

it('requires authentication', function () {
    $recipient = makeUser(usd: 0);

    $this->postJson('/api/v1/transfer', [
        'identifier' => $recipient->referral_code,
        'amount' => 10,
        'currency' => 'USD',
    ])->assertStatus(401);
});

// Item 5 (desk review): X-Idempotency-Key double-submit guard on transfer,
// fail-open mode (does not require the header).

it('still transfers when no X-Idempotency-Key header is sent (backward-compatible)', function () {
    $sender = makeUser(usd: 100);
    $recipient = makeUser(usd: 0);
    Sanctum::actingAs($sender);

    $this->postJson('/api/v1/transfer', [
        'identifier' => $recipient->referral_code,
        'amount' => 10,
        'currency' => 'USD',
        'pin' => '123456',
    ])->assertStatus(200)->assertJsonPath('success', true);

    $this->assertEqualsWithDelta(90.0, (float) $sender->wallets()->where('currency', 'USD')->first()->balance, 0.1);
});

it('dedupes a double-tap transfer sharing the same X-Idempotency-Key', function () {
    $sender = makeUser(usd: 100);
    $recipient = makeUser(usd: 0);
    Sanctum::actingAs($sender);

    $payload = [
        'identifier' => $recipient->referral_code,
        'amount' => 10,
        'currency' => 'USD',
        'pin' => '123456',
    ];
    $key = (string) \Illuminate\Support\Str::uuid();
    $headers = ['X-Idempotency-Key' => $key];

    $first = $this->postJson('/api/v1/transfer', $payload, $headers);
    $first->assertStatus(200)->assertJsonPath('success', true);

    $second = $this->postJson('/api/v1/transfer', $payload, $headers);
    $second->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertHeader('X-Idempotency-Replayed', 'true');

    // Exactly ONE transfer executed despite two identical requests.
    expect($sender->transactions()->where('type', \App\Enums\TransactionType::TRANSFER_OUT)->count())->toBe(1);
    $this->assertEqualsWithDelta(90.0, (float) $sender->wallets()->where('currency', 'USD')->first()->balance, 0.1);
    $this->assertEqualsWithDelta(10.0, (float) $recipient->wallets()->where('currency', 'USD')->first()->balance, 0.1);
});
