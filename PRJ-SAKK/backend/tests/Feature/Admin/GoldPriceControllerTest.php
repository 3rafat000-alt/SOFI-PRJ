<?php

use App\Models\GoldPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Regression guard for the /admin/gold hardening (31c69d5 + ec90e58):
 *  - toggleActive() must flip ONLY is_active, never touch source/buy/sell/spread
 *    (previously a hand toggle silently pinned source='manual', detaching the
 *    karat from auto-sync).
 *  - update() must reject buy_price < sell_price (a wallet holding gold sold
 *    below its buy price is a guaranteed loss on every trade).
 */

function goldAdmin(): User
{
    return User::factory()->create(['is_admin' => true]);
}

function makeGoldPrice(array $overrides = []): GoldPrice
{
    return GoldPrice::create(array_merge([
        'karat' => '24',
        'buy_price' => 100.00,
        'sell_price' => 95.00,
        'spread' => 5.26,
        'source' => 'auto',
        'is_active' => true,
    ], $overrides));
}

// ── toggleActive ──

it('toggleActive flips is_active from true to false', function () {
    $admin = goldAdmin();
    $price = makeGoldPrice(['is_active' => true]);

    $this->actingAs($admin)
        ->post(route('admin.gold.price.toggle', $price))
        ->assertRedirect();

    expect($price->fresh()->is_active)->toBeFalse();
});

it('toggleActive flips is_active from false to true', function () {
    $admin = goldAdmin();
    $price = makeGoldPrice(['is_active' => false]);

    $this->actingAs($admin)
        ->post(route('admin.gold.price.toggle', $price))
        ->assertRedirect();

    expect($price->fresh()->is_active)->toBeTrue();
});

it('toggleActive on an auto-sourced karat leaves source, prices and spread untouched (core regression)', function () {
    $admin = goldAdmin();
    $price = makeGoldPrice([
        'source' => 'auto',
        'buy_price' => 123.45,
        'sell_price' => 120.00,
        'spread' => 2.88,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.gold.price.toggle', $price))
        ->assertRedirect();

    $fresh = $price->fresh();
    expect($fresh->source)->toBe('auto');
    expect((float) $fresh->buy_price)->toBe(123.45);
    expect((float) $fresh->sell_price)->toBe(120.00);
    expect((float) $fresh->spread)->toBe(2.88);
    expect($fresh->is_active)->toBeFalse();
});

// ── update ──

it('update rejects buy_price less than sell_price and leaves the row unchanged', function () {
    $admin = goldAdmin();
    $price = makeGoldPrice([
        'source' => 'auto',
        'buy_price' => 100.00,
        'sell_price' => 95.00,
        'spread' => 5.26,
    ]);

    $this->actingAs($admin)
        ->put(route('admin.gold.price.update', $price), [
            'buy_price' => 90.00,
            'sell_price' => 95.00,
            'is_active' => true,
        ])
        ->assertSessionHasErrors('buy_price');

    $fresh = $price->fresh();
    expect((float) $fresh->buy_price)->toBe(100.00);
    expect((float) $fresh->sell_price)->toBe(95.00);
    expect((float) $fresh->spread)->toBe(5.26);
    expect($fresh->source)->toBe('auto');
});

it('update accepts buy_price greater than or equal to sell_price, sets source manual and recomputes spread', function () {
    $admin = goldAdmin();
    $price = makeGoldPrice([
        'source' => 'auto',
        'buy_price' => 100.00,
        'sell_price' => 95.00,
        'spread' => 5.26,
    ]);

    $this->actingAs($admin)
        ->put(route('admin.gold.price.update', $price), [
            'buy_price' => 110.00,
            'sell_price' => 105.00,
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.gold.index'));

    $fresh = $price->fresh();
    expect((float) $fresh->buy_price)->toBe(110.00);
    expect((float) $fresh->sell_price)->toBe(105.00);
    expect($fresh->source)->toBe('manual');
    expect((float) $fresh->spread)->toBe(round((110.00 - 105.00) / 105.00 * 100, 2));
});

it('update accepts buy_price exactly equal to sell_price (gte boundary)', function () {
    $admin = goldAdmin();
    $price = makeGoldPrice();

    $this->actingAs($admin)
        ->put(route('admin.gold.price.update', $price), [
            'buy_price' => 100.00,
            'sell_price' => 100.00,
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.gold.index'));

    $fresh = $price->fresh();
    expect((float) $fresh->buy_price)->toBe(100.00);
    expect((float) $fresh->sell_price)->toBe(100.00);
    expect($fresh->source)->toBe('manual');
});

// ── guard: non-admin / guest blocked ──

it('guest hitting toggleActive is redirected to admin login, DB unchanged', function () {
    $price = makeGoldPrice(['is_active' => true]);

    $this->post(route('admin.gold.price.toggle', $price))
        ->assertRedirect(route('login'));

    expect($price->fresh()->is_active)->toBeTrue();
});

it('non-admin user hitting update is redirected to admin login, DB unchanged', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $price = makeGoldPrice(['buy_price' => 100.00, 'sell_price' => 95.00]);

    $this->actingAs($user)
        ->put(route('admin.gold.price.update', $price), [
            'buy_price' => 200.00,
            'sell_price' => 50.00,
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.login'));

    expect((float) $price->fresh()->buy_price)->toBe(100.00);
});
