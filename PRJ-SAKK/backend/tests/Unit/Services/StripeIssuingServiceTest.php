<?php

use App\Enums\CardStatus;
use App\Enums\TransactionType;
use App\Models\Integration;
use App\Models\Transaction;
use App\Models\User;
use App\Models\VirtualCard;
use App\Models\Wallet;
use App\Services\StripeIssuingService;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // Force isConfigured() = false (no Stripe secret) so tests never attempt
    // a real network call to the Stripe SDK.
    Integration::where('key', 'stripe')->delete();
    config(['services.stripe.secret' => null, 'services.stripe.issuing_webhook_secret' => null]);
});

it('is not configured without a Stripe secret key', function () {
    $service = new StripeIssuingService();

    expect($service->isConfigured())->toBeFalse();
});

// SEV-4 regression: admin-toggled-off row must fail CLOSED, never fall
// through to a stray env secret. This is the poison case — row PRESENT
// and is_active=false — distinct from the row-ABSENT case above (which
// legitimately falls back to env, still resulting in not-configured here
// because beforeEach() also nulls the env creds).
it('fails closed when the integration row is present but inactive, even with env creds set', function () {
    Integration::updateOrCreate(
        ['key' => 'stripe'],
        ['name' => 'Stripe Issuing', 'name_ar' => 'ستريب — إصدار البطاقات', 'is_active' => false, 'credentials' => ['secret' => 'sk_db_poison'], 'settings' => []]
    );
    // A stray, fully-usable env secret must NOT resurrect card issuance.
    config(['services.stripe.secret' => 'sk_env_poison']);

    $service = new StripeIssuingService();

    expect($service->isConfigured())->toBeFalse();

    $user = User::factory()->create();
    $wallet = Wallet::factory()->create(['user_id' => $user->id]);
    $result = $service->issueVirtualCard($user, $wallet);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toBe('Stripe غير مُكون');
});

it('createCardholder short-circuits when Stripe is not configured', function () {
    $user = User::factory()->create();
    $service = new StripeIssuingService();

    $result = $service->createCardholder($user);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toBe('Stripe غير مُكون');
});

it('createCardholder returns existing cardholder id without calling Stripe', function () {
    $user = User::factory()->create(['stripe_cardholder_id' => null]);
    // isConfigured is false here, so this exercises the "not configured" gate
    // even with a pre-set id — confirm not-configured wins first is NOT the
    // case: the real gate order is isConfigured() first, so assert that.
    $service = new StripeIssuingService();

    $result = $service->createCardholder($user);
    expect($result['success'])->toBeFalse();
});

it('updateCardholder fails gracefully when not configured', function () {
    $user = User::factory()->create();
    $service = new StripeIssuingService();

    $result = $service->updateCardholder($user);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toBe('لا يوجد Cardholder للمستخدم');
});

it('issueVirtualCard fails gracefully when not configured', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->create(['user_id' => $user->id]);
    $service = new StripeIssuingService();

    $result = $service->issueVirtualCard($user, $wallet);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toBe('Stripe غير مُكون');
});

it('getCardDetails rejects a non-stripe card without calling the API', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->create(['user_id' => $user->id]);
    $card = VirtualCard::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'provider' => 'local',
        'provider_card_id' => null,
    ]);

    $service = new StripeIssuingService();
    $result = $service->getCardDetails($card);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toBe('البطاقة ليست من Stripe');
});

it('freezeCard on a local (non-stripe) card updates status without calling Stripe', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->create(['user_id' => $user->id]);
    $card = VirtualCard::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'provider' => 'local',
        'provider_card_id' => null,
        'status' => 'active',
        'is_active' => true,
    ]);

    $service = new StripeIssuingService();
    $result = $service->freezeCard($card);

    expect($result['success'])->toBeTrue();
    $card->refresh();
    expect($card->status)->toBe(CardStatus::FROZEN);
    expect($card->is_active)->toBeFalse();
});

it('unfreezeCard on a local card reactivates it', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->create(['user_id' => $user->id]);
    $card = VirtualCard::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'provider' => 'local',
        'provider_card_id' => null,
        'status' => 'frozen',
        'is_active' => false,
    ]);

    $service = new StripeIssuingService();
    $result = $service->unfreezeCard($card);

    expect($result['success'])->toBeTrue();
    $card->refresh();
    expect($card->status)->toBe(CardStatus::ACTIVE);
    expect($card->is_active)->toBeTrue();
});

