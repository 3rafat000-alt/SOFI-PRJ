<?php

use App\Models\Integration;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\CCPaymentService;
use Illuminate\Support\Facades\Http;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

/**
 * Activates the ccpayment integration with deterministic test creds so HMAC
 * signatures / Http::fake assertions are reproducible.
 */
function activateCcpayment(array $settings = []): void
{
    Integration::withTrashed()->updateOrCreate(
        ['key' => 'ccpayment'],
        [
            'name' => 'CCPayment',
            'name_ar' => 'سي سي بايمنت',
            'is_active' => true,
            'is_visible' => true,
            'category' => 'payment',
            'credentials' => ['app_id' => 'test_app', 'app_secret' => 'test_secret'],
            'settings' => array_merge([
                'ip_whitelist' => '127.0.0.1,10.0.0.0/24',
                'debug_mode' => false,
            ], $settings),
            'deleted_at' => null,
        ]
    );
}

it('throws a business-error RuntimeException when the API responds with a non-10000 code', function () {
    activateCcpayment();
    Http::fake(['*' => Http::response(['code' => 13000, 'msg' => 'unsupported coin'], 200)]);

    $service = new CCPaymentService();

    expect(fn() => $service->getAssetList())
        ->toThrow(\RuntimeException::class, 'خطأ CCPayment: unsupported coin');
});

it('throws when the HTTP response itself fails', function () {
    activateCcpayment();
    Http::fake(['*' => Http::response('', 500)]);

    $service = new CCPaymentService();

    expect(fn() => $service->getAssetList())->toThrow(\RuntimeException::class);
});

it('validates required params before calling createDepositAddress', function () {
    activateCcpayment();
    $service = new CCPaymentService();

    expect(fn() => $service->createDepositAddress(['orderId' => 'x']))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('validates required params before calling withdrawToNetwork', function () {
    activateCcpayment();
    $service = new CCPaymentService();

    expect(fn() => $service->withdrawToNetwork(['orderId' => 'ord1']))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('validates required params before calling withdrawToCwallet', function () {
    activateCcpayment();
    $service = new CCPaymentService();

    expect(fn() => $service->withdrawToCwallet(['orderId' => 'ord1']))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('creates a deposit address with the deterministic reference and route notify url', function () {
    activateCcpayment();
    Http::fake(['*' => Http::response([
        'code' => 10000, 'msg' => 'success',
        'data' => ['address' => 'T1234abc', 'memo' => null],
    ], 200)]);

    $user = User::factory()->create();
    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'currency' => 'USDT']);

    $service = new CCPaymentService();
    $result = $service->createWalletDeposit($wallet, 'TRC20');

    expect($result['address'])->toBe('T1234abc');
    expect($result['reference_id'])->toBe("sarva_{$wallet->user_id}_{$wallet->id}_TRX");
});

it('gets or creates a deposit address passing through chain and notify url', function () {
    activateCcpayment();
    Http::fake(['*' => Http::response(['code' => 10000, 'msg' => 'ok', 'data' => ['address' => 'addr1']], 200)]);

    $service = new CCPaymentService();
    $result = $service->getOrCreateDepositAddress('ref-1', 'TRX', 'https://example.com/notify');

    expect($result['address'])->toBe('addr1');
    Http::assertSent(function ($request) {
        $body = json_decode($request->body(), true);
        return $body['referenceId'] === 'ref-1'
            && $body['chain'] === 'TRX'
            && $body['notifyUrl'] === 'https://example.com/notify';
    });
});

it('gets a single deposit record', function () {
    activateCcpayment();
    Http::fake(['*' => Http::response(['code' => 10000, 'msg' => 'ok', 'data' => ['record' => ['recordId' => 'r1', 'amount' => '5.00']]], 200)]);

    $service = new CCPaymentService();
    $record = $service->getDepositRecord('r1');

    expect($record['recordId'])->toBe('r1');
});

it('filters deposit record list params to the allowed keys', function () {
    activateCcpayment();
    Http::fake(['*' => Http::response(['code' => 10000, 'msg' => 'ok', 'data' => []], 200)]);

    $service = new CCPaymentService();
    $service->getDepositRecords(['chain' => 'TRX', 'not_allowed' => 'x', 'limit' => 10]);

    Http::assertSent(function ($request) {
        $body = json_decode($request->body(), true);
        return $body === ['chain' => 'TRX', 'limit' => 10];
    });
});

it('gets a withdraw fee estimate', function () {
    activateCcpayment();
    Http::fake(['*' => Http::response(['code' => 10000, 'msg' => 'ok', 'data' => ['fee' => ['amount' => '1.5']]], 200)]);

    $service = new CCPaymentService();
    $fee = $service->getWithdrawFee(1280, 'TRX');

    expect($fee['amount'])->toBe('1.5');
});

it('processes a wallet withdrawal, records a pending transaction with fee metadata', function () {
    activateCcpayment();
    Http::fake([
        '*getWithdrawFee*' => Http::response(['code' => 10000, 'msg' => 'ok', 'data' => ['fee' => ['amount' => '1.00']]], 200),
        '*applyAppWithdrawToNetwork*' => Http::response(['code' => 10000, 'msg' => 'ok', 'data' => ['recordId' => 'rec-w1']], 200),
    ]);

    $user = User::factory()->create();
    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'currency' => 'USDT']);

    $service = new CCPaymentService();
    $result = $service->processWalletWithdraw($wallet, 'Taddr123', '20.00', 'TRC20');

    expect($result['record_id'])->toBe('rec-w1');

    $tx = Transaction::where('reference', $result['order_id'])->first();
    expect($tx)->not->toBeNull();
    expect($tx->status)->toBe(\App\Enums\TransactionStatus::PENDING);
    expect($tx->metadata['to_address'])->toBe('Taddr123');
    expect($tx->metadata['fee']['amount'])->toBe('1.00');
});

it('gets a single withdraw record by orderId or recordId', function () {
    activateCcpayment();
    Http::fake(['*' => Http::response(['code' => 10000, 'msg' => 'ok', 'data' => ['record' => ['orderId' => 'o1']]], 200)]);

    $service = new CCPaymentService();
    $record = $service->getWithdrawRecord('o1', null);

    expect($record['orderId'])->toBe('o1');
});

it('filters withdraw record list params to the allowed keys', function () {
    activateCcpayment();
    Http::fake(['*' => Http::response(['code' => 10000, 'msg' => 'ok', 'data' => []], 200)]);

    $service = new CCPaymentService();
    $service->getWithdrawRecords(['chain' => 'TRX', 'bogus' => 'y']);

    Http::assertSent(fn($r) => json_decode($r->body(), true) === ['chain' => 'TRX']);
});

it('gets asset list and single asset', function () {
    activateCcpayment();
    Http::fake(['*' => Http::response(['code' => 10000, 'msg' => 'ok', 'data' => ['asset' => ['coinId' => 1280]]], 200)]);

    $service = new CCPaymentService();
    $asset = $service->getAsset(1280);

    expect($asset['coinId'])->toBe(1280);
});

it('processes a withdraw webhook success: marks completed and does not refund', function () {
    activateCcpayment();
    $user = User::factory()->create();
    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'currency' => 'USDT']);
    $tx = Transaction::create([
        'user_id' => $user->id, 'wallet_id' => $wallet->id,
        'type' => \App\Enums\TransactionType::WITHDRAWAL, 'category' => \App\Enums\TransactionCategory::CRYPTO,
        'status' => \App\Enums\TransactionStatus::PENDING, 'amount' => 10, 'currency' => 'USDT',
        'reference' => 'ord-success-1', 'title' => 'سحب',
    ]);
    $before = (float) $wallet->balance;

    $service = new CCPaymentService();
    $service->handleWithdrawWebhook(['orderId' => 'ord-success-1', 'status' => 'success', 'txId' => 'tx1']);

    $tx->refresh();
    $wallet->refresh();
    expect($tx->status)->toBe(\App\Enums\TransactionStatus::COMPLETED);
    expect((float) $wallet->balance)->toBe($before); // unchanged, no refund on success
});

