<?php

use App\Enums\KycStatus;
use App\Models\Fee;
use App\Models\GoldPrice;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function goldUser(float $usd = 500): User
{
    $user = User::factory()->create([
        'kyc_status' => KycStatus::VERIFIED,
        'pin_code' => bcrypt('123456'),
    ]);

    $user->wallets()->where('currency', 'USD')->update([
        'balance' => $usd,
        'available_balance' => $usd,
        'pending_balance' => 0,
    ]);

    GoldPrice::updateOrCreate(['karat' => '24'], [
        'buy_price' => 89.50,
        'sell_price' => 88.20,
        'spread' => 1.47,
        'is_active' => true,
    ]);

    Fee::updateOrCreate(
        ['code' => Fee::CODE_GOLD_BUY],
        ['percentage' => 1.0, 'fixed_amount' => 0, 'min_fee' => 0, 'max_fee' => null, 'min_amount' => 0, 'max_amount' => null, 'is_active' => true, 'type' => Fee::TYPE_GOLD, 'currency' => 'USD', 'name_ar' => 'رسوم شراء الذهب', 'name_en' => 'Gold Buy Fee', 'sort_order' => 1]
    );
    Fee::updateOrCreate(
        ['code' => Fee::CODE_GOLD_SELL],
        ['percentage' => 0.5, 'fixed_amount' => 0, 'min_fee' => 0, 'max_fee' => null, 'min_amount' => 0, 'max_amount' => null, 'is_active' => true, 'type' => Fee::TYPE_GOLD, 'currency' => 'USD', 'name_ar' => 'رسوم بيع الذهب', 'name_en' => 'Gold Sell Fee', 'sort_order' => 2]
    );

    return $user;
}

it('returns gold prices for all karats', function () {
    $user = goldUser();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/gold/prices');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            '*' => ['karat', 'karat_label', 'purity', 'buy_price', 'sell_price', 'spread'],
        ],
    ]);
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.karat'))->toBe('24');
});

it('returns empty gold wallet for new user', function () {
    $user = goldUser();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/gold/wallet');

    $response->assertStatus(200);
    expect((float) $response->json('data.balance_grams'))->toBe(0.0);
    expect((float) $response->json('data.current_value_usd'))->toBe(0.0);
});

it('buys gold and deducts USD', function () {
    $user = goldUser(usd: 1000);
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/gold/buy', [
        'karat' => '24',
        'grams' => 10,
        'pin' => '123456',
    ]);

    $response->assertStatus(200);
    $data = $response->json('data');
    expect((float) $data['grams'])->toBe(10.0);
    expect($data['karat'])->toBe('24');
    expect($data['reference'])->toMatch('/^GLD-/');

    // USD deducted: 10 * 89.50 + 1% fee = 895 + 8.95 = 903.95
    $expectedTotal = round(10 * 89.50 * 1.01, 2);
    expect((float) $data['total_paid_usd'])->toBe($expectedTotal);
});

it('rejects gold buy with insufficient balance', function () {
    $user = goldUser(usd: 50);
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/gold/buy', [
        'karat' => '24',
        'grams' => 10,
        'pin' => '123456',
    ]);

    $response->assertStatus(422);
    expect($response->json('message'))->toContain('رصيد غير كافٍ');
});

it('rejects gold buy with wrong PIN', function () {
    $user = goldUser(usd: 1000);
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/gold/buy', [
        'karat' => '24',
        'grams' => 1,
        'pin' => '000000',
    ]);

    $response->assertStatus(422);
    expect($response->json('message'))->toContain('PIN');
});