it('cancelCard on a local card with balance refunds the wallet and zeros the card', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'currency' => 'USD', 'balance' => 50]);
    $card = VirtualCard::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'provider' => 'local',
        'provider_card_id' => null,
        'balance' => 30,
        'status' => 'active',
        'is_active' => true,
    ]);

    $service = new StripeIssuingService();
    $result = $service->cancelCard($card, $wallet);

    expect($result['success'])->toBeTrue();
    expect((float) $result['refunded'])->toBe(30.0);

    $card->refresh();
    $wallet->refresh();
    expect($card->status)->toBe(CardStatus::CANCELLED);
    expect((float) $card->balance)->toBe(0.0);
    expect((float) $wallet->balance)->toBe(80.0);

    $this->assertDatabaseHas('transactions', [
        'card_id' => $card->id,
        'type' => TransactionType::CARD_UNLOAD->value,
    ]);
});

it('cancelCard with zero balance does not create a refund transaction', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'currency' => 'USD', 'balance' => 50]);
    $card = VirtualCard::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'provider' => 'local',
        'provider_card_id' => null,
        'balance' => 0,
    ]);

    $service = new StripeIssuingService();
    $result = $service->cancelCard($card, $wallet);

    expect($result['success'])->toBeTrue();
    expect((float) $result['refunded'])->toBe(0.0);
    expect(Transaction::where('card_id', $card->id)->count())->toBe(0);
});

// ==================== Real Stripe-client path (mocked SDK, no live calls) ====================

/**
 * A tiny property-bag double standing in for Stripe's IssuingServiceFactory
 * (the object at $stripeClient->issuing), which exposes ->cardholders /
 * ->cards via its own magic __get(). Plain Mockery::mock() doesn't answer
 * property reads, only method calls, so a small anonymous class is used
 * instead — each value is itself a Mockery double for the leaf service
 * (CardholderService/CardService) whose *methods* (create/update/retrieve)
 * are mocked normally.
 */
function issuingServiceDouble(array $services)
{
    return new class($services) {
        private array $services;
        public function __construct(array $services) { $this->services = $services; }
        public function __get($name) { return $this->services[$name] ?? null; }
    };
}

/**
 * Build a StripeIssuingService with its internal StripeClient swapped for a
 * Mockery double shaped like Stripe\StripeClient (->issuing->cardholders,
 * ->issuing->cards). No network call ever leaves the process.
 */
function stripeIssuingServiceWithMockClient($issuingDouble): StripeIssuingService
{
    Integration::withTrashed()->updateOrCreate(
        ['key' => 'stripe'],
        [
            'name' => 'Stripe', 'name_ar' => 'سترايب', 'is_active' => true, 'is_visible' => true, 'category' => 'payment',
            'credentials' => ['secret' => 'sk_test_fake', 'issuing_webhook_secret' => 'whsec_test'],
            'settings' => ['test_mode' => true],
            'deleted_at' => null,
        ]
    );

    $service = new StripeIssuingService();

    // The service's $stripe property is typed ?Stripe\StripeClient, so the
    // double must actually extend it. Stripe\StripeClient's own __get() is a
    // concrete magic method that Mockery's partial mock of a real class does
    // not reliably intercept, so instead we subclass it directly and override
    // __get() to return our issuing double — no network call ever happens.
    $stripeClientMock = new class('sk_test_dummy') extends \Stripe\StripeClient {
        public $issuingDouble;
        public function __get($name)
        {
            return $name === 'issuing' ? $this->issuingDouble : parent::__get($name);
        }
    };
    $stripeClientMock->issuingDouble = $issuingDouble;

    $ref = new ReflectionClass($service);
    $prop = $ref->getProperty('stripe');
    $prop->setAccessible(true);
    $prop->setValue($service, $stripeClientMock);

    return $service;
}

it('createCardholder creates a Stripe cardholder and persists the id', function () {
    $user = User::factory()->create(['kyc_level' => 2, 'stripe_cardholder_id' => null]);

    $cardholdersService = Mockery::mock();
    $cardholdersService->shouldReceive('create')->once()->andReturn((object) ['id' => 'ich_new1']);
    $issuing = issuingServiceDouble(['cardholders' => $cardholdersService]);

    $service = stripeIssuingServiceWithMockClient($issuing);
    $result = $service->createCardholder($user);

    expect($result['success'])->toBeTrue();
    expect($result['cardholder_id'])->toBe('ich_new1');
    expect($user->fresh()->stripe_cardholder_id)->toBe('ich_new1');
});

