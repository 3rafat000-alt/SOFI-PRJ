<?php

use App\Models\Integration;
use App\Services\CCPaymentService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->ccpaymentMock = Mockery::mock(CCPaymentService::class);
    $this->app->instance(CCPaymentService::class, $this->ccpaymentMock);

    // Default: verifyIp returns true, verifyWebhookSignature returns true
    $this->ccpaymentMock->shouldReceive('verifyWebhookIp')->andReturn(true)->byDefault();
    $this->ccpaymentMock->shouldReceive('verifyWebhookSignature')->andReturn(true)->byDefault();

    // Reset app env to testing (some tests change it to production)
    $this->app->instance('env', 'testing');
});

it('rejects deposit webhook with invalid IP', function () {
    $this->ccpaymentMock->shouldReceive('verifyWebhookIp')->andReturn(false);

    $response = $this->postJson('/webhooks/ccpayment/deposit', [
        'referenceId' => 'REF123',
        'status' => 'success',
        'amount' => '100.00',
    ]);

    $response->assertStatus(403);
    $response->assertJson(['success' => false]);
});

it('rejects deposit webhook with invalid signature', function () {
    $this->ccpaymentMock->shouldReceive('verifyWebhookSignature')->andReturn(false);

    $response = $this->postJson('/webhooks/ccpayment/deposit', ['test' => 'data'], [
        'Sign' => 'invalid',
        'Timestamp' => '1234567890',
    ]);

    $response->assertStatus(401);
    $response->assertJson(['success' => false]);
});

it('processes deposit webhook successfully', function () {
    $this->ccpaymentMock->shouldReceive('handleDepositWebhook')
        ->once()
        ->with(Mockery::on(function ($data) {
            return $data['referenceId'] === 'REF123';
        }));

    $response = $this->postJson('/webhooks/ccpayment/deposit', [
        'referenceId' => 'REF123',
        'status' => 'success',
        'amount' => '100.00',
    ], [
        'Sign' => 'valid-signature',
        'Timestamp' => '1234567890',
    ]);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);
});

it('confirms the CCPayment activation handshake without processing a deposit', function () {
    // type=ActivateWebhookURL must short-circuit before handleDepositWebhook is touched.
    $this->ccpaymentMock->shouldReceive('handleDepositWebhook')->never();

    $response = $this->postJson('/webhooks/ccpayment/deposit', [
        'type' => 'ActivateWebhookURL',
        'msg' => [],
    ], [
        'Sign' => 'valid-signature',
        'Timestamp' => '1234567890',
    ]);

    $response->assertStatus(200);
    $response->assertJson(['msg' => 'Success']);
});

it('confirms the CCPayment activation handshake on the withdraw endpoint', function () {
    $this->ccpaymentMock->shouldReceive('handleWithdrawWebhook')->never();

    $response = $this->postJson('/webhooks/ccpayment/withdraw', [
        'type' => 'ActivateWebhookURL',
        'msg' => [],
    ], [
        'Sign' => 'valid-signature',
        'Timestamp' => '1234567890',
    ]);

    $response->assertStatus(200);
    $response->assertJson(['msg' => 'Success']);
});

it('rejects an activation handshake with an invalid signature', function () {
    $this->ccpaymentMock->shouldReceive('verifyWebhookSignature')->andReturn(false);
    $this->ccpaymentMock->shouldReceive('handleDepositWebhook')->never();

    $response = $this->postJson('/webhooks/ccpayment/deposit', [
        'type' => 'ActivateWebhookURL',
        'msg' => [],
    ], [
        'Sign' => 'forged',
        'Timestamp' => '1234567890',
    ]);

    $response->assertStatus(401);
});

it('returns 500 when deposit webhook processing throws exception', function () {
    $this->ccpaymentMock->shouldReceive('handleDepositWebhook')
        ->andThrow(new \Exception('Processing failed'));

    $response = $this->postJson('/webhooks/ccpayment/deposit', [
        'referenceId' => 'REF123',
        'status' => 'success',
        'amount' => '100.00',
    ], [
        'Sign' => 'sig',
        'Timestamp' => '1234567890',
    ]);

    $response->assertStatus(500);
});

it('rejects withdrawal webhook with invalid IP', function () {
    $this->ccpaymentMock->shouldReceive('verifyWebhookIp')->andReturn(false);

    $response = $this->postJson('/webhooks/ccpayment/withdraw', [
        'orderId' => 'ORD123',
        'status' => 'success',
        'amount' => '50.00',
    ]);

    $response->assertStatus(403);
});

it('processes withdrawal webhook successfully', function () {
    $this->ccpaymentMock->shouldReceive('handleWithdrawWebhook')
        ->once()
        ->with(Mockery::on(function ($data) {
            return $data['orderId'] === 'ORD123';
        }));

    $response = $this->postJson('/webhooks/ccpayment/withdraw', [
        'orderId' => 'ORD123',
        'status' => 'success',
        'amount' => '50.00',
    ], [
        'Sign' => 'valid-sig',
        'Timestamp' => '1234567890',
    ]);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);
});

it('rejects test deposit in production environment', function () {
    touch(storage_path('installed'));
    app()->instance('env', 'production');
    $response = $this->postJson('/api/webhooks/ccpayment/test/deposit', [
        'referenceId' => 'REF_TEST',
        'status' => 'success',
        'amount' => '100',
    ]);

    $response->assertStatus(403);

    // Restore env for subsequent tests
    app()->instance('env', 'testing');
});

it('rejects test withdraw in production environment', function () {
    touch(storage_path('installed'));
    app()->instance('env', 'production');
    $response = $this->postJson('/api/webhooks/ccpayment/test/withdraw', [
        'orderId' => 'ORD_TEST',
        'status' => 'success',
        'amount' => '50',
    ]);

    $response->assertStatus(403);

    // Restore env for subsequent tests
    app()->instance('env', 'testing');
});

it('validates test deposit request fields', function () {
    $response = $this->postJson('/api/webhooks/ccpayment/test/deposit', [
        'status' => 'invalid_status',
        'amount' => 'not-a-number',
    ]);

    $response->assertStatus(422);
});

it('validates test withdraw request fields', function () {
    $response = $this->postJson('/api/webhooks/ccpayment/test/withdraw', [
        'status' => 'unknown',
    ]);

    $response->assertStatus(422);
});

it('rejects info endpoint in production', function () {
    touch(storage_path('installed'));
    app()->instance('env', 'production');
    $response = $this->getJson('/api/webhooks/ccpayment/info');

    $response->assertStatus(403);

    // Restore env for subsequent tests
    app()->instance('env', 'testing');
});

it('returns webhook info with ngrok instructions', function () {
    // Override env to testing — should still be blocked if not local/dev/testing
    // The code checks !in_array(['local', 'development', 'testing']) 
    // Actually app()->environment(['local', 'development', 'testing']) — testing IS in that list
    // So this endpoint should work in testing env
    $response = $this->getJson('/webhooks/ccpayment/info');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'webhooks' => ['deposit', 'withdraw'],
    ]);
});
