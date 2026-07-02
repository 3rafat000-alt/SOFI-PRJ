<?php

use App\Enums\CardStatus;
use App\Models\VirtualCard;
use App\Services\StripeIssuingService;

beforeEach(function () {
    $this->stripeMock = Mockery::mock(StripeIssuingService::class);
    $this->app->instance(StripeIssuingService::class, $this->stripeMock);
});

it('rejects webhook with invalid signature', function () {
    $this->stripeMock->shouldReceive('verifyWebhookSignature')
        ->once()
        ->andReturn(false);

    $response = $this->postJson('/api/webhooks/stripe/issuing', [
        'type' => 'issuing_authorization.request',
        'data' => ['object' => []],
    ]);

    $response->assertStatus(401);
    $response->assertJson(['error' => 'Invalid signature']);
});

it('handles authorization request — approved', function () {
    $this->stripeMock->shouldReceive('verifyWebhookSignature')->andReturn(true);
    $this->stripeMock->shouldReceive('handleAuthorizationRequest')
        ->once()
        ->andReturn(['approved' => true]);

    $payload = json_encode([
        'type' => 'issuing_authorization.request',
        'data' => ['object' => ['id' => 'auth_123', 'amount' => 5000]],
    ]);

    $response = $this->call('POST', '/api/webhooks/stripe/issuing', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => 'valid_sig',
        'CONTENT_TYPE' => 'application/json',
    ], $payload);

    $response->assertStatus(200);
    $response->assertJson(['approved' => true]);
});

it('handles authorization request — declined', function () {
    $this->stripeMock->shouldReceive('verifyWebhookSignature')->andReturn(true);
    $this->stripeMock->shouldReceive('handleAuthorizationRequest')
        ->once()
        ->andReturn(['approved' => false, 'reason' => 'insufficient_funds']);

    $payload = json_encode([
        'type' => 'issuing_authorization.request',
        'data' => ['object' => ['id' => 'auth_456', 'amount' => 50000]],
    ]);

    $response = $this->call('POST', '/api/webhooks/stripe/issuing', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => 'valid_sig',
        'CONTENT_TYPE' => 'application/json',
    ], $payload);

    $response->assertStatus(200);
    $response->assertJson(['approved' => false]);
});

it('handles authorization created with closed status', function () {
    $this->stripeMock->shouldReceive('verifyWebhookSignature')->andReturn(true);
    $this->stripeMock->shouldReceive('handleAuthorizationCapture')->once();

    $payload = json_encode([
        'type' => 'issuing_authorization.created',
        'data' => ['object' => ['id' => 'auth_789', 'status' => 'closed', 'amount' => 3000]],
    ]);

    $response = $this->call('POST', '/api/webhooks/stripe/issuing', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => 'sig',
        'CONTENT_TYPE' => 'application/json',
    ], $payload);

    $response->assertStatus(200);
    $response->assertJson(['received' => true]);
});

it('handles authorization created with pending status', function () {
    $this->stripeMock->shouldReceive('verifyWebhookSignature')->andReturn(true);
    $this->stripeMock->shouldNotReceive('handleAuthorizationCapture');

    $payload = json_encode([
        'type' => 'issuing_authorization.created',
        'data' => ['object' => ['id' => 'auth_101', 'status' => 'pending']],
    ]);

    $response = $this->call('POST', '/api/webhooks/stripe/issuing', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => 'sig',
        'CONTENT_TYPE' => 'application/json',
    ], $payload);

    $response->assertStatus(200);
});

it('handles authorization updated — closed', function () {
    $this->stripeMock->shouldReceive('verifyWebhookSignature')->andReturn(true);
    $this->stripeMock->shouldReceive('handleAuthorizationCapture')->once();

    $payload = json_encode([
        'type' => 'issuing_authorization.updated',
        'data' => ['object' => ['id' => 'auth_202', 'status' => 'closed']],
    ]);

    $response = $this->call('POST', '/api/webhooks/stripe/issuing', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => 'sig',
        'CONTENT_TYPE' => 'application/json',
    ], $payload);

    $response->assertStatus(200);
});

it('handles authorization updated — reversed', function () {
    $this->stripeMock->shouldReceive('verifyWebhookSignature')->andReturn(true);
    $this->stripeMock->shouldReceive('handleAuthorizationReversal')->once();

    $payload = json_encode([
        'type' => 'issuing_authorization.updated',
        'data' => ['object' => ['id' => 'auth_303', 'status' => 'reversed']],
    ]);

    $response = $this->call('POST', '/api/webhooks/stripe/issuing', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => 'sig',
        'CONTENT_TYPE' => 'application/json',
    ], $payload);

    $response->assertStatus(200);
});