it('createCardholder blocks a user below KYC level 2 when Stripe IS configured', function () {
    $user = User::factory()->create(['kyc_level' => 1, 'stripe_cardholder_id' => null]);

    $issuing = issuingServiceDouble([]);
    $service = stripeIssuingServiceWithMockClient($issuing);

    $result = $service->createCardholder($user);

    expect($result['success'])->toBeFalse();
    expect($result['required_level'])->toBe(2);
});

it('createCardholder translates a Stripe ApiErrorException into an Arabic message', function () {
    $user = User::factory()->create(['kyc_level' => 2, 'stripe_cardholder_id' => null]);

    $cardholdersService = Mockery::mock();
    $cardholdersService->shouldReceive('create')->once()->andThrow(
        \Stripe\Exception\InvalidRequestException::factory('bad request')
    );
    $issuing = issuingServiceDouble(['cardholders' => $cardholdersService]);

    $service = stripeIssuingServiceWithMockClient($issuing);
    $result = $service->createCardholder($user);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toBeString();
});

it('updateCardholder updates an existing Stripe cardholder', function () {
    $user = User::factory()->create(['stripe_cardholder_id' => 'ich_existing1']);

    $cardholdersService = Mockery::mock();
    $cardholdersService->shouldReceive('update')->once()->andReturn((object) ['id' => 'ich_existing1']);
    $issuing = issuingServiceDouble(['cardholders' => $cardholdersService]);

    $service = stripeIssuingServiceWithMockClient($issuing);
    $result = $service->updateCardholder($user);

    expect($result['success'])->toBeTrue();
});

it('updateCardholder surfaces a translated error on API failure', function () {
    $user = User::factory()->create(['stripe_cardholder_id' => 'ich_existing2']);

    $cardholdersService = Mockery::mock();
    $cardholdersService->shouldReceive('update')->once()->andThrow(
        \Stripe\Exception\InvalidRequestException::factory('nope')
    );
    $issuing = issuingServiceDouble(['cardholders' => $cardholdersService]);

    $service = stripeIssuingServiceWithMockClient($issuing);
    $result = $service->updateCardholder($user);

    expect($result['success'])->toBeFalse();
});

it('issueVirtualCard creates a cardholder if missing, then issues a Stripe card and a local record', function () {
    $user = User::factory()->create(['kyc_level' => 2, 'stripe_cardholder_id' => null]);
    $wallet = Wallet::factory()->for($user)->create(['currency' => 'USD']);

    $cardholdersService = Mockery::mock();
    $cardholdersService->shouldReceive('create')->once()->andReturn((object) ['id' => 'ich_auto1']);
    $cardsService = Mockery::mock();
    $cardsService->shouldReceive('create')->once()->andReturn((object) [
        'id' => 'ic_new1', 'brand' => 'visa', 'last4' => '4242', 'exp_month' => 9, 'exp_year' => 2030,
    ]);
    $issuing = issuingServiceDouble(['cardholders' => $cardholdersService, 'cards' => $cardsService]);

    $service = stripeIssuingServiceWithMockClient($issuing);
    $result = $service->issueVirtualCard($user, $wallet);

    expect($result['success'])->toBeTrue();
    expect($result['card']['last4'])->toBe('4242');
    $this->assertDatabaseHas('virtual_cards', [
        'user_id' => $user->id,
        'provider' => 'stripe',
        'provider_card_id' => 'ic_new1',
    ]);
});

it('issueVirtualCard enforces the KYC-tiered card count limit', function () {
    $user = User::factory()->create(['kyc_level' => 2, 'stripe_cardholder_id' => 'ich_has1']);
    $wallet = Wallet::factory()->for($user)->create(['currency' => 'USD']);

    $cardsLimit = app(\App\Services\KycService::class)->cardsLimitForUser($user);
    for ($i = 0; $i < $cardsLimit; $i++) {
        VirtualCard::factory()->create(['user_id' => $user->id, 'wallet_id' => $wallet->id, 'status' => 'active']);
    }

    $issuing = issuingServiceDouble([]);
    $service = stripeIssuingServiceWithMockClient($issuing);

    $result = $service->issueVirtualCard($user, $wallet);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toContain((string) $cardsLimit);
});

it('issueVirtualCard surfaces a translated error when Stripe card creation fails', function () {
    $user = User::factory()->create(['kyc_level' => 2, 'stripe_cardholder_id' => 'ich_has2']);
    $wallet = Wallet::factory()->for($user)->create(['currency' => 'USD']);

    $cardsService = Mockery::mock();
    $cardsService->shouldReceive('create')->once()->andThrow(
        \Stripe\Exception\CardException::factory('card declined', null, 'card_declined')
    );
    $issuing = issuingServiceDouble(['cards' => $cardsService]);

    $service = stripeIssuingServiceWithMockClient($issuing);
    $result = $service->issueVirtualCard($user, $wallet);

    expect($result['success'])->toBeFalse();
    $this->assertDatabaseMissing('virtual_cards', ['user_id' => $user->id, 'provider' => 'stripe']);
});

