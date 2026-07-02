<?php

use App\Models\ExchangeRate;
use App\Models\PlatformRevenue;
use App\Models\Transaction;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * FX conversion path (WalletService::convert + ExchangeRateService).
 * Rate is pinned so the math is exact: rate=13000, spread=2 -> buy=12870, sell=13130.
 */

uses(RefreshDatabase::class);

const PINNED_RATE = 13000.0;
const PINNED_SPREAD = 2.0;
const PINNED_BUY = 12870.0;  // 13000 * (1 - 0.01)
const PINNED_SELL = 13130.0; // 13000 * (1 + 0.01)

function seedPinnedRate(): void
{
    ExchangeRate::updateOrCreate(
        ['from_currency' => 'USD', 'to_currency' => 'SYP'],
        [
            'rate' => PINNED_RATE,
            'buy_rate' => PINNED_BUY,
            'sell_rate' => PINNED_SELL,
            'spread' => PINNED_SPREAD,
            'source' => 'test',
            'is_active' => true,
            'fetched_at' => now(),
        ]
    );
    Illuminate\Support\Facades\Cache::forget('exchange_rate_usd_syp');
}

function convUser(float $usd = 0, float $syp = 0): User
{
    $user = User::factory()->create();

    $user->wallets()->where('currency', 'USD')->update([
        'balance' => $usd,
        'available_balance' => $usd,
        'pending_balance' => 0,
    ]);
    $user->wallets()->where('currency', 'SYP')->update([
        'balance' => $syp,
        'available_balance' => $syp,
        'pending_balance' => 0,
    ]);

    return $user;
}

it('converts usd to syp at the buy rate, debiting from and crediting to', function () {
    seedPinnedRate();
    $user = convUser(usd: 100);
    $from = $user->wallets()->where('currency', 'USD')->first();
    $to = $user->wallets()->where('currency', 'SYP')->first();

    $tx = app(WalletService::class)->convert($from, $to, 50.0, 'usd_to_syp');

    $expectedConverted = 50.0 * PINNED_BUY; // 643,500

    $from->refresh();
    $to->refresh();

    $this->assertEqualsWithDelta(50.0, 100.0 - (float) $from->balance, 0.000001);
    $this->assertEqualsWithDelta($expectedConverted, (float) $to->balance, 0.000001);
    expect((float) $tx->metadata['spread_profit_syp'])->toBeGreaterThan(0);
});

it('converts syp to usd at the sell rate, debiting from and crediting to', function () {
    seedPinnedRate();
    $user = convUser(syp: 1_000_000);
    $from = $user->wallets()->where('currency', 'SYP')->first();
    $to = $user->wallets()->where('currency', 'USD')->first();

    $tx = app(WalletService::class)->convert($from, $to, 130000.0, 'syp_to_usd');

    $expectedConverted = 130000.0 / PINNED_SELL; // ~9.9010...

    $from->refresh();
    $to->refresh();

    $this->assertEqualsWithDelta(130000.0, 1_000_000.0 - (float) $from->balance, 0.000001);
    $this->assertEqualsWithDelta($expectedConverted, (float) $to->balance, 0.000001);
});

it('rejects converting more than the available balance and leaves balances untouched', function () {
    seedPinnedRate();
    $user = convUser(usd: 10);
    $from = $user->wallets()->where('currency', 'USD')->first();
    $to = $user->wallets()->where('currency', 'SYP')->first();

    expect(fn () => app(WalletService::class)->convert($from, $to, 100.0, 'usd_to_syp'))
        ->toThrow(RuntimeException::class);

    $from->refresh();
    $to->refresh();

    $this->assertEqualsWithDelta(10.0, (float) $from->balance, 0.000001);
    $this->assertEqualsWithDelta(0.0, (float) $to->balance, 0.000001);
});

