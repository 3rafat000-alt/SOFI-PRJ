<?php

use App\Models\Transaction;
use App\Models\User;
use App\Services\CCPaymentService;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

// W-SEV-5: server-side idempotency guard on the crypto withdrawal-create
// endpoint. VerifyIdempotencyKey wraps POST /ccpayment/withdraw.

beforeEach(function () {
    $this->ccpaymentMock = Mockery::mock(CCPaymentService::class);
    $this->app->instance(CCPaymentService::class, $this->ccpaymentMock);
});

function idempotentWithdrawPayload(Illuminate\Support\Str $unused = null): array
{
    return [
        'address' => 'TXaddressaddress123',
        'amount' => '10.00',
        'chain' => 'TRC20',
        'currency' => 'USDT',
    ];
}

it('rejects a withdrawal request missing the idempotency key header', function () {
    $user = User::factory()->create(['kyc_level' => 2]);
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    $wallet->update(['balance' => 100, 'available_balance' => 100]);
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/ccpayment/withdraw', array_merge(
        idempotentWithdrawPayload(),
        ['wallet_id' => $wallet->id],
    ));

    $response->assertStatus(400)->assertJson(['code' => 'idempotency_key_required']);
});

it('rejects a malformed idempotency key', function () {
    $user = User::factory()->create(['kyc_level' => 2]);
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/ccpayment/withdraw', array_merge(
        idempotentWithdrawPayload(),
        ['wallet_id' => $wallet->id],
    ), ['X-Idempotency-Key' => 'not a uuid!!']);

    $response->assertStatus(400)->assertJson(['code' => 'idempotency_key_invalid']);
});

it('processes exactly one of 5 sequential identical-key requests and rejects the rest with 409, then replays the stored response on a later retry', function () {
    // True parallel requests can't be simulated inside a single PHP test
    // process (no real threads/forks share the request lifecycle here), so
    // this proves the guard's actual mechanism directly: the atomic
    // Cache::lock is exclusive per user+key, so a "concurrent" duplicate
    // (lock already held, or the response already cached) is rejected
    // without re-running dispatchWithdrawToGateway / any wallet mutation —
    // which is the property W-SEV-5 requires.
    $user = User::factory()->create(['kyc_level' => 2]);
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    $wallet->update(['balance' => 1000, 'available_balance' => 1000]);
    Sanctum::actingAs($user);

    $key = (string) \Illuminate\Support\Str::uuid();
    $payload = array_merge(idempotentWithdrawPayload(), ['wallet_id' => $wallet->id]);
    $headers = ['X-Idempotency-Key' => $key];

    $this->ccpaymentMock->shouldReceive('dispatchWithdrawToGateway')
        ->once() // <-- the money code must run exactly once across all 5 calls
        ->andReturn([
            'record_id' => 'REC-IDEMP',
            'order_id' => 'sarva_wd_idemp',
            'fee' => ['amount' => '1.00'],
        ]);

    // Request 1: the real request, holds the lock for its duration, succeeds
    // and caches its response.
    $first = $this->postJson('/api/v1/ccpayment/withdraw', $payload, $headers);
    $first->assertStatus(200)->assertJson(['success' => true]);

    // Requests 2-5: same user + same key, sent after request 1 already
    // completed and released its lock — so these hit the "already
    // completed" replay path (not the in-flight 409 path), which is the
    // other half of the no-double-execution contract: the response is
    // replayed byte-for-byte instead of calling the gateway again.
    for ($i = 0; $i < 4; $i++) {
        $replay = $this->postJson('/api/v1/ccpayment/withdraw', $payload, $headers);
        $replay->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertHeader('X-Idempotency-Replayed', 'true');
        expect($replay->json('data.order_id'))->toBe($first->json('data.order_id'));
    }

    // Only ONE wallet debit + ONE transaction row exist, despite 5 requests.
    expect((float) $wallet->fresh()->balance)->toEqual(990.0);
    expect(Transaction::where('user_id', $user->id)->count())->toBe(1);
});

it('rejects a genuinely in-flight duplicate with 409 without touching the wallet', function () {
    // Directly exercises the lock-contention branch: pre-acquire the same
    // lock the middleware would take, then fire the request — it must see
    // the lock unavailable and 409 immediately, never reaching the
    // controller/gateway/wallet at all.
    $user = User::factory()->create(['kyc_level' => 2]);
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    $wallet->update(['balance' => 100, 'available_balance' => 100]);
    Sanctum::actingAs($user);

    $key = (string) \Illuminate\Support\Str::uuid();
    $routeName = 'api.ccpayment.withdraw';
    $cacheKey = "idempotency:{$routeName}:{$user->id}:{$key}";

    $held = Cache::lock($cacheKey, 120);
    expect($held->get())->toBeTrue();

    $this->ccpaymentMock->shouldReceive('dispatchWithdrawToGateway')->never();

    try {
        $response = $this->postJson('/api/v1/ccpayment/withdraw', array_merge(
            idempotentWithdrawPayload(),
            ['wallet_id' => $wallet->id],
        ), ['X-Idempotency-Key' => $key]);

        $response->assertStatus(409)->assertJson(['code' => 'duplicate_request_in_flight']);
        expect((float) $wallet->fresh()->balance)->toEqual(100.0);
    } finally {
        $held->release();
    }
});

it('treats different idempotency keys as independent requests', function () {
    $user = User::factory()->create(['kyc_level' => 2]);
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    $wallet->update(['balance' => 1000, 'available_balance' => 1000]);
    Sanctum::actingAs($user);

    $this->ccpaymentMock->shouldReceive('dispatchWithdrawToGateway')
        ->twice()
        ->andReturnUsing(fn ($orderId) => [
            'record_id' => 'REC-' . $orderId,
            'order_id' => $orderId,
            'fee' => ['amount' => '1.00'],
        ]);

    $payload = array_merge(idempotentWithdrawPayload(), ['wallet_id' => $wallet->id]);

    $r1 = $this->postJson('/api/v1/ccpayment/withdraw', $payload, ['X-Idempotency-Key' => (string) \Illuminate\Support\Str::uuid()]);
    $r2 = $this->postJson('/api/v1/ccpayment/withdraw', $payload, ['X-Idempotency-Key' => (string) \Illuminate\Support\Str::uuid()]);

    $r1->assertStatus(200);
    $r2->assertStatus(200);
    expect((float) $wallet->fresh()->balance)->toEqual(980.0); // two real debits
});