it('sells gold and credits USD', function () {
    $user = goldUser(usd: 1000);
    Sanctum::actingAs($user);

    // First buy
    $this->postJson('/api/v1/gold/buy', [
        'karat' => '24',
        'grams' => 10,
        'pin' => '123456',
    ])->assertStatus(200);

    // Then sell
    $response = $this->postJson('/api/v1/gold/sell', [
        'karat' => '24',
        'grams' => 5,
        'pin' => '123456',
    ]);

    $response->assertStatus(200);
    $data = $response->json('data');
    expect((float) $data['grams'])->toBe(5.0);
    expect($data['karat'])->toBe('24');
    expect($data['reference'])->toMatch('/^GLD-/');

    // Remaining balance: 10 - 5 = 5 grams
    expect((float) $data['new_balance_grams'])->toBe(5.0);
});

it('returns gold transaction history', function () {
    $user = goldUser(usd: 1000);
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/gold/buy', [
        'karat' => '24',
        'grams' => 5,
        'pin' => '123456',
    ])->assertStatus(200);

    $response = $this->getJson('/api/v1/gold/transactions');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
    $response->assertJsonStructure([
        'data' => [
            '*' => ['reference', 'type', 'karat', 'grams', 'total_usd', 'status', 'created_at'],
        ],
        'current_page', 'last_page', 'total',
    ]);
});

it('returns gold statistics', function () {
    $user = goldUser(usd: 1000);
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/gold/buy', [
        'karat' => '24',
        'grams' => 10,
        'pin' => '123456',
    ])->assertStatus(200);

    $response = $this->getJson('/api/v1/gold/stats');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => ['current_grams', 'current_value_usd', 'total_invested_usd', 'total_bought_usd', 'total_sold_usd', 'total_fees_paid_usd', 'total_transactions'],
    ]);
});

it('blocks karat arbitrage — cannot sell 24k declared when only 18k is held', function () {
    $user = goldUser(usd: 1000);
    Sanctum::actingAs($user);

    GoldPrice::updateOrCreate(['karat' => '18'], [
        'buy_price' => 67.00,
        'sell_price' => 66.00,
        'spread' => 1.5,
        'is_active' => true,
    ]);

    // Buy 10 grams of 18k (cheap karat).
    $this->postJson('/api/v1/gold/buy', [
        'karat' => '18',
        'grams' => 10,
        'pin' => '123456',
    ])->assertStatus(200);

    // Attempt to sell declaring 24k (higher sell price) — must be rejected,
    // the user only holds 18k grams.
    $response = $this->postJson('/api/v1/gold/sell', [
        'karat' => '24',
        'grams' => 10,
        'pin' => '123456',
    ]);

    $response->assertStatus(422);
    expect($response->json('message'))->toContain('رصيد ذهب غير كافٍ');

    // Selling the correct karat (18k) succeeds.
    $response = $this->postJson('/api/v1/gold/sell', [
        'karat' => '18',
        'grams' => 10,
        'pin' => '123456',
    ]);
    $response->assertStatus(200);
});

it('rejects gold sell when fee would exceed or equal proceeds', function () {
    $user = goldUser(usd: 1000);
    Sanctum::actingAs($user);

    Fee::updateOrCreate(
        ['code' => Fee::CODE_GOLD_SELL],
        ['percentage' => 0, 'fixed_amount' => 1000, 'min_fee' => 0, 'max_fee' => null, 'min_amount' => 0, 'max_amount' => null, 'is_active' => true, 'type' => Fee::TYPE_GOLD, 'currency' => 'USD', 'name_ar' => 'رسوم بيع الذهب', 'name_en' => 'Gold Sell Fee', 'sort_order' => 2]
    );

    $this->postJson('/api/v1/gold/buy', [
        'karat' => '24',
        'grams' => 5,
        'pin' => '123456',
    ])->assertStatus(200);

    $response = $this->postJson('/api/v1/gold/sell', [
        'karat' => '24',
        'grams' => 5,
        'pin' => '123456',
    ]);

    $response->assertStatus(422);
});

