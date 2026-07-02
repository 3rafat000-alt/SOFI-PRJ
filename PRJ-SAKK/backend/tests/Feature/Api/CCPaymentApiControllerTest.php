<?php

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\CCPaymentService;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

// This suite covers App\Http\Controllers\API\CCPaymentController — the
// user-facing crypto deposit/withdraw endpoints (as opposed to the provider
// webhook controller, which has its own test file). CCPaymentService is
// mocked throughout — no live CCPayment HTTP calls.

beforeEach(function () {
    $this->ccpaymentMock = Mockery::mock(CCPaymentService::class);
    $this->app->instance(CCPaymentService::class, $this->ccpaymentMock);
});

// The withdraw-create route requires X-Idempotency-Key (W-SEV-5); every
// existing withdraw call below needs a fresh key so requests aren't treated
// as duplicates of one another across test cases.
function withdrawHeaders(): array
{
    return ['X-Idempotency-Key' => (string) \Illuminate\Support\Str::uuid()];
}

// ==================== getConfig ====================

it('returns config with active flag true', function () {
    Sanctum::actingAs(User::factory()->create());
    $this->ccpaymentMock->shouldReceive('isActive')->once()->andReturn(true);

    $response = $this->getJson('/api/v1/ccpayment/config');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => ['is_active' => true],
        ])
        ->assertJsonStructure(['data' => ['supported_coins', 'supported_chains', 'message']]);
});

it('returns config with active flag false', function () {
    Sanctum::actingAs(User::factory()->create());
    $this->ccpaymentMock->shouldReceive('isActive')->once()->andReturn(false);

    $response = $this->getJson('/api/v1/ccpayment/config');

    $response->assertStatus(200)->assertJson(['data' => ['is_active' => false]]);
});

it('locks the config response to USDT-only supported coins and chains', function () {
    Sanctum::actingAs(User::factory()->create());
    $this->ccpaymentMock->shouldReceive('isActive')->once()->andReturn(true);

    $response = $this->getJson('/api/v1/ccpayment/config');

    $response->assertStatus(200);
    expect($response->json('data.supported_coins'))->toBe(['USDT'])
        ->and(array_keys($response->json('data.supported_chains')))->toBe(['USDT']);
});

// ==================== getSupportedCoins ====================

it('returns supported coins list', function () {
    Sanctum::actingAs(User::factory()->create());
    $this->ccpaymentMock->shouldReceive('getAssetList')->once()->andReturn([
        'assets' => [['coinId' => 1280, 'symbol' => 'USDT']],
    ]);

    $response = $this->getJson('/api/v1/ccpayment/supported-coins');

    $response->assertStatus(200)
        ->assertJson(['success' => true, 'data' => ['count' => 1]]);
});

it('returns 500 when fetching supported coins throws', function () {
    Sanctum::actingAs(User::factory()->create());
    $this->ccpaymentMock->shouldReceive('getAssetList')->once()->andThrow(new Exception('down'));

    $response = $this->getJson('/api/v1/ccpayment/supported-coins');

    $response->assertStatus(500)->assertJson(['success' => false]);
});

// ==================== createDepositAddress ====================

it('creates a deposit address for the caller-owned wallet', function () {
    $user = User::factory()->create();
    $wallet = $user->wallets()->where('currency', 'USD')->first() ?? Wallet::factory()->for($user)->create();
    Sanctum::actingAs($user);

    $this->ccpaymentMock->shouldReceive('createWalletDeposit')
        ->once()
        ->with(Mockery::on(fn ($w) => $w->id === $wallet->id), 'TRC20', 'USDT')
        ->andReturn(['address' => 'Txxxaddr', 'memo' => null, 'reference_id' => 'sarva_ref']);

    $response = $this->postJson('/api/v1/ccpayment/deposit/address', [
        'wallet_id' => $wallet->id,
        'chain' => 'TRC20',
        'currency' => 'USDT',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'address' => 'Txxxaddr',
                'reference_id' => 'sarva_ref',
            ],
        ]);
});