it('handles card created event', function () {
    $this->stripeMock->shouldReceive('verifyWebhookSignature')->andReturn(true);

    $payload = json_encode([
        'type' => 'issuing_card.created',
        'data' => ['object' => ['id' => 'card_111', 'last4' => '1234']],
    ]);

    $response = $this->call('POST', '/api/webhooks/stripe/issuing', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => 'sig',
        'CONTENT_TYPE' => 'application/json',
    ], $payload);

    $response->assertStatus(200);
});

it('handles card updated — syncs local card status', function () {
    $this->stripeMock->shouldReceive('verifyWebhookSignature')->andReturn(true);

    $card = VirtualCard::factory()->create([
        'provider_card_id' => 'ic_999',
        'provider' => 'stripe',
        'status' => CardStatus::ACTIVE,
    ]);

    $payload = json_encode([
        'type' => 'issuing_card.updated',
        'data' => ['object' => ['id' => 'ic_999', 'status' => 'canceled']],
    ]);

    $response = $this->call('POST', '/api/webhooks/stripe/issuing', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => 'sig',
        'CONTENT_TYPE' => 'application/json',
    ], $payload);

    $response->assertStatus(200);

    $card->refresh();
    expect($card->status->value)->toBe(CardStatus::CANCELLED->value);
    expect($card->is_active)->toBeFalse();
});

it('handles card updated — maps inactive to frozen', function () {
    $this->stripeMock->shouldReceive('verifyWebhookSignature')->andReturn(true);

    $card = VirtualCard::factory()->create([
        'provider_card_id' => 'ic_888',
        'provider' => 'stripe',
        'status' => CardStatus::ACTIVE,
    ]);

    $payload = json_encode([
        'type' => 'issuing_card.updated',
        'data' => ['object' => ['id' => 'ic_888', 'status' => 'inactive']],
    ]);

    $this->call('POST', '/api/webhooks/stripe/issuing', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => 'sig',
        'CONTENT_TYPE' => 'application/json',
    ], $payload);

    $card->refresh();
    expect($card->status->value)->toBe(CardStatus::FROZEN->value);
});

it('handles cardholder created event', function () {
    $this->stripeMock->shouldReceive('verifyWebhookSignature')->andReturn(true);

    $payload = json_encode([
        'type' => 'issuing_cardholder.created',
        'data' => ['object' => ['id' => 'ch_555']],
    ]);

    $response = $this->call('POST', '/api/webhooks/stripe/issuing', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => 'sig',
        'CONTENT_TYPE' => 'application/json',
    ], $payload);

    $response->assertStatus(200);
});

it('handles dispute created event', function () {
    $this->stripeMock->shouldReceive('verifyWebhookSignature')->andReturn(true);

    $payload = json_encode([
        'type' => 'issuing_dispute.created',
        'data' => ['object' => ['id' => 'dp_777', 'amount' => 5000]],
    ]);

    $response = $this->call('POST', '/api/webhooks/stripe/issuing', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => 'sig',
        'CONTENT_TYPE' => 'application/json',
    ], $payload);

    $response->assertStatus(200);
});

it('returns success for unknown event types', function () {
    $this->stripeMock->shouldReceive('verifyWebhookSignature')->andReturn(true);

    $payload = json_encode([
        'type' => 'unknown.event.type',
        'data' => ['object' => []],
    ]);

    $response = $this->call('POST', '/api/webhooks/stripe/issuing', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => 'sig',
        'CONTENT_TYPE' => 'application/json',
    ], $payload);

    $response->assertStatus(200);
    $response->assertJson(['received' => true]);
});

it('maps decline reasons correctly', function () {
    // Testing via authorization request which uses mapDeclineReason internally
    $this->stripeMock->shouldReceive('verifyWebhookSignature')->andReturn(true);

    $reasons = [
        'insufficient_funds' => 'insufficient_funds',
        'card_inactive' => 'card_inactive',
        'card_frozen' => 'card_inactive',
        'spending_limit_exceeded' => 'spending_controls',
        'merchant_blocked' => 'webhook_declined',
        'card_not_found' => 'card_inactive',
        'system_error' => 'webhook_error',
    ];

    foreach ($reasons as $internalReason => $expectedStripeReason) {
        $this->stripeMock->shouldReceive('handleAuthorizationRequest')
            ->once()
            ->andReturn(['approved' => false, 'reason' => $internalReason]);

        $payload = json_encode([
            'type' => 'issuing_authorization.request',
            'data' => ['object' => ['id' => 'auth_' . $internalReason]],
        ]);

        $response = $this->call('POST', '/api/webhooks/stripe/issuing', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => 'sig',
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('decline_reason', $expectedStripeReason);
    }
});