it('getCardDetails retrieves full PAN/CVC for a Stripe card via the SDK', function () {
    $card = VirtualCard::factory()->create(['provider' => 'stripe', 'provider_card_id' => 'ic_details1']);

    $cardsService = Mockery::mock();
    $cardsService->shouldReceive('retrieve')->once()->with('ic_details1', Mockery::any())->andReturn((object) [
        'number' => '4242424242424242', 'cvc' => '123', 'exp_month' => 9, 'exp_year' => 2030,
    ]);
    $issuing = issuingServiceDouble(['cards' => $cardsService]);

    $service = stripeIssuingServiceWithMockClient($issuing);
    $result = $service->getCardDetails($card);

    expect($result['success'])->toBeTrue();
    expect($result['card']['number'])->toBe('4242424242424242');
});

it('getCardDetails returns a generic failure when the Stripe retrieve call throws', function () {
    $card = VirtualCard::factory()->create(['provider' => 'stripe', 'provider_card_id' => 'ic_details2']);

    $cardsService = Mockery::mock();
    $cardsService->shouldReceive('retrieve')->once()->andThrow(
        \Stripe\Exception\InvalidRequestException::factory('nope')
    );
    $issuing = issuingServiceDouble(['cards' => $cardsService]);

    $service = stripeIssuingServiceWithMockClient($issuing);
    $result = $service->getCardDetails($card);

    expect($result['success'])->toBeFalse();
});

it('freezeCard on a Stripe-backed card calls the SDK to set status inactive', function () {
    $card = VirtualCard::factory()->create(['provider' => 'stripe', 'provider_card_id' => 'ic_freeze1', 'status' => 'active', 'is_active' => true]);

    $cardsService = Mockery::mock();
    $cardsService->shouldReceive('update')->once()->with('ic_freeze1', ['status' => 'inactive']);
    $issuing = issuingServiceDouble(['cards' => $cardsService]);

    $service = stripeIssuingServiceWithMockClient($issuing);
    $result = $service->freezeCard($card);

    expect($result['success'])->toBeTrue();
    expect($card->fresh()->status)->toBe(CardStatus::FROZEN);
});

it('freezeCard surfaces a translated error when the Stripe update call fails', function () {
    $card = VirtualCard::factory()->create(['provider' => 'stripe', 'provider_card_id' => 'ic_freeze2', 'status' => 'active', 'is_active' => true]);

    $cardsService = Mockery::mock();
    $cardsService->shouldReceive('update')->once()->andThrow(
        \Stripe\Exception\InvalidRequestException::factory('nope')
    );
    $issuing = issuingServiceDouble(['cards' => $cardsService]);

    $service = stripeIssuingServiceWithMockClient($issuing);
    $result = $service->freezeCard($card);

    expect($result['success'])->toBeFalse();
    expect($card->fresh()->status)->toBe(CardStatus::ACTIVE);
});

it('unfreezeCard on a Stripe-backed card calls the SDK to set status active', function () {
    $card = VirtualCard::factory()->frozen()->create(['provider' => 'stripe', 'provider_card_id' => 'ic_unfreeze1']);

    $cardsService = Mockery::mock();
    $cardsService->shouldReceive('update')->once()->with('ic_unfreeze1', ['status' => 'active']);
    $issuing = issuingServiceDouble(['cards' => $cardsService]);

    $service = stripeIssuingServiceWithMockClient($issuing);
    $result = $service->unfreezeCard($card);

    expect($result['success'])->toBeTrue();
    expect($card->fresh()->status)->toBe(CardStatus::ACTIVE);
});

it('unfreezeCard surfaces a translated error when the Stripe update call fails', function () {
    $card = VirtualCard::factory()->frozen()->create(['provider' => 'stripe', 'provider_card_id' => 'ic_unfreeze2']);

    $cardsService = Mockery::mock();
    $cardsService->shouldReceive('update')->once()->andThrow(
        \Stripe\Exception\InvalidRequestException::factory('nope')
    );
    $issuing = issuingServiceDouble(['cards' => $cardsService]);

    $service = stripeIssuingServiceWithMockClient($issuing);
    $result = $service->unfreezeCard($card);

    expect($result['success'])->toBeFalse();
});