it('rejects deposit address creation for a wallet not owned by the caller', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $wallet = $owner->wallets()->where('currency', 'USD')->first();
    Sanctum::actingAs($intruder);

    $response = $this->postJson('/api/v1/ccpayment/deposit/address', [
        'wallet_id' => $wallet->id,
        'chain' => 'TRC20',
        'currency' => 'USDT',
    ]);

    $response->assertStatus(403)->assertJson(['success' => false]);
});

it('validates deposit address chain and currency', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/ccpayment/deposit/address', [
        'wallet_id' => 999999,
        'chain' => 'NOPE',
        'currency' => 'NOPE',
    ]);

    $response->assertStatus(422);
});

// SEV-1 regression (580d51c): crypto deposit was frozen to USDT-only after the
// backend serviced non-USDT tokens 1:1 as USD, causing fund loss. These lock
// the currency/chain gates so a future edit can't silently widen them again.

it('rejects a BTC/ETH/USDC deposit address request with the USDT-only Arabic message', function (string $currency) {
    $user = User::factory()->create();
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/ccpayment/deposit/address', [
        'wallet_id' => $wallet->id,
        'chain' => 'TRC20',
        'currency' => $currency,
    ]);

    $response->assertStatus(422)
        ->assertJsonFragment(['currency' => ['العملة المدعومة حالياً هي USDT فقط']]);
})->with(['BTC', 'ETH', 'USDC']);

it('creates a USDT deposit address on TRC20', function () {
    $user = User::factory()->create();
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    Sanctum::actingAs($user);

    $this->ccpaymentMock->shouldReceive('createWalletDeposit')
        ->once()
        ->with(Mockery::on(fn ($w) => $w->id === $wallet->id), 'TRC20', 'USDT')
        ->andReturn(['address' => 'Tfakeaddr', 'memo' => null, 'reference_id' => 'sarva_fake_ref']);

    $response = $this->postJson('/api/v1/ccpayment/deposit/address', [
        'wallet_id' => $wallet->id,
        'chain' => 'TRC20',
        'currency' => 'USDT',
    ]);

    $response->assertStatus(200)->assertJson(['success' => true]);
});

it('rejects a USDT deposit address request on an unsupported BTC chain', function () {
    $user = User::factory()->create();
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    Sanctum::actingAs($user);

    $this->ccpaymentMock->shouldReceive('createWalletDeposit')->never();

    $response = $this->postJson('/api/v1/ccpayment/deposit/address', [
        'wallet_id' => $wallet->id,
        'chain' => 'BTC',
        'currency' => 'USDT',
    ]);

    $response->assertStatus(422);
});

it('returns 500 when deposit address creation throws', function () {
    $user = User::factory()->create();
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    Sanctum::actingAs($user);

    $this->ccpaymentMock->shouldReceive('createWalletDeposit')->once()->andThrow(new Exception('provider down'));

    $response = $this->postJson('/api/v1/ccpayment/deposit/address', [
        'wallet_id' => $wallet->id,
        'chain' => 'TRC20',
        'currency' => 'USDT',
    ]);

    $response->assertStatus(500)->assertJson(['success' => false]);
});

// ==================== getDepositStatus ====================

it('returns deposit status by reference', function () {
    $user = User::factory()->create();
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    Sanctum::actingAs($user);

    $tx = Transaction::factory()->for($user)->for($wallet)->create([
        'reference' => 'REC123',
        'status' => \App\Enums\TransactionStatus::COMPLETED,
        'amount' => 50,
    ]);

    $response = $this->getJson('/api/v1/ccpayment/deposit/REC123/status');

    $response->assertStatus(200)->assertJson(['success' => true, 'data' => ['reference' => 'REC123']]);
});

it('resolves deposit status by the metadata reference id when the primary reference differs', function () {
    $user = User::factory()->create();
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    Sanctum::actingAs($user);

    Transaction::factory()->for($user)->for($wallet)->create([
        'reference' => 'REC999',
        'metadata' => ['ccpayment_reference_id' => 'sarva_ref_abc'],
        'status' => \App\Enums\TransactionStatus::PENDING,
        'amount' => 0,
    ]);

    $response = $this->getJson('/api/v1/ccpayment/deposit/sarva_ref_abc/status');

    $response->assertStatus(200)->assertJson(['success' => true]);
});

it('returns 404 for an unknown deposit reference', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/ccpayment/deposit/UNKNOWN/status');

    $response->assertStatus(404)->assertJson(['success' => false]);
});