it('rejects gold buy when the USD wallet is frozen without crediting gold', function () {
    $user = goldUser(usd: 1000);
    $user->wallets()->where('currency', 'USD')->update(['is_frozen' => true]);
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/gold/buy', [
        'karat' => '24',
        'grams' => 5,
        'pin' => '123456',
    ]);

    $response->assertStatus(422);

    $walletResponse = $this->getJson('/api/v1/gold/wallet');
    expect((float) $walletResponse->json('data.balance_grams'))->toBe(0.0);
});

it('values mixed-karat holdings per-karat sell price, not a blended average', function () {
    $user = goldUser(usd: 5000);
    Sanctum::actingAs($user);

    // 24k sell_price = 88.20 (set by goldUser()).
    GoldPrice::updateOrCreate(['karat' => '18'], [
        'buy_price' => 67.00,
        'sell_price' => 66.00,
        'spread' => 1.5,
        'is_active' => true,
    ]);

    $this->postJson('/api/v1/gold/buy', [
        'karat' => '24',
        'grams' => 10,
        'pin' => '123456',
    ])->assertStatus(200);

    $this->postJson('/api/v1/gold/buy', [
        'karat' => '18',
        'grams' => 10,
        'pin' => '123456',
    ])->assertStatus(200);

    // Correct per-karat sum: 10*88.20 + 10*66.00 = 1542.00
    // Old blended-average bug would have valued at 20 * avg(88.20, 66.00) = 1542.00 too
    // by coincidence of equal grams — so make grams unequal to expose the bug.
    $this->postJson('/api/v1/gold/buy', [
        'karat' => '24',
        'grams' => 5,
        'pin' => '123456',
    ])->assertStatus(200);

    // Now: 24k = 15g, 18k = 10g.
    // Correct value = 15*88.20 + 10*66.00 = 1323.00 + 660.00 = 1983.00
    // Blended-average bug would compute (15+10) * avg(88.20,66.00)
    //   = 25 * 77.10 = 1927.50 — different from the correct value.
    $expectedValue = round(15 * 88.20 + 10 * 66.00, 2);

    $walletResponse = $this->getJson('/api/v1/gold/wallet');
    $walletResponse->assertStatus(200);
    expect((float) $walletResponse->json('data.current_value_usd'))->toBe($expectedValue);

    $statsResponse = $this->getJson('/api/v1/gold/stats');
    $statsResponse->assertStatus(200);
    expect((float) $statsResponse->json('data.current_value_usd'))->toBe($expectedValue);
});

it('excludes karats with no active price from wallet valuation', function () {
    $user = goldUser(usd: 1000);
    Sanctum::actingAs($user);

    GoldPrice::updateOrCreate(['karat' => '18'], [
        'buy_price' => 67.00,
        'sell_price' => 66.00,
        'spread' => 1.5,
        'is_active' => true,
    ]);

    $this->postJson('/api/v1/gold/buy', [
        'karat' => '18',
        'grams' => 10,
        'pin' => '123456',
    ])->assertStatus(200);

    // Deactivate the 18k price after the buy — the holding still exists
    // with grams, but has no live sell_price to mark-to-market against.
    GoldPrice::where('karat', '18')->update(['is_active' => false]);

    $response = $this->getJson('/api/v1/gold/wallet');
    $response->assertStatus(200);
    // Grams are still owned (balance_grams unaffected) but contribute 0
    // to current_value_usd since there is no active price for that karat.
    expect((float) $response->json('data.balance_grams'))->toBe(10.0);
    expect((float) $response->json('data.current_value_usd'))->toBe(0.0);
});

it('requires authentication for gold endpoints', function () {
    $this->getJson('/api/v1/gold/prices')->assertStatus(401);
    $this->getJson('/api/v1/gold/wallet')->assertStatus(401);
    $this->postJson('/api/v1/gold/buy')->assertStatus(401);
    $this->postJson('/api/v1/gold/sell')->assertStatus(401);
    $this->getJson('/api/v1/gold/transactions')->assertStatus(401);
    $this->getJson('/api/v1/gold/stats')->assertStatus(401);
});