it('processes a withdraw webhook failure: marks failed and refunds the wallet', function () {
    activateCcpayment();
    $user = User::factory()->create();
    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'currency' => 'USDT', 'balance' => 100]);
    $tx = Transaction::create([
        'user_id' => $user->id, 'wallet_id' => $wallet->id,
        'type' => \App\Enums\TransactionType::WITHDRAWAL, 'category' => \App\Enums\TransactionCategory::CRYPTO,
        'status' => \App\Enums\TransactionStatus::PENDING, 'amount' => 15, 'currency' => 'USDT',
        'reference' => 'ord-fail-1', 'title' => 'سحب',
    ]);

    $service = new CCPaymentService();
    $service->handleWithdrawWebhook(['orderId' => 'ord-fail-1', 'status' => 'failed']);

    $tx->refresh();
    $wallet->refresh();
    expect($tx->status)->toBe(\App\Enums\TransactionStatus::FAILED);
    expect((float) $wallet->balance)->toBe(115.0);
});

it('is idempotent on a repeated withdraw webhook status', function () {
    activateCcpayment();
    $user = User::factory()->create();
    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'currency' => 'USDT', 'balance' => 100]);
    Transaction::create([
        'user_id' => $user->id, 'wallet_id' => $wallet->id,
        'type' => \App\Enums\TransactionType::WITHDRAWAL, 'category' => \App\Enums\TransactionCategory::CRYPTO,
        'status' => \App\Enums\TransactionStatus::FAILED, 'amount' => 15, 'currency' => 'USDT',
        'reference' => 'ord-fail-2', 'title' => 'سحب',
    ]);

    $service = new CCPaymentService();
    $service->handleWithdrawWebhook(['orderId' => 'ord-fail-2', 'status' => 'failed']);

    $wallet->refresh();
    expect((float) $wallet->balance)->toBe(100.0); // no double refund
});