// ==================== getDepositHistory ====================

it('returns paginated deposit history for the caller only', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    $otherWallet = $other->wallets()->where('currency', 'USD')->first();
    Sanctum::actingAs($user);

    Transaction::factory()->for($user)->for($wallet)->create([
        'category' => \App\Enums\TransactionCategory::CRYPTO,
        'type' => \App\Enums\TransactionType::DEPOSIT,
    ]);
    Transaction::factory()->for($other)->for($otherWallet)->create([
        'category' => \App\Enums\TransactionCategory::CRYPTO,
        'type' => \App\Enums\TransactionType::DEPOSIT,
    ]);

    $response = $this->getJson('/api/v1/ccpayment/deposits');

    $response->assertStatus(200);
    expect($response->json('data.data'))->toHaveCount(1);
});

// ==================== withdraw ====================

it('processes a crypto withdrawal for a KYC-permitted user with sufficient balance', function () {
    $user = User::factory()->create(['kyc_level' => 2]);
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    $wallet->update(['balance' => 100, 'available_balance' => 100]);
    Sanctum::actingAs($user);

    // The controller now reserves its own orderId (Phase A) before calling
    // the gateway, so the dispatch mock echoes back whatever orderId it's
    // given rather than asserting a fixed literal.
    $this->ccpaymentMock->shouldReceive('dispatchWithdrawToGateway')
        ->once()
        ->withArgs(fn ($orderId) => is_string($orderId) && $orderId !== '')
        ->andReturnUsing(fn ($orderId) => [
            'record_id' => 'REC1',
            'order_id' => $orderId,
            'fee' => ['amount' => '1.00'],
        ]);

    $response = $this->postJson('/api/v1/ccpayment/withdraw', [
        'wallet_id' => $wallet->id,
        'address' => 'TXaddressaddress123',
        'amount' => '10.00',
        'chain' => 'TRC20',
        'currency' => 'USDT',
    ], withdrawHeaders());

    $response->assertStatus(200)->assertJson(['success' => true]);
    expect($response->json('data.order_id'))->toBeString()->not->toBeEmpty();
    expect($wallet->fresh()->balance)->toEqual(90.0);

    $tx = Transaction::where('reference', $response->json('data.order_id'))->first();
    expect($tx)->not->toBeNull();
    expect($tx->status)->toBe(\App\Enums\TransactionStatus::PENDING);
    expect($tx->metadata['gateway_dispatched'])->toBeTrue();
    expect($tx->metadata['ccpayment_record_id'])->toBe('REC1');
});

it('rejects withdrawal for a wallet not owned by the caller', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create(['kyc_level' => 2]);
    $wallet = $owner->wallets()->where('currency', 'USD')->first();
    Sanctum::actingAs($intruder);

    $response = $this->postJson('/api/v1/ccpayment/withdraw', [
        'wallet_id' => $wallet->id,
        'address' => 'TXaddressaddress123',
        'amount' => '10.00',
        'chain' => 'TRC20',
        'currency' => 'USDT',
    ], withdrawHeaders());

    $response->assertStatus(403);
});

it('blocks withdrawal for a user below the KYC level required for can_withdraw', function () {
    $user = User::factory()->create(['kyc_level' => 0]);
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    $wallet->update(['balance' => 1000, 'available_balance' => 1000]);
    Sanctum::actingAs($user);

    $this->ccpaymentMock->shouldReceive('dispatchWithdrawToGateway')->never();

    $response = $this->postJson('/api/v1/ccpayment/withdraw', [
        'wallet_id' => $wallet->id,
        'address' => 'TXaddressaddress123',
        'amount' => '10.00',
        'chain' => 'TRC20',
        'currency' => 'USDT',
    ], withdrawHeaders());

    $response->assertStatus(403)->assertJson(['success' => false, 'code' => 'kyc_required']);
});

