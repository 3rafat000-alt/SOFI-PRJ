<?php

use App\Models\GoldPrice;
use App\Models\SystemSetting;
use App\Services\GoldPriceService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->service = new GoldPriceService();
});

it('reports auto-update disabled by default', function () {
    expect($this->service->isAutoEnabled())->toBeFalse();
});

it('reports auto-update enabled when the system setting is set', function () {
    SystemSetting::set('gold_auto_update', '1', 'boolean');

    expect($this->service->isAutoEnabled())->toBeTrue();
});

it('uses the default margin when unset', function () {
    expect($this->service->margin())->toBe(0.75);
});

it('clamps margin to the 0-10 sane range', function () {
    SystemSetting::set('gold_auto_margin', '999', 'decimal');
    expect($this->service->margin())->toBe(10.0);

    SystemSetting::set('gold_auto_margin', '-5', 'decimal');
    expect($this->service->margin())->toBe(0.0);

    SystemSetting::set('gold_auto_margin', '2.5', 'decimal');
    expect($this->service->margin())->toBe(2.5);
});

it('fetches spot price from gold-api.com and converts ounce to gram', function () {
    Http::fake([
        'api.gold-api.com/*' => Http::response(['price' => 2400.0], 200),
    ]);

    $gram = $this->service->fetchSpotPerGram24k();

    // 2400 / 31.1034768 troy-oz-per-gram
    expect($gram)->toBe(round(2400.0 / 31.1034768, 4));
});

it('returns null when gold-api.com request fails', function () {
    Http::fake([
        'api.gold-api.com/*' => Http::response([], 500),
    ]);

    expect($this->service->fetchSpotPerGram24k())->toBeNull();
});

it('returns null when gold-api.com returns a non-positive price', function () {
    Http::fake([
        'api.gold-api.com/*' => Http::response(['price' => 0], 200),
    ]);

    expect($this->service->fetchSpotPerGram24k())->toBeNull();
});

it('fetches spot price from goldapi.io when configured with a key', function () {
    config(['services.gold.provider' => 'goldapi', 'services.gold.goldapi_key' => 'test-key']);

    Http::fake([
        'www.goldapi.io/*' => Http::response(['price_gram_24k' => 88.5], 200),
    ]);

    $gram = $this->service->fetchSpotPerGram24k();

    expect($gram)->toBe(88.5);
});

it('returns null for goldapi.io provider when the key is missing', function () {
    config(['services.gold.provider' => 'goldapi', 'services.gold.goldapi_key' => null]);

    expect($this->service->fetchSpotPerGram24k())->toBeNull();
});

it('returns null when goldapi.io request fails', function () {
    config(['services.gold.provider' => 'goldapi', 'services.gold.goldapi_key' => 'test-key']);

    Http::fake([
        'www.goldapi.io/*' => Http::response([], 503),
    ]);

    expect($this->service->fetchSpotPerGram24k())->toBeNull();
});

it('returns null when the HTTP client throws', function () {
    Http::fake(function () {
        throw new \Exception('network down');
    });

    expect($this->service->fetchSpotPerGram24k())->toBeNull();
});

it('refresh returns failure message when spot fetch fails, without touching prices', function () {
    Http::fake([
        'api.gold-api.com/*' => Http::response([], 500),
    ]);

    $result = $this->service->refresh();

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toBeString();
    expect(GoldPrice::count())->toBe(0);
});

it('refresh derives and persists per-karat buy/sell prices from spot with default margin', function () {
    Http::fake([
        'api.gold-api.com/*' => Http::response(['price' => 2400.0], 200),
    ]);

    $result = $this->service->refresh();

    expect($result['success'])->toBeTrue();
    expect($result['updated'])->toBe(4);
    expect($result['margin'])->toBe(0.75);

    $spotPerGram = round(2400.0 / 31.1034768, 4);
    expect($result['spot'])->toBe($spotPerGram);

    $row24 = GoldPrice::where('karat', '24')->first();
    $mid24 = round($spotPerGram * (24 / 24), 2);
    $expectedBuy = round($mid24 * 1.0075, 2);
    $expectedSell = round($mid24 * 0.9925, 2);

    expect((float) $row24->buy_price)->toBe($expectedBuy);
    expect((float) $row24->sell_price)->toBe($expectedSell);
    expect($row24->source)->toBe('auto');
    expect($row24->is_active)->toBeTrue();

    expect((float) SystemSetting::get('gold_last_spot_24k'))->toBe($spotPerGram);
    expect(SystemSetting::get('gold_last_auto_update'))->not->toBeNull();
});

it('refresh preserves an admin-disabled karat instead of re-activating it', function () {
    GoldPrice::create([
        'karat' => '18',
        'buy_price' => 1,
        'sell_price' => 1,
        'spread' => 0,
        'source' => 'manual',
        'is_active' => false,
    ]);

    Http::fake([
        'api.gold-api.com/*' => Http::response(['price' => 2400.0], 200),
    ]);

    $this->service->refresh();

    $row18 = GoldPrice::where('karat', '18')->first();
    expect($row18->is_active)->toBeFalse();
    expect($row18->source)->toBe('auto');
});

it('refresh updates existing rows in place across repeated calls', function () {
    Http::fake([
        'api.gold-api.com/*' => Http::sequence()
            ->push(['price' => 2000.0], 200)
            ->push(['price' => 2500.0], 200),
    ]);

    $this->service->refresh();
    expect(GoldPrice::count())->toBe(4);

    $this->service->refresh();
    expect(GoldPrice::count())->toBe(4);

    $row24 = GoldPrice::where('karat', '24')->first();
    $expectedSpot = round(2500.0 / 31.1034768, 4);
    $expectedMid = round($expectedSpot, 2);
    expect((float) $row24->buy_price)->toBe(round($expectedMid * 1.0075, 2));
});