it('rejects conversion between wallets owned by different users (IDOR)', function () {
    seedPinnedRate();
    $userA = convUser(usd: 100);
    $userB = convUser(usd: 0);
    $from = $userA->wallets()->where('currency', 'USD')->first();
    $to = $userB->wallets()->where('currency', 'SYP')->first();

    expect(fn () => app(WalletService::class)->convert($from, $to, 10.0, 'usd_to_syp'))
        ->toThrow(RuntimeException::class, 'مختلفين');
});

it('writes a double-sided ledger (debit leg + credit leg) that reconciles with balances', function () {
    seedPinnedRate();
    $user = convUser(usd: 200);
    $from = $user->wallets()->where('currency', 'USD')->first();
    $to = $user->wallets()->where('currency', 'SYP')->first();

    app(WalletService::class)->convert($from, $to, 20.0, 'usd_to_syp');

    $from->refresh();
    $to->refresh();

    $debitLeg = Transaction::where('wallet_id', $from->id)
        ->where('type', 'exchange')
        ->where('metadata->leg', 'debit')
        ->first();
    $creditLeg = Transaction::where('wallet_id', $to->id)
        ->where('type', 'exchange')
        ->where('metadata->leg', 'credit')
        ->first();

    expect($debitLeg)->not->toBeNull();
    expect($creditLeg)->not->toBeNull();

    $this->assertEqualsWithDelta((float) $debitLeg->balance_after, (float) $from->balance, 0.000001);
    $this->assertEqualsWithDelta((float) $creditLeg->balance_after, (float) $to->balance, 0.000001);

    expect((float) $debitLeg->amount)->toBe(-20.0);
    expect((float) $creditLeg->amount)->toEqualWithDelta(20.0 * PINNED_BUY, 0.000001);
});

it('uses the authoritative DB rate at conversion time, not a stale cached rate (arbitrage-window fix)', function () {
    seedPinnedRate();
    $user = convUser(usd: 100);
    $from = $user->wallets()->where('currency', 'USD')->first();
    $to = $user->wallets()->where('currency', 'SYP')->first();

    // Prime the display-layer cache with the OLD rate.
    app(\App\Services\ExchangeRateService::class)->getRate('USD', 'SYP');

    // World rate jumps AFTER the cache was primed, but the cache key is left
    // stale on purpose (simulates the arbitrage window: cache TTL=300s,
    // admin updates the row without anyone hitting updateRate() to bust it,
    // e.g. a direct DB correction or a race with another request's read).
    $staleBuy = PINNED_BUY;
    $newRate = 20000.0;
    $newSpread = 2.0;
    $newBuy = $newRate * (1 - $newSpread / 200); // 19800

    ExchangeRate::where('from_currency', 'USD')->where('to_currency', 'SYP')->update([
        'rate' => $newRate,
        'buy_rate' => $newBuy,
        'sell_rate' => $newRate * (1 + $newSpread / 200),
        'spread' => $newSpread,
    ]);

    // Sanity: the cache still serves the OLD value (proves a staleness window exists).
    $cachedRead = app(\App\Services\ExchangeRateService::class)->getRate('USD', 'SYP');
    expect((float) $cachedRead['buy_rate'])->toEqualWithDelta($staleBuy, 0.000001);

    // The convert (money-committing) path must ignore that stale cache and use
    // the freshly-updated authoritative row.
    $tx = app(WalletService::class)->convert($from, $to, 10.0, 'usd_to_syp');

    $to->refresh();

    $expectedWithNewRate = 10.0 * $newBuy;      // 198,000
    $expectedWithStaleRate = 10.0 * $staleBuy;   // 128,700

    $this->assertEqualsWithDelta($expectedWithNewRate, (float) $to->balance, 0.000001);
    expect((float) $to->balance)->not->toEqualWithDelta($expectedWithStaleRate, 0.000001);
    expect((float) $tx->metadata['rate'])->toEqualWithDelta($newBuy, 0.000001);
});