it('rejects withdrawal when the wallet balance is insufficient', function () {
    $user = User::factory()->create(['kyc_level' => 2]);
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    $wallet->update(['balance' => 5, 'available_balance' => 5]);
    Sanctum::actingAs($user);

    $this->ccpaymentMock->shouldReceive('dispatchWithdrawToGateway')->never();

    $response = $this->postJson('/api/v1/ccpayment/withdraw', [
        'wallet_id' => $wallet->id,
        'address' => 'TXaddressaddress123',
        'amount' => '10.00',
        'chain' => 'TRC20',
        'currency' => 'USDT',
    ], withdrawHeaders());

    $response->assertStatus(400)->assertJson(['success' => false]);
    expect($wallet->fresh()->balance)->toEqual(5.0);
});

it('refunds the wallet and marks the transaction failed when the provider withdraw call throws', function () {
    // Optimistic-debit flow: Phase A debits + reserves the tx BEFORE the
    // gateway call, so a Phase-B gateway exception must refund the wallet
    // and mark the tx FAILED rather than never having debited at all — the
    // net balance ends up unchanged, but via debit-then-refund, not a no-op.
    $user = User::factory()->create(['kyc_level' => 2]);
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    $wallet->update(['balance' => 100, 'available_balance' => 100]);
    Sanctum::actingAs($user);

    $this->ccpaymentMock->shouldReceive('dispatchWithdrawToGateway')->once()->andThrow(new Exception('provider rejected'));

    $response = $this->postJson('/api/v1/ccpayment/withdraw', [
        'wallet_id' => $wallet->id,
        'address' => 'TXaddressaddress123',
        'amount' => '10.00',
        'chain' => 'TRC20',
        'currency' => 'USDT',
    ], withdrawHeaders());

    $response->assertStatus(500)->assertJson(['success' => false]);
    expect($wallet->fresh()->balance)->toEqual(100.0);
    expect($wallet->fresh()->available_balance)->toEqual(100.0);

    $tx = Transaction::where('user_id', $user->id)->where('amount', 10)->first();
    expect($tx)->not->toBeNull();
    expect($tx->status)->toBe(\App\Enums\TransactionStatus::FAILED);
    expect($tx->metadata['refunded'])->toBeTrue();
    expect($tx->metadata['gateway_dispatched'])->toBeFalse();
});

it('rejects a BTC withdrawal — USDT-only gate applies to withdraw too', function () {
    $user = User::factory()->create(['kyc_level' => 2]);
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    $wallet->update(['balance' => 100, 'available_balance' => 100]);
    Sanctum::actingAs($user);

    $this->ccpaymentMock->shouldReceive('dispatchWithdrawToGateway')->never();

    $response = $this->postJson('/api/v1/ccpayment/withdraw', [
        'wallet_id' => $wallet->id,
        'address' => 'TXaddressaddress123',
        'amount' => '10.00',
        'chain' => 'TRC20',
        'currency' => 'BTC',
    ], withdrawHeaders());

    $response->assertStatus(422)
        ->assertJsonFragment(['currency' => ['العملة المدعومة حالياً هي USDT فقط']]);
    expect($wallet->fresh()->balance)->toEqual(100.0);
});

it('validates withdrawal request fields', function () {
    $user = User::factory()->create(['kyc_level' => 2]);
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/ccpayment/withdraw', [
        'wallet_id' => 'not-an-id',
        'address' => 'short',
        'amount' => 'abc',
        'chain' => 'FOO',
        'currency' => 'FOO',
    ], withdrawHeaders());

    $response->assertStatus(422);
});

// ==================== getWithdrawFee ====================

it('returns the withdraw fee, resolving coinId/chain server-side', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->ccpaymentMock->shouldReceive('getCoinId')->once()->with('USDT', 'TRC20')->andReturn(1280);
    $this->ccpaymentMock->shouldReceive('ccChain')->once()->with('TRC20')->andReturn('TRX');
    $this->ccpaymentMock->shouldReceive('getWithdrawFee')->once()->with(1280, 'TRX')->andReturn(['amount' => '1.5']);

    $response = $this->getJson('/api/v1/ccpayment/withdraw/fee?chain=TRC20&currency=USDT');

    $response->assertStatus(200)->assertJson(['success' => true, 'data' => ['fee' => ['amount' => '1.5']]]);
});