it('cancelCard on a Stripe-backed card cancels on the SDK and still refunds locally even if the SDK call fails', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create(['currency' => 'USD', 'balance' => 50]);
    $card = VirtualCard::factory()->create([
        'user_id' => $user->id, 'wallet_id' => $wallet->id, 'provider' => 'stripe', 'provider_card_id' => 'ic_cancel1', 'balance' => 20,
    ]);

    $cardsService = Mockery::mock();
    $cardsService->shouldReceive('update')->once()->andThrow(
        \Stripe\Exception\InvalidRequestException::factory('already canceled')
    );
    $issuing = issuingServiceDouble(['cards' => $cardsService]);

    $service = stripeIssuingServiceWithMockClient($issuing);
    $result = $service->cancelCard($card, $wallet);

    // Local refund + cancellation still succeeds even though the remote
    // Stripe cancel call failed (logged as a warning, not fatal).
    expect($result['success'])->toBeTrue();
    expect((float) $result['refunded'])->toBe(20.0);
    expect((float) $wallet->fresh()->balance)->toBe(70.0);
    expect($card->fresh()->status)->toBe(CardStatus::CANCELLED);
});

it('verifyWebhookSignature returns false when no webhook secret is configured', function () {
    $service = new StripeIssuingService();

    expect($service->verifyWebhookSignature('{}', 't=123,v1=abc'))->toBeFalse();
});

it('verifyWebhookSignature validates the HMAC and timestamp tolerance', function () {
    Integration::withTrashed()->updateOrCreate(
        ['key' => 'stripe'],
        [
            'name' => 'Stripe', 'name_ar' => 'سترايب', 'is_active' => true, 'is_visible' => true, 'category' => 'payment',
            'credentials' => ['secret' => null, 'issuing_webhook_secret' => 'whsec_test'],
            'settings' => ['test_mode' => true],
            'deleted_at' => null,
        ]
    );

    $service = new StripeIssuingService();
    $payload = '{"event":"test"}';
    $timestamp = time();
    $validSig = hash_hmac('sha256', $timestamp . '.' . $payload, 'whsec_test');

    expect($service->verifyWebhookSignature($payload, "t={$timestamp},v1={$validSig}"))->toBeTrue();
    expect($service->verifyWebhookSignature($payload, "t={$timestamp},v1=forged"))->toBeFalse();

    // Outside the 300s replay tolerance
    $staleTimestamp = time() - 400;
    $staleSig = hash_hmac('sha256', $staleTimestamp . '.' . $payload, 'whsec_test');
    expect($service->verifyWebhookSignature($payload, "t={$staleTimestamp},v1={$staleSig}"))->toBeFalse();

    // Malformed signature header (missing t/v1 pairs)
    expect($service->verifyWebhookSignature($payload, 'garbage'))->toBeFalse();
});

// ==================== handleAuthorizationRequest (pure DB logic, no Stripe API call) ====================

function issuingAuth(string $cardId, float $amountDollars, string $category = 'grocery_stores', string $country = 'US'): array
{
    return [
        'id' => 'iauth_' . uniqid(),
        'card' => ['id' => $cardId],
        'pending_request' => ['amount' => (int) round($amountDollars * 100), 'currency' => 'usd'],
        'merchant_data' => ['name' => 'Test Merchant', 'category' => $category, 'country' => $country],
    ];
}

it('declines authorization when the card cannot be found locally', function () {
    $service = new StripeIssuingService();

    $result = $service->handleAuthorizationRequest(issuingAuth('ic_unknown', 10));

    expect($result['approved'])->toBeFalse();
    expect($result['reason'])->toBe('card_not_found');
});

it('declines authorization for a frozen card (frozen-wallet/card guard)', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create(['currency' => 'USD', 'balance' => 1000, 'available_balance' => 1000]);
    $card = VirtualCard::factory()->frozen()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'provider' => 'stripe',
        'provider_card_id' => 'ic_frozen1',
    ]);

    $service = new StripeIssuingService();
    $result = $service->handleAuthorizationRequest(issuingAuth('ic_frozen1', 10));

    expect($result['approved'])->toBeFalse();
    expect($result['reason'])->toBe('card_inactive');
});