it('withdraw webhook is a no-op when transaction not found or orderId missing', function () {
    activateCcpayment();
    $service = new CCPaymentService();

    // Missing orderId entirely
    $service->handleWithdrawWebhook(['status' => 'success']);

    // Unknown orderId
    $service->handleWithdrawWebhook(['orderId' => 'does-not-exist', 'status' => 'success']);

    expect(Transaction::count())->toBe(0);
});

it('verifyWebhookSignature accepts a correctly-signed body and rejects a forged one', function () {
    activateCcpayment();
    $service = new CCPaymentService();

    $body = '{"a":1}';
    $timestamp = (string) (time() * 1000);
    $validSign = hash_hmac('sha256', 'test_app' . $timestamp . $body, 'test_secret');

    expect($service->verifyWebhookSignature($body, $validSign, $timestamp))->toBeTrue();
    expect($service->verifyWebhookSignature($body, 'forged', $timestamp))->toBeFalse();
});

it('verifyWebhookSignature fails closed when secret/sign/timestamp missing', function () {
    Integration::where('key', 'ccpayment')->delete();
    config(['services.ccpayment.app_id' => '', 'services.ccpayment.app_secret' => '']);

    $service = new CCPaymentService();

    expect($service->verifyWebhookSignature('{}', 'sign', 'ts'))->toBeFalse();
});

it('verifyWebhookIp allows all when whitelist empty or debug mode on', function () {
    activateCcpayment(['ip_whitelist' => '', 'debug_mode' => true]);
    $service = new CCPaymentService();

    expect($service->verifyWebhookIp('1.2.3.4'))->toBeTrue();
});

it('verifyWebhookIp allows an exact-match IP and rejects an unlisted one', function () {
    activateCcpayment(['ip_whitelist' => '203.0.113.5', 'debug_mode' => false]);
    $service = new CCPaymentService();

    expect($service->verifyWebhookIp('203.0.113.5'))->toBeTrue();
    expect($service->verifyWebhookIp('8.8.8.8'))->toBeFalse();
});

it('verifyWebhookIp allows an IP inside a whitelisted CIDR range', function () {
    activateCcpayment(['ip_whitelist' => '10.0.0.0/24', 'debug_mode' => false]);
    $service = new CCPaymentService();

    expect($service->verifyWebhookIp('10.0.0.55'))->toBeTrue();
    expect($service->verifyWebhookIp('10.0.1.55'))->toBeFalse();
});

it('generates a signed test deposit webhook payload', function () {
    activateCcpayment();
    $service = new CCPaymentService();

    $result = $service->generateTestWebhookPayload('deposit', ['amount' => '42.00']);

    expect($result['payload']['amount'])->toBe('42.00');
    expect($result['headers'])->toHaveKeys(['Sign', 'Timestamp', 'Appid']);

    $body = json_encode($result['payload']);
    $expectedSign = hash_hmac('sha256', 'test_app' . $result['headers']['Timestamp'] . $body, 'test_secret');
    expect($result['headers']['Sign'])->toBe($expectedSign);
});

it('generates a signed test withdraw webhook payload', function () {
    activateCcpayment();
    $service = new CCPaymentService();

    $result = $service->generateTestWebhookPayload('withdraw');

    expect($result['payload'])->toHaveKey('orderId');
    expect($result['payload']['status'])->toBe('success');
});

it('resolves deposit owner from the deterministic sarva reference format', function () {
    activateCcpayment();
    $user = User::factory()->create();
    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'currency' => 'USDT']);
    $referenceId = "sarva_{$user->id}_{$wallet->id}_TRX";

    Http::fake(['*' => Http::response(['code' => 10000, 'msg' => 'ok', 'data' => ['record' => ['amount' => '30.00']]], 200)]);

    $service = new CCPaymentService();
    $service->handleDepositWebhook([
        'type' => 'DirectDeposit',
        'msg' => [
            'recordId' => 'rec-owner-1',
            'referenceId' => $referenceId,
            'status' => 'Success',
        ],
    ]);

    $tx = Transaction::where('metadata->ccpayment_record_id', 'rec-owner-1')->first();
    expect($tx)->not->toBeNull();
    expect($tx->user_id)->toBe($user->id);
    expect($tx->wallet_id)->toBe($wallet->id);
});

it('drops a deposit webhook when the owner cannot be resolved', function () {
    activateCcpayment();
    Http::fake(['*' => Http::response(['code' => 10000, 'msg' => 'ok', 'data' => ['record' => ['amount' => '30.00']]], 200)]);

    $service = new CCPaymentService();
    $service->handleDepositWebhook([
        'msg' => [
            'recordId' => 'rec-unresolvable',
            'referenceId' => 'garbage-unparseable-ref',
            'status' => 'Success',
        ],
    ]);

    expect(Transaction::count())->toBe(0);
});

it('drops a deposit webhook missing recordId or referenceId', function () {
    activateCcpayment();

    $service = new CCPaymentService();
    $service->handleDepositWebhook(['msg' => ['status' => 'Success']]);

    expect(Transaction::count())->toBe(0);
});
