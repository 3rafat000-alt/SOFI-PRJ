<?php

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\ExchangeRate;
use App\Services\WalletService;

/**
 * Regression guard for the SYP magnitude + ledger + spread-profit bug class.
 *
 * Locks down:
 *  - canonical scale 1 USD ≈ 13,000 SYP (TRUE scale, no ÷100);
 *  - convert() DERIVES SYP→USD from the single USD→SYP row (never a stale reverse row);
 *  - convert() records BOTH legs (debit + credit) so balances reconcile to the ledger;
 *  - the spread is the PLATFORM's profit (customer always gets the worse side).
 */

function seedUsdSypRate(): void
{
    // Canonical USD→SYP @ 13,000 (±1% spread): bank buys USD @12870, sells @13130.
    ExchangeRate::updateOrCreate(
        ['from_currency' => 'USD', 'to_currency' => 'SYP'],
        ['rate' => 13000, 'buy_rate' => 12870, 'sell_rate' => 13130, 'spread' => 2, 'is_active' => true, 'fetched_at' => now()],
    );
}

function makeUserWithWallets(float $usd, float $syp): array
{
    $user = User::factory()->create();

    $usdWallet = Wallet::firstOrCreate(['user_id' => $user->id, 'currency' => 'USD'], ['is_active' => true]);
    $sypWallet = Wallet::firstOrCreate(['user_id' => $user->id, 'currency' => 'SYP'], ['is_active' => true]);

    $usdWallet->forceFill(['balance' => $usd, 'available_balance' => $usd, 'is_active' => true])->save();
    $sypWallet->forceFill(['balance' => $syp, 'available_balance' => $syp, 'is_active' => true])->save();

    return [$usdWallet->refresh(), $sypWallet->refresh()];
}

it('converts USD→SYP at true scale (millions of SYP, not 13 or 135)', function () {
    seedUsdSypRate();
    [$usd, $syp] = makeUserWithWallets(100, 0);

    app(WalletService::class)->convert($usd, $syp, 100, 'usd_to_syp');

    $syp->refresh();
    // Customer sells USD → gets the BUY side (12,870). Proves 13 / 135 bug gone
    // AND that the spread favours the platform (not 13,130).
    expect((float) $syp->balance)->toBeGreaterThan(1_000_000)
        ->and((float) $syp->balance)->toBeLessThan(1_400_000)
        ->and((float) $syp->balance)->toEqual(100 * 12870.0);
});

it('derives SYP→USD from the USD→SYP row and IGNORES a stale/poisoned reverse row', function () {
    seedUsdSypRate();

    // Poison the reverse row with garbage — convert() must never read it.
    ExchangeRate::updateOrCreate(
        ['from_currency' => 'SYP', 'to_currency' => 'USD'],
        ['rate' => 999, 'buy_rate' => 999, 'sell_rate' => 999, 'spread' => 0, 'is_active' => true, 'fetched_at' => now()],
    );

    [$usd, $syp] = makeUserWithWallets(0, 13130);

    app(WalletService::class)->convert($syp, $usd, 13130, 'syp_to_usd');

    $usd->refresh();
    // 13,130 SYP ÷ 13,130 sell_rate = exactly 1.0 USD. Poisoned row would give ~13M.
    expect((float) $usd->balance)->toEqualWithDelta(1.0, 0.0001);
});

it('round-trip USD→SYP→USD loses the spread to the platform (no value evaporation, no user gain)', function () {
    seedUsdSypRate();
    [$usd, $syp] = makeUserWithWallets(100, 0);

    $service = app(WalletService::class);
    $service->convert($usd, $syp, 100, 'usd_to_syp');
    $syp->refresh();
    $service->convert($syp, $usd, (float) $syp->balance, 'syp_to_usd');
    $usd->refresh();

    // Comes back within a few percent (old reverse-row bug made this ~$0.01),
    // but strictly < $100 because the spread is the platform's profit.
    expect((float) $usd->balance)->toBeGreaterThan(95)
        ->and((float) $usd->balance)->toBeLessThan(100);
});

it('records BOTH legs of an exchange so balances reconcile to the ledger', function () {
    seedUsdSypRate();
    [$usd, $syp] = makeUserWithWallets(100, 0);

    app(WalletService::class)->convert($usd, $syp, 100, 'usd_to_syp');

    $usdTx = Transaction::where('wallet_id', $usd->id)->where('type', 'exchange')->get();
    $sypTx = Transaction::where('wallet_id', $syp->id)->where('type', 'exchange')->get();

    expect($usdTx)->toHaveCount(1)
        ->and($sypTx)->toHaveCount(1)
        ->and((float) $usdTx->first()->amount)->toEqual(-100.0)
        ->and((float) $sypTx->first()->amount)->toBeGreaterThan(0);

    // Destination balance equals its single recorded credit leg — reconciles.
    $syp->refresh();
    expect((float) $syp->balance)->toEqual((float) $sypTx->first()->amount);
});

it('captures the spread as platform profit in transaction metadata', function () {
    seedUsdSypRate();
    [$usd, $syp] = makeUserWithWallets(100, 0);

    $tx = app(WalletService::class)->convert($usd, $syp, 100, 'usd_to_syp');

    // mid 13,000, buy 12,870 → profit = 100 × (13,000 − 12,870) = 13,000 SYP.
    expect((float) $tx->metadata['spread_profit_syp'])->toEqualWithDelta(13000, 1);
});

it('records exchange spread profit in the platform_revenues treasury ledger', function () {
    seedUsdSypRate();
    [$usd, $syp] = makeUserWithWallets(100, 0);

    $tx = app(WalletService::class)->convert($usd, $syp, 100, 'usd_to_syp');

    $rev = \App\Models\PlatformRevenue::where('source', 'exchange_spread')->first();
    expect($rev)->not->toBeNull()
        ->and($rev->currency)->toBe('SYP')
        ->and((float) $rev->amount)->toEqualWithDelta(13000, 1)
        ->and($rev->transaction_id)->toBe($tx->id);
});
