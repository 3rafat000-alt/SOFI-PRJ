<?php

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Webhook signature rejection — CCPaymentWebhookController + StripeIssuingWebhookController.
 *
 * Both webhook endpoints are unauthenticated (api.php:466-469, web.php:189-192) and
 * therefore the signature is the ONLY thing standing between an attacker and a forged
 * balance credit. These tests assert the controllers fail closed: a missing, empty, or
 * forged signature — and an unconfigured signing secret — never reaches the money path.
 *
 * Real signers:
 *   CCPayment: hash_hmac('sha256', appId.timestamp.body, appSecret), constant-time compared
 *              (CCPaymentService::verifyWebhookSignature, :492). Controller also requires
 *              both Sign + Timestamp headers (CCPaymentWebhookController::verifySignature, :89).
 *   Stripe:    Stripe\Webhook::constructEvent; returns false when secret is empty
 *              (StripeIssuingService::verifyWebhookSignature, :702-707).
 */

// ──────────────── CCPayment (web routes /webhooks/ccpayment/*) ────────────────

it('rejects a ccpayment deposit webhook with no signature headers', function () {
    // IP whitelist is open in testing (empty whitelist => allow), so the request
    // reaches the signature gate; absent Sign/Timestamp => 401.
    $this->postJson('/webhooks/ccpayment/deposit', [
        'referenceId' => 'ref-no-sig',
        'status' => 'success',
        'amount' => '100',
    ])->assertStatus(401)
      ->assertJsonPath('success', false);
});

it('rejects a ccpayment deposit webhook with a forged signature', function () {
    $this->postJson('/webhooks/ccpayment/deposit', [
        'referenceId' => 'ref-forged',
        'status' => 'success',
        'amount' => '100',
    ], [
        'Sign' => 'deadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeef',
        'Timestamp' => (string) time(),
    ])->assertStatus(401)
      ->assertJsonPath('success', false);
});

it('rejects a ccpayment deposit webhook missing only the timestamp header', function () {
    $this->postJson('/webhooks/ccpayment/deposit', [
        'referenceId' => 'ref-no-ts',
        'status' => 'success',
        'amount' => '100',
    ], [
        'Sign' => 'deadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeef',
    ])->assertStatus(401);
});

it('rejects a ccpayment withdraw webhook with no signature headers', function () {
    $this->postJson('/webhooks/ccpayment/withdraw', [
        'orderId' => 'ord-no-sig',
        'status' => 'success',
        'amount' => '50',
    ])->assertStatus(401)
      ->assertJsonPath('success', false);
});

it('does not credit any wallet when a ccpayment webhook signature is rejected', function () {
    $user = User::factory()->create();
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    $before = (float) $wallet->balance;

    $this->postJson('/webhooks/ccpayment/deposit', [
        'referenceId' => 'ref-attacker',
        'status' => 'success',
        'amount' => '999999',
        'wallet_id' => $wallet->id,
    ], [
        'Sign' => 'forged',
        'Timestamp' => (string) time(),
    ])->assertStatus(401);

    expect((float) $wallet->fresh()->balance)->toBe($before);
});

// ──────────────── Stripe Issuing (api route /api/webhooks/stripe/issuing) ────────────────

it('rejects a stripe issuing webhook with no signature header', function () {
    // No Stripe-Signature header => verifyWebhookSignature(false) => 401.
    $this->call(
        'POST',
        '/api/webhooks/stripe/issuing',
        [],
        [],
        [],
        ['CONTENT_TYPE' => 'application/json'],
        json_encode(['type' => 'issuing_authorization.request', 'data' => ['object' => []]])
    )->assertStatus(401);
});

it('rejects a stripe issuing webhook with a forged signature header', function () {
    $this->call(
        'POST',
        '/api/webhooks/stripe/issuing',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => 't=' . time() . ',v1=forged_signature_value',
        ],
        json_encode(['type' => 'issuing_authorization.request', 'data' => ['object' => []]])
    )->assertStatus(401);
});

it('rejects a stripe issuing webhook when the signing secret is unconfigured', function () {
    // StripeIssuingService::verifyWebhookSignature returns false on empty secret (:704).
    config(['services.stripe.issuing_webhook_secret' => '']);

    $this->call(
        'POST',
        '/api/webhooks/stripe/issuing',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => 't=' . time() . ',v1=anything',
        ],
        json_encode(['type' => 'issuing_card.updated', 'data' => ['object' => ['id' => 'ic_x']]])
    )->assertStatus(401);
});

// ──────────────── Accept-valid (positive complement) ────────────────
//
// The negatives above prove the gate fails CLOSED. These prove it also OPENS for a
// genuinely valid signature — otherwise a gate that 401s everything would "pass" the
// negative suite while being completely broken. For the two positive HMAC tests we use
// $this->call() with a self-built raw $body so the bytes we SIGN are byte-identical to
// the bytes transmitted (postJson re-encodes JSON and would desync the HMAC).
//
// NOTE on replay: CCPaymentService::verifyWebhookSignature (:492-496) has NO timestamp
// tolerance window, so a stale-timestamp "replay" is not rejectable on the CCPayment
// side and we deliberately do not assert one. Real replay protection lives only on the
// Stripe path (Stripe SDK 300s tolerance, asserted in the last test).

