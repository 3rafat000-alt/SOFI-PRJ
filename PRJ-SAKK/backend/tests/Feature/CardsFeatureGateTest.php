<?php

use App\Models\Integration;
use App\Models\User;
use App\Support\CardsFeature;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
});

function setStripe(bool $active, ?string $secret): void
{
    Integration::query()->where('key', 'stripe')->delete();
    Integration::create([
        'key' => 'stripe',
        'name' => 'Stripe',
        'name_ar' => 'ستريب',
        'category' => 'payment',
        'is_active' => $active,
        'credentials' => $secret !== null ? ['secret' => $secret] : [],
    ]);
}

it('reports cards disabled while the stripe gateway is inactive', function () {
    setStripe(active: false, secret: 'sk_test_ABC');
    expect(CardsFeature::enabled())->toBeFalse();

    $this->getJson('/api/v1/features')
        ->assertOk()
        ->assertJsonPath('data.cards_enabled', false);
});

it('reports cards disabled when active but no secret key is set', function () {
    setStripe(active: true, secret: null);
    expect(CardsFeature::enabled())->toBeFalse();
});

it('blocks the cards API with a 503 cards_disabled while disabled', function () {
    setStripe(active: false, secret: 'sk_test_ABC');
    Sanctum::actingAs($this->user);

    $this->getJson('/api/v1/cards')
        ->assertStatus(503)
        ->assertJsonPath('code', 'cards_disabled');

    $this->postJson('/api/v1/cards', ['wallet_id' => 1, 'brand' => 'visa'])
        ->assertStatus(503)
        ->assertJsonPath('code', 'cards_disabled');
});

it('opens the cards feature the moment stripe is active with a secret', function () {
    setStripe(active: true, secret: 'sk_test_ABC');
    expect(CardsFeature::enabled())->toBeTrue();

    Sanctum::actingAs($this->user);

    // Gate passes → request reaches the controller (200, empty list for a new user).
    $this->getJson('/api/v1/cards')->assertOk();

    $this->getJson('/api/v1/features')
        ->assertOk()
        ->assertJsonPath('data.cards_enabled', true);
});
