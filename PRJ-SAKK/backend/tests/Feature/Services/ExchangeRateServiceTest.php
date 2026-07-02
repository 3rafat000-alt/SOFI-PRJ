<?php

use App\Models\ExchangeRate;
use App\Models\ExchangeRateHistory;
use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->service = app(ExchangeRateService::class);
    Cache::flush();
});

it('returns 1:1 rate for same currency', function () {
    $result = $this->service->getRate('USD', 'USD');

    expect($result['success'])->toBeTrue();
    expect($result['rate'])->toBe(1.0);
    expect($result['source'])->toBe('direct');
});

it('returns error when no rate configured', function () {
    $result = $this->service->getRate('USD', 'SYP');

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toContain('سعر الصرف غير متوفر');
});

it('gets USD to SYP rate from database', function () {
    ExchangeRate::factory()->create([
        'from_currency' => 'USD',
        'to_currency' => 'SYP',
        'rate' => 13000,
        'spread' => 2.0,
        'is_active' => true,
    ]);

    $result = $this->service->getRate('USD', 'SYP');

    expect($result['success'])->toBeTrue();
    expect($result['from'])->toBe('USD');
    expect($result['to'])->toBe('SYP');
    expect($result['rate'])->toBe(13000.0);
    expect($result['buy_rate'])->toBeLessThan($result['sell_rate']);
    expect($result['spread'])->toBe(2.0);
});

it('gets SYP to USD rate by inverting', function () {
    ExchangeRate::factory()->create([
        'from_currency' => 'USD',
        'to_currency' => 'SYP',
        'rate' => 13000,
        'spread' => 2.0,
        'is_active' => true,
    ]);

    $result = $this->service->getRate('SYP', 'USD');

    expect($result['success'])->toBeTrue();
    expect($result['from'])->toBe('SYP');
    expect($result['to'])->toBe('USD');
    expect($result['rate'])->toBe(1 / 13000);
});

it('caches the exchange rate', function () {
    ExchangeRate::factory()->create([
        'from_currency' => 'USD',
        'to_currency' => 'SYP',
        'rate' => 13000,
        'is_active' => true,
    ]);

    // First call should read from DB and cache
    $this->service->getRate('USD', 'SYP');

    // Delete the rate to ensure cache is working
    ExchangeRate::query()->delete();

    // Second call should come from cache
    $result = $this->service->getRate('USD', 'SYP');

    expect($result['success'])->toBeTrue();
    expect($result['rate'])->toBe(13000.0);
});

it('converts USD to SYP using sell rate by default', function () {
    ExchangeRate::factory()->create([
        'from_currency' => 'USD',
        'to_currency' => 'SYP',
        'rate' => 13000,
        'spread' => 2.0,
        'is_active' => true,
    ]);

    $result = $this->service->convert(100, 'USD', 'SYP');

    expect($result['success'])->toBeTrue();
    expect($result['original_amount'])->toBe(100.0);
    expect($result['original_currency'])->toBe('USD');
    expect($result['target_currency'])->toBe('SYP');
    expect($result['converted_amount'])->toBeGreaterThan(0);
});

it('converts SYP to USD using appropriate rate', function () {
    ExchangeRate::factory()->create([
        'from_currency' => 'USD',
        'to_currency' => 'SYP',
        'rate' => 13000,
        'spread' => 2.0,
        'is_active' => true,
    ]);

    $result = $this->service->convert(1300000, 'SYP', 'USD', 'sell');

    expect($result['success'])->toBeTrue();
    expect($result['original_currency'])->toBe('SYP');
    expect($result['target_currency'])->toBe('USD');
});

it('returns error when converting with no rate', function () {
    $result = $this->service->convert(100, 'USD', 'SYP');

    expect($result['success'])->toBeFalse();
});

it('uses buy rate when direction is buy', function () {
    ExchangeRate::factory()->create([
        'from_currency' => 'USD',
        'to_currency' => 'SYP',
        'rate' => 13000,
        'spread' => 2.0,
        'is_active' => true,
    ]);

    $buyResult = $this->service->convert(100, 'USD', 'SYP', 'buy');
    $sellResult = $this->service->convert(100, 'USD', 'SYP', 'sell');

    // Buy rate should be lower than sell rate (better for user buying SYP)
    expect($buyResult['converted_amount'])->not->toBe($sellResult['converted_amount']);
});

it('gets all rates', function () {
    ExchangeRate::factory()->create([
        'from_currency' => 'USD',
        'to_currency' => 'SYP',
        'rate' => 13000,
        'is_active' => true,
    ]);

    $result = $this->service->getAllRates();

    expect($result['success'])->toBeTrue();
    expect($result['base'])->toBe('USD');
    expect($result['rates'])->toHaveKey('SYP');
    expect($result['rates']['SYP']['rate'])->toBe(13000.0);
});

it('updates exchange rate and clears cache', function () {
    $oldRate = ExchangeRate::factory()->create([
        'from_currency' => 'USD',
        'to_currency' => 'SYP',
        'rate' => 13000,
        'is_active' => true,
    ]);

    $updated = $this->service->updateRate(14000, 3.0);

    expect((float) $updated->rate)->toBe(14000.0);
    expect((float) $updated->spread)->toBe(3.0);
    expect($updated->source)->toBe('manual');

    // Verify history record created
    $history = ExchangeRateHistory::where('from_currency', 'USD')->first();
    expect($history)->not->toBeNull();
    expect((float) $history->rate)->toBe(14000.0);

    // Verify cache cleared - rate should be fresh from DB
    $result = $this->service->getRate('USD', 'SYP');
    expect($result['rate'])->toBe(14000.0);
});

it('gets current rate model', function () {
    $rate = ExchangeRate::factory()->create([
        'from_currency' => 'USD',
        'to_currency' => 'SYP',
    ]);

    $current = $this->service->getCurrentRate();

    expect($current)->not->toBeNull();
    expect($current->id)->toBe($rate->id);
});

it('returns null for current rate when none exists', function () {
    $current = $this->service->getCurrentRate();
    expect($current)->toBeNull();
});

it('gets rate history', function () {
    ExchangeRateHistory::factory()->count(5)->create([
        'from_currency' => 'USD',
        'to_currency' => 'SYP',
    ]);

    $history = $this->service->getRateHistory(30);

    expect($history['success'])->toBeTrue();
    expect($history['from'])->toBe('USD');
    expect($history['to'])->toBe('SYP');
    expect(count($history['data']))->toBe(5);
});

it('checks if rate is configured', function () {
    expect($this->service->isConfigured())->toBeFalse();

    ExchangeRate::factory()->create([
        'from_currency' => 'USD',
        'to_currency' => 'SYP',
        'is_active' => true,
    ]);

    expect($this->service->isConfigured())->toBeTrue();
});

it('calculates buy and sell rates from spread correctly', function () {
    ExchangeRate::factory()->create([
        'from_currency' => 'USD',
        'to_currency' => 'SYP',
        'rate' => 10000,
        'spread' => 4.0,
        'is_active' => true,
    ]);

    $result = $this->service->getRate('USD', 'SYP');

    // spread=4%, half=2%, buy=10000*0.98=9800, sell=10000*1.02=10200
    expect($result['buy_rate'])->toBe(9800.0);
    expect($result['sell_rate'])->toBe(10200.0);
});