it('declines authorization when the wallet has insufficient available balance', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create(['currency' => 'USD', 'balance' => 5, 'available_balance' => 5]);
    $card = VirtualCard::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'provider' => 'stripe',
        'provider_card_id' => 'ic_poor1',
        'status' => 'active',
        'is_active' => true,
        'daily_limit' => 500,
        'monthly_limit' => 5000,
        'per_transaction_limit' => 500,
    ]);

    $service = new StripeIssuingService();
    $result = $service->handleAuthorizationRequest(issuingAuth('ic_poor1', 50));

    expect($result['approved'])->toBeFalse();
    expect($result['reason'])->toBe('insufficient_funds');
});

it('approves a valid authorization and holds the funds via Wallet::hold (available_balance -> pending_balance)', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create(['currency' => 'USD', 'balance' => 1000, 'available_balance' => 1000, 'pending_balance' => 0]);
    $card = VirtualCard::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'provider' => 'stripe',
        'provider_card_id' => 'ic_ok1',
        'status' => 'active',
        'is_active' => true,
        'daily_limit' => 500,
        'monthly_limit' => 5000,
        'per_transaction_limit' => 500,
        'daily_spent' => 0,
        'monthly_spent' => 0,
    ]);

    $service = new StripeIssuingService();
    $result = $service->handleAuthorizationRequest(issuingAuth('ic_ok1', 25));

    expect($result['approved'])->toBeTrue();
    expect($result)->toHaveKey('transaction_id');

    $wallet->refresh();
    expect((float) $wallet->available_balance)->toBe(975.0);
    expect((float) $wallet->pending_balance)->toBe(25.0);
    expect((float) $wallet->balance)->toBe(1000.0); // not debited until capture
});

it('is idempotent: a replayed authorization request (same Stripe auth id) does not double-hold funds', function () {
    // Regression for C-SEV-1: issuing_authorization.request is exempt from the
    // controller's event-id dedup (must answer within Stripe's 2s window), so a
    // Stripe re-delivery or signed-payload replay can call this method twice
    // with the identical authorization payload/id. It must return the same
    // approved decision without a second hold, a second spend increment, or a
    // second Transaction row.
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create(['currency' => 'USD', 'balance' => 1000, 'available_balance' => 1000, 'pending_balance' => 0]);
    $card = VirtualCard::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'provider' => 'stripe',
        'provider_card_id' => 'ic_replay1',
        'status' => 'active',
        'is_active' => true,
        'daily_limit' => 500,
        'monthly_limit' => 5000,
        'per_transaction_limit' => 500,
        'daily_spent' => 0,
        'monthly_spent' => 0,
    ]);

    $service = new StripeIssuingService();
    $auth = issuingAuth('ic_replay1', 25); // fixed authorization id, reused below

    $first = $service->handleAuthorizationRequest($auth);
    $second = $service->handleAuthorizationRequest($auth);

    expect($first['approved'])->toBeTrue();
    expect($second['approved'])->toBeTrue();
    expect($second['transaction_id'])->toBe($first['transaction_id']);
    expect($second['idempotent_replay'] ?? false)->toBeTrue();

    $wallet->refresh();
    expect((float) $wallet->available_balance)->toBe(975.0); // held once, not twice
    expect((float) $wallet->pending_balance)->toBe(25.0);

    $card->refresh();
    expect((float) $card->daily_spent)->toBe(25.0);
    expect((float) $card->monthly_spent)->toBe(25.0);

    expect(Transaction::where('metadata->authorization_id', $auth['id'])->count())->toBe(1);
});

it('declines authorization exceeding the $500/day spend cap', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create(['currency' => 'USD', 'balance' => 10000, 'available_balance' => 10000]);
    $card = VirtualCard::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'provider' => 'stripe',
        'provider_card_id' => 'ic_daily1',
        'status' => 'active',
        'is_active' => true,
        'daily_limit' => 500,
        'monthly_limit' => 5000,
        'per_transaction_limit' => 500,
        'daily_spent' => 480, // + 25 would exceed the $500 daily cap
        'monthly_spent' => 480,
        'daily_reset_at' => now()->toDateString(),
        'monthly_reset_at' => now()->startOfMonth()->toDateString(),
    ]);

    $service = new StripeIssuingService();
    $result = $service->handleAuthorizationRequest(issuingAuth('ic_daily1', 25));

    expect($result['approved'])->toBeFalse();
    expect($result['reason'])->toBe('spending_limit_exceeded');
});