it('ignores a client-supplied coin_id and derives it server-side', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->ccpaymentMock->shouldReceive('getCoinId')->once()->with('USDT', 'ERC20')->andReturn(1280);
    $this->ccpaymentMock->shouldReceive('ccChain')->once()->with('ERC20')->andReturn('ETH');
    $this->ccpaymentMock->shouldReceive('getWithdrawFee')->once()->andReturn(['amount' => '2.0']);

    // client sends a bogus CoinMarketCap-style coin_id — must be ignored
    $response = $this->getJson('/api/v1/ccpayment/withdraw/fee?chain=ERC20&currency=USDT&coin_id=1');

    $response->assertStatus(200)->assertJson(['success' => true]);
});

it('returns 500 when fee lookup throws', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->ccpaymentMock->shouldReceive('getCoinId')->once()->andReturn(1280);
    $this->ccpaymentMock->shouldReceive('ccChain')->once()->andReturn('TRX');
    $this->ccpaymentMock->shouldReceive('getWithdrawFee')->once()->andThrow(new Exception('down'));

    $response = $this->getJson('/api/v1/ccpayment/withdraw/fee?chain=TRC20');

    $response->assertStatus(500)->assertJson(['success' => false]);
});

it('validates the withdraw fee chain parameter is required', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/ccpayment/withdraw/fee');

    $response->assertStatus(422);
});

// ==================== getWithdrawStatus ====================

it('returns withdrawal status by reference for the owning user', function () {
    $user = User::factory()->create();
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    Sanctum::actingAs($user);

    Transaction::factory()->for($user)->for($wallet)->create([
        'reference' => 'ORD777',
        'status' => \App\Enums\TransactionStatus::PENDING,
    ]);

    $response = $this->getJson('/api/v1/ccpayment/withdraw/ORD777/status');

    $response->assertStatus(200)->assertJson(['success' => true, 'data' => ['reference' => 'ORD777']]);
});

it('returns 404 for a withdrawal reference belonging to another user', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $wallet = $owner->wallets()->where('currency', 'USD')->first();
    Sanctum::actingAs($intruder);

    Transaction::factory()->for($owner)->for($wallet)->create(['reference' => 'ORD888']);

    $response = $this->getJson('/api/v1/ccpayment/withdraw/ORD888/status');

    $response->assertStatus(404);
});

// ==================== getWithdrawHistory ====================

it('returns paginated withdrawal history for the caller only', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    $otherWallet = $other->wallets()->where('currency', 'USD')->first();
    Sanctum::actingAs($user);

    Transaction::factory()->for($user)->for($wallet)->create([
        'category' => \App\Enums\TransactionCategory::CRYPTO,
        'type' => \App\Enums\TransactionType::WITHDRAWAL,
    ]);
    Transaction::factory()->for($other)->for($otherWallet)->create([
        'category' => \App\Enums\TransactionCategory::CRYPTO,
        'type' => \App\Enums\TransactionType::WITHDRAWAL,
    ]);

    $response = $this->getJson('/api/v1/ccpayment/withdrawals');

    $response->assertStatus(200);
    expect($response->json('data.data'))->toHaveCount(1);
});

// ==================== getAssets / getAssetDetail ====================

it('returns merchant assets', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->ccpaymentMock->shouldReceive('getAssetList')->once()->andReturn(['assets' => [['coinId' => 1280]]]);

    $response = $this->getJson('/api/v1/ccpayment/assets');

    $response->assertStatus(200)->assertJson(['success' => true]);
});

it('returns 500 when merchant assets lookup throws', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->ccpaymentMock->shouldReceive('getAssetList')->once()->andThrow(new Exception('down'));

    $response = $this->getJson('/api/v1/ccpayment/assets');

    $response->assertStatus(500);
});

it('returns single asset detail', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->ccpaymentMock->shouldReceive('getAsset')->once()->with(1280)->andReturn(['coinId' => 1280, 'symbol' => 'USDT']);

    $response = $this->getJson('/api/v1/ccpayment/assets/1280');

    $response->assertStatus(200)->assertJson(['success' => true, 'data' => ['asset' => ['coinId' => 1280]]]);
});

it('returns 500 when single asset detail lookup throws', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->ccpaymentMock->shouldReceive('getAsset')->once()->andThrow(new Exception('down'));

    $response = $this->getJson('/api/v1/ccpayment/assets/1280');

    $response->assertStatus(500);
});
