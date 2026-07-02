<?php

use App\Models\ExchangeRate;
use App\Models\ExchangeRateHistory;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

// App\Http\Controllers\API\ExchangeRateController — SYP true-scale is
// 1 USD ~ 13,000 SYP everywhere in this codebase (NO division by 100); the
// mid/buy/sell rates below and their inverse (SYP->USD) reflect that.

beforeEach(function () {
    Cache::flush();
    Sanctum::actingAs(User::factory()->create());
});

function seedSakkRate(float $rate = 13000, float $spread = 2.0): ExchangeRate
{
    return ExchangeRate::updateOrCreate(
        ['from_currency' => 'USD', 'to_currency' => 'SYP'],
        [
            'rate' => $rate,
            'buy_rate' => $rate * (1 - $spread / 200),
            'sell_rate' => $rate * (1 + $spread / 200),
            'spread' => $spread,
            'source' => 'manual',
            'is_active' => true,
            'fetched_at' => now(),
        ]
    );
}

// ==================== getRate ====================

it('returns the USD/SYP rate with calculated buy/sell rates at true scale (~13,000)', function () {
    seedSakkRate(13000, 2.0);

    $response = $this->getJson('/api/v1/exchange-rates/rate?from=USD&to=SYP');

    $response->assertStatus(200)
        ->assertJson(['success' => true, 'data' => ['from' => 'USD', 'to' => 'SYP', 'rate' => 13000.0]]);

    $data = $response->json('data');
    expect($data['buy_rate'])->toBeLessThan(13000);
    expect($data['sell_rate'])->toBeGreaterThan(13000);
});

it('returns the inverse SYP->USD rate correctly derived from the single USD/SYP row', function () {
    seedSakkRate(13000, 2.0);

    $response = $this->getJson('/api/v1/exchange-rates/rate?from=SYP&to=USD');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data['from'])->toBe('SYP');
    expect($data['to'])->toBe('USD');
    expect(round($data['rate'], 8))->toBe(round(1 / 13000, 8));
});

it('returns 1:1 for identical currencies without touching the database', function () {
    $response = $this->getJson('/api/v1/exchange-rates/rate?from=USD&to=USD');

    $response->assertStatus(200)
        ->assertJson(['success' => true, 'data' => ['rate' => 1.0, 'buy_rate' => 1.0, 'sell_rate' => 1.0]]);
});

it('returns 404 when no exchange rate is configured', function () {
    ExchangeRate::query()->delete();

    $response = $this->getJson('/api/v1/exchange-rates/rate?from=USD&to=SYP');

    $response->assertStatus(404)->assertJson(['success' => false]);
});

it('defaults to USD/SYP when no query params are given', function () {
    seedSakkRate();

    $response = $this->getJson('/api/v1/exchange-rates/rate');

    $response->assertStatus(200)->assertJson(['data' => ['from' => 'USD', 'to' => 'SYP']]);
});

// ==================== getAllRates ====================

it('returns all rates keyed by target currency', function () {
    seedSakkRate(13000);

    $response = $this->getJson('/api/v1/exchange-rates');

    $response->assertStatus(200)
        ->assertJson(['success' => true, 'data' => ['success' => true, 'base' => 'USD']])
        ->assertJsonStructure(['data' => ['rates' => ['SYP' => ['rate', 'buy_rate', 'sell_rate', 'spread']]]]);
});

it('returns 404 from getAllRates when unconfigured', function () {
    ExchangeRate::query()->delete();

    $response = $this->getJson('/api/v1/exchange-rates');

    $response->assertStatus(404);
});

// ==================== convert ====================

it('converts USD to SYP using the sell rate by default (worse rate for the user)', function () {
    seedSakkRate(13000, 2.0);

    $response = $this->postJson('/api/v1/exchange-rates/convert', [
        'amount' => 10,
        'from' => 'USD',
        'to' => 'SYP',
    ]);

    $response->assertStatus(200)->assertJson(['success' => true]);
    $data = $response->json('data');
    expect($data['direction'])->toBe('sell');
    // sell_rate > mid_rate, so converting 10 USD nets MORE than 130,000 SYP raw
    expect($data['converted_amount'])->toBeGreaterThan(130000);
});