it('accepts a valid ccpayment deposit signature and credits the wallet', function () {
    // Use the env/config fallback branch (no Integration row exists in :memory:),
    // and force a fresh service so the constructor picks up these credentials.
    config([
        'services.ccpayment.app_id' => 'test_app',
        'services.ccpayment.app_secret' => 'test_secret',
    ]);
    app()->forgetInstance(\App\Services\CCPaymentService::class);

    $user = User::factory()->create();
    $wallet = $user->wallets()->where('currency', 'USD')->first();
    $wallet->update(['balance' => 0, 'available_balance' => 0]);
    $before = (float) $wallet->fresh()->balance;

    // handleDepositWebhook only credits when a matching PENDING transaction exists
    // (reference == referenceId, wallet_id == this wallet). Seed one explicitly.
    Transaction::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'currency' => 'USD',
        'reference' => 'ref-ok',
        'type' => TransactionType::DEPOSIT,
        'amount' => 100,
        'status' => TransactionStatus::PENDING,
    ]);

    // recordId is mandatory (CCPaymentService.php:402) or the handler returns early.
    $payload = [
        'recordId' => 'rec-ok',
        'referenceId' => 'ref-ok',
        'status' => 'success',
        'amount' => '100',
    ];
    $body = json_encode($payload);
    $timestamp = (string) (time() * 1000);
    $sign = hash_hmac('sha256', 'test_app' . $timestamp . $body, 'test_secret');

    $this->call(
        'POST',
        '/webhooks/ccpayment/deposit',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_SIGN' => $sign,
            'HTTP_TIMESTAMP' => $timestamp,
        ],
        $body
    )->assertStatus(200)
      ->assertJsonPath('success', true);

    // Valid signature reached the money path and credited exactly the payload amount.
    expect((float) $wallet->fresh()->balance)->toBe($before + 100.0);
});

it('accepts a valid ccpayment withdraw signature and completes the transaction', function () {
    config([
        'services.ccpayment.app_id' => 'test_app',
        'services.ccpayment.app_secret' => 'test_secret',
    ]);
    app()->forgetInstance(\App\Services\CCPaymentService::class);

    $user = User::factory()->create();
    $wallet = $user->wallets()->where('currency', 'USD')->first();

    // Withdraw handler looks up by orderId; on 'success' it only flips PENDING -> COMPLETED
    // (no credit; refund happens only on FAILED, :477-486).
    Transaction::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'currency' => 'USD',
        'reference' => 'ord-ok',
        'type' => TransactionType::WITHDRAWAL,
        'amount' => -50,
        'status' => TransactionStatus::PENDING,
    ]);

    $payload = [
        'orderId' => 'ord-ok',
        'status' => 'success',
        'amount' => '50',
    ];
    $body = json_encode($payload);
    $timestamp = (string) (time() * 1000);
    $sign = hash_hmac('sha256', 'test_app' . $timestamp . $body, 'test_secret');

    $this->call(
        'POST',
        '/webhooks/ccpayment/withdraw',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_SIGN' => $sign,
            'HTTP_TIMESTAMP' => $timestamp,
        ],
        $body
    )->assertStatus(200)
      ->assertJsonPath('success', true);

    expect(Transaction::where('reference', 'ord-ok')->first()->status)
        ->toBe(TransactionStatus::COMPLETED);
});

it('accepts a stripe issuing webhook with a valid current signature', function () {
    $secret = 'whsec_test_' . str_repeat('a', 24);
    config(['services.stripe.issuing_webhook_secret' => $secret]);
    // Leave services.stripe.secret unset so $this->stripe stays null; the chosen event
    // type ('issuing_card.created') only logs and never dereferences the Stripe client.
    config(['services.stripe.secret' => null]);
    app()->forgetInstance(\App\Services\StripeIssuingService::class);

    $payload = json_encode([
        'id' => 'evt_test',
        'type' => 'issuing_card.created',
        'data' => ['object' => ['id' => 'ic_x', 'last4' => '4242']],
    ]);
    $t = time();
    // Stripe header format: "t={ts},v1={hmac}" where hmac signs "{ts}.{payload}"
    // (vendor/stripe/stripe-php/lib/WebhookSignature.php computeSignature).
    $sig = 't=' . $t . ',v1=' . hash_hmac('sha256', $t . '.' . $payload, $secret);

    $this->call(
        'POST',
        '/api/webhooks/stripe/issuing',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $sig,
        ],
        $payload
    )->assertStatus(200)
      ->assertJsonPath('received', true);
});

it('rejects a stripe issuing webhook whose valid signature has a stale timestamp (replay)', function () {
    $secret = 'whsec_test_' . str_repeat('a', 24);
    config(['services.stripe.issuing_webhook_secret' => $secret]);
    config(['services.stripe.secret' => null]);
    app()->forgetInstance(\App\Services\StripeIssuingService::class);

    $payload = json_encode([
        'id' => 'evt_replay',
        'type' => 'issuing_card.created',
        'data' => ['object' => ['id' => 'ic_x', 'last4' => '4242']],
    ]);
    // Timestamp older than the SDK's 300s tolerance (Webhook.php DEFAULT_TOLERANCE = 300).
    // The HMAC is COMPUTED CORRECTLY for this (stale) timestamp, so the only thing that
    // rejects it is the freshness window — proving replay protection is real, not a
    // side effect of a bad signature.
    $t = time() - 400;
    $sig = 't=' . $t . ',v1=' . hash_hmac('sha256', $t . '.' . $payload, $secret);

    $this->call(
        'POST',
        '/api/webhooks/stripe/issuing',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $sig,
        ],
        $payload
    )->assertStatus(401);
});