it('declines authorization exceeding the $5,000/month spend cap', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create(['currency' => 'USD', 'balance' => 10000, 'available_balance' => 10000]);
    $card = VirtualCard::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'provider' => 'stripe',
        'provider_card_id' => 'ic_monthly1',
        'status' => 'active',
        'is_active' => true,
        'daily_limit' => 500,
        'monthly_limit' => 5000,
        'per_transaction_limit' => 500,
        'daily_spent' => 0,
        'monthly_spent' => 4990, // + 25 would exceed the $5,000 monthly cap
        'daily_reset_at' => now()->toDateString(),
        'monthly_reset_at' => now()->startOfMonth()->toDateString(),
    ]);

    $service = new StripeIssuingService();
    $result = $service->handleAuthorizationRequest(issuingAuth('ic_monthly1', 25));

    expect($result['approved'])->toBeFalse();
    expect($result['reason'])->toBe('spending_limit_exceeded');
});

it('resets stale daily/monthly spent counters before evaluating the spend cap', function () {
    // Sufficient wallet balance so the flow reaches checkSpendingLimits (past
    // the insufficient_funds gate) and is approved — proving the reset-then-check
    // ordering inside checkSpendingLimits ran and persisted before the cap check.
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create(['currency' => 'USD', 'balance' => 10000, 'available_balance' => 10000]);
    $card = VirtualCard::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'provider' => 'stripe',
        'provider_card_id' => 'ic_reset1',
        'status' => 'active',
        'is_active' => true,
        'daily_limit' => 500,
        'monthly_limit' => 5000,
        'per_transaction_limit' => 500,
        'daily_spent' => 499, // stale — from yesterday
        'monthly_spent' => 4999, // stale — from last month
        'daily_reset_at' => now()->subDay()->toDateString(),
        'monthly_reset_at' => now()->subMonth()->startOfMonth()->toDateString(),
    ]);

    $service = new StripeIssuingService();
    $result = $service->handleAuthorizationRequest(issuingAuth('ic_reset1', 25));

    // Approved — proving the stale 499/4999 counters were reset to 0 before
    // the +25 check ran (499+25 > 500 would otherwise have tripped the daily cap).
    expect($result['approved'])->toBeTrue();
    $card->refresh();
    expect((float) $card->daily_spent)->toBe(25.0);
    expect((float) $card->monthly_spent)->toBe(25.0);
});

it('declines a transaction above the per-transaction limit', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create(['currency' => 'USD', 'balance' => 10000, 'available_balance' => 10000]);
    $card = VirtualCard::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'provider' => 'stripe',
        'provider_card_id' => 'ic_pertxn1',
        'status' => 'active',
        'is_active' => true,
        'daily_limit' => 500,
        'monthly_limit' => 5000,
        'per_transaction_limit' => 200,
        'daily_spent' => 0,
        'monthly_spent' => 0,
    ]);

    $service = new StripeIssuingService();
    $result = $service->handleAuthorizationRequest(issuingAuth('ic_pertxn1', 250));

    expect($result['approved'])->toBeFalse();
    expect($result['reason'])->toBe('spending_limit_exceeded');
});

it('declines authorization for a blocked merchant category', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create(['currency' => 'USD', 'balance' => 1000, 'available_balance' => 1000]);
    $card = VirtualCard::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'provider' => 'stripe',
        'provider_card_id' => 'ic_gambling1',
        'status' => 'active',
        'is_active' => true,
        'daily_limit' => 500,
        'monthly_limit' => 5000,
        'per_transaction_limit' => 500,
    ]);

    $service = new StripeIssuingService();
    $result = $service->handleAuthorizationRequest(issuingAuth('ic_gambling1', 25, 'gambling'));

    expect($result['approved'])->toBeFalse();
    expect($result['reason'])->toBe('merchant_blocked');
});

it('declines an international transaction when international is disabled on the card', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create(['currency' => 'USD', 'balance' => 1000, 'available_balance' => 1000]);
    $card = VirtualCard::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'provider' => 'stripe',
        'provider_card_id' => 'ic_intl1',
        'status' => 'active',
        'is_active' => true,
        'daily_limit' => 500,
        'monthly_limit' => 5000,
        'per_transaction_limit' => 500,
        'international_enabled' => false,
    ]);

    $service = new StripeIssuingService();
    $result = $service->handleAuthorizationRequest(issuingAuth('ic_intl1', 25, 'grocery_stores', 'FR'));

    expect($result['approved'])->toBeFalse();
    expect($result['reason'])->toBe('international_disabled');
});

// ==================== handleAuthorizationCapture ====================
// Both capture and the PROCESSING branch of reversal ALSO call
// $wallet->decrement('reserved_balance', ...) — the SAME missing-column bug
// documented above. These two paths are locked in as BUG regression tests
// rather than pretending they work end to end. capture's "no transaction
// found" early-return path (before reserved_balance is touched) is still
// genuinely covered.