it('converts using the buy rate when direction=buy is explicit', function () {
    seedSakkRate(13000, 2.0);

    $response = $this->postJson('/api/v1/exchange-rates/convert', [
        'amount' => 10,
        'from' => 'USD',
        'to' => 'SYP',
        'direction' => 'buy',
    ]);

    $response->assertStatus(200);
    expect($response->json('data.direction'))->toBe('buy');
    expect($response->json('data.converted_amount'))->toBeLessThan(130000);
});

it('rejects a zero or negative conversion amount', function () {
    seedSakkRate();

    $response = $this->postJson('/api/v1/exchange-rates/convert', [
        'amount' => 0,
        'from' => 'USD',
        'to' => 'SYP',
    ]);

    $response->assertStatus(422);

    $responseNeg = $this->postJson('/api/v1/exchange-rates/convert', [
        'amount' => -5,
        'from' => 'USD',
        'to' => 'SYP',
    ]);

    $responseNeg->assertStatus(422);
});

it('rejects an unsupported currency pair', function () {
    seedSakkRate();

    $response = $this->postJson('/api/v1/exchange-rates/convert', [
        'amount' => 10,
        'from' => 'EUR',
        'to' => 'SYP',
    ]);

    $response->assertStatus(422);
});

it('handles a huge conversion amount without overflow/error', function () {
    seedSakkRate(13000, 2.0);

    $response = $this->postJson('/api/v1/exchange-rates/convert', [
        'amount' => 999999999,
        'from' => 'USD',
        'to' => 'SYP',
    ]);

    $response->assertStatus(200);
    expect($response->json('data.converted_amount'))->toBeGreaterThan(0);
});

it('rounds the converted amount to 2 decimal places', function () {
    seedSakkRate(13333.333333, 2.0);

    $response = $this->postJson('/api/v1/exchange-rates/convert', [
        'amount' => 1,
        'from' => 'USD',
        'to' => 'SYP',
    ]);

    $response->assertStatus(200);
    $converted = $response->json('data.converted_amount');
    expect(round($converted, 2))->toBe($converted);
});

it('returns 400 when converting with no configured rate', function () {
    ExchangeRate::query()->delete();

    $response = $this->postJson('/api/v1/exchange-rates/convert', [
        'amount' => 10,
        'from' => 'USD',
        'to' => 'SYP',
    ]);

    $response->assertStatus(400)->assertJson(['success' => false]);
});

// ==================== getHistory ====================

it('returns rate history within the requested window', function () {
    ExchangeRateHistory::create([
        'from_currency' => 'USD', 'to_currency' => 'SYP',
        'rate' => 13000, 'buy_rate' => 12870, 'sell_rate' => 13130,
        'source' => 'manual', 'recorded_at' => now()->subDays(5),
    ]);
    ExchangeRateHistory::create([
        'from_currency' => 'USD', 'to_currency' => 'SYP',
        'rate' => 13100, 'buy_rate' => 12969, 'sell_rate' => 13231,
        'source' => 'manual', 'recorded_at' => now()->subDays(40), // outside default 30-day window
    ]);

    $response = $this->getJson('/api/v1/exchange-rates/history');

    $response->assertStatus(200)->assertJson(['success' => true]);
    expect($response->json('data.data'))->toHaveCount(1);
});

it('respects a custom days window for history', function () {
    ExchangeRateHistory::create([
        'from_currency' => 'USD', 'to_currency' => 'SYP',
        'rate' => 13000, 'buy_rate' => 12870, 'sell_rate' => 13130,
        'source' => 'manual', 'recorded_at' => now()->subDays(40),
    ]);

    $response = $this->getJson('/api/v1/exchange-rates/history?days=60');

    $response->assertStatus(200);
    expect($response->json('data.data'))->toHaveCount(1);
});

// ==================== isConfigured ====================

it('reports configured=true when an active USD/SYP row exists', function () {
    seedSakkRate();

    $response = $this->getJson('/api/v1/exchange-rates/configured');

    $response->assertStatus(200)->assertJson(['success' => true, 'data' => ['configured' => true]]);
});

it('reports configured=false when no active rate exists', function () {
    ExchangeRate::query()->delete();

    $response = $this->getJson('/api/v1/exchange-rates/configured');

    $response->assertStatus(200)->assertJson(['data' => ['configured' => false]]);
});