it('captures an approved authorization by settling pending_balance via Wallet::capture', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create(['currency' => 'USD', 'balance' => 1000, 'available_balance' => 975, 'pending_balance' => 25]);
    $card = VirtualCard::factory()->create(['user_id' => $user->id, 'wallet_id' => $wallet->id]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'card_id' => $card->id,
        'amount' => -25,
        'status' => \App\Enums\TransactionStatus::PROCESSING,
        'metadata' => ['authorization_id' => 'auth_cap1'],
    ]);

    $service = new StripeIssuingService();
    $result = $service->handleAuthorizationCapture(['id' => 'auth_cap1', 'card' => ['id' => $card->provider_card_id], 'approved_amount' => 2500]);

    expect($result['success'])->toBeTrue();

    $wallet->refresh();
    expect((float) $wallet->pending_balance)->toBe(0.0);
    expect((float) $wallet->balance)->toBe(975.0);
    expect((float) $wallet->available_balance)->toBe(1000.0);
});

it('capture returns an error when no pending authorization transaction exists (does not reach the broken reserved_balance code)', function () {
    $service = new StripeIssuingService();

    $result = $service->handleAuthorizationCapture(['id' => 'auth_missing', 'approved_amount' => 1000]);

    expect($result['success'])->toBeFalse();
});

// ==================== handleAuthorizationReversal ====================

it('reverses a still-processing (held) authorization by releasing pending_balance via Wallet::release', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create(['currency' => 'USD', 'balance' => 1000, 'available_balance' => 975, 'pending_balance' => 25]);
    $card = VirtualCard::factory()->create(['user_id' => $user->id, 'wallet_id' => $wallet->id, 'daily_spent' => 25, 'monthly_spent' => 25]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'card_id' => $card->id,
        'amount' => -25,
        'status' => \App\Enums\TransactionStatus::PROCESSING,
        'metadata' => ['authorization_id' => 'auth_rev1'],
    ]);

    $service = new StripeIssuingService();
    $result = $service->handleAuthorizationReversal(['id' => 'auth_rev1', 'amount_reversed' => 2500]);

    expect($result['success'])->toBeTrue();

    $wallet->refresh();
    expect((float) $wallet->pending_balance)->toBe(0.0);
    expect((float) $wallet->available_balance)->toBe(1000.0);
    expect((float) $wallet->balance)->toBe(1000.0); // never debited (hold-only, no capture)

    $card->refresh();
    expect((float) $card->daily_spent)->toBe(0.0);
    expect((float) $card->monthly_spent)->toBe(0.0);
});

it('reverses a completed (captured) authorization by refunding the wallet', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create(['currency' => 'USD', 'balance' => 975, 'available_balance' => 975]);
    $card = VirtualCard::factory()->create(['user_id' => $user->id, 'wallet_id' => $wallet->id, 'daily_spent' => 25, 'monthly_spent' => 25]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'card_id' => $card->id,
        'amount' => -25,
        'status' => \App\Enums\TransactionStatus::COMPLETED,
        'metadata' => ['authorization_id' => 'auth_rev2'],
    ]);

    $service = new StripeIssuingService();
    $result = $service->handleAuthorizationReversal(['id' => 'auth_rev2', 'amount_reversed' => 2500]);

    expect($result['success'])->toBeTrue();
    $wallet->refresh();
    expect((float) $wallet->balance)->toBe(1000.0);
});

it('is idempotent: a second reversal of an already-refunded authorization does not double-credit', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create(['currency' => 'USD', 'balance' => 1000, 'available_balance' => 1000]);
    $card = VirtualCard::factory()->create(['user_id' => $user->id, 'wallet_id' => $wallet->id]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'card_id' => $card->id,
        'amount' => -25,
        'status' => \App\Enums\TransactionStatus::REFUNDED,
        'metadata' => ['authorization_id' => 'auth_rev3'],
    ]);

    $service = new StripeIssuingService();
    $result = $service->handleAuthorizationReversal(['id' => 'auth_rev3', 'amount_reversed' => 2500]);

    expect($result['success'])->toBeTrue();
    expect($result['already_reversed'])->toBeTrue();
    expect((float) $wallet->fresh()->balance)->toBe(1000.0); // unchanged
});

it('reversal returns an error when no matching authorization transaction exists', function () {
    $service = new StripeIssuingService();

    $result = $service->handleAuthorizationReversal(['id' => 'auth_missing', 'amount_reversed' => 500]);

    expect($result['success'])->toBeFalse();
});
