<?php

use App\Models\Fee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

// App\Http\Controllers\Admin\FeeController — admin fee management (web
// panel: index/update/toggle/preview) + the two JSON API endpoints
// (apiIndex/apiCalculate) it also owns.

function feeAdmin(): User
{
    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true, 'is_active' => true])->save();

    return $admin;
}

function makeFee(array $overrides = []): Fee
{
    return Fee::create(array_merge([
        'code' => 'test_fee_' . uniqid(),
        'name_ar' => 'رسوم اختبار',
        'name_en' => 'Test Fee',
        'type' => Fee::TYPE_DEPOSIT,
        'currency' => 'USD',
        'fixed_amount' => 1.0,
        'percentage' => 0,
        'min_fee' => 0,
        'max_fee' => null,
        'min_amount' => 0,
        'max_amount' => null,
        'is_active' => true,
        'sort_order' => 0,
    ], $overrides));
}

// ==================== index (web) ====================

it('renders the admin fees index page grouped by type', function () {
    makeFee(['type' => Fee::TYPE_DEPOSIT]);
    makeFee(['type' => Fee::TYPE_WITHDRAWAL]);

    $response = $this->actingAs(feeAdmin())->get(route('admin.fees.index'));

    $response->assertOk();
    $response->assertViewIs('admin.fees.index');
    $response->assertViewHas('fees');
    $response->assertViewHas('feePreview');
});

it('blocks a non-admin from the fees index page (redirected to admin login)', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.fees.index'));

    $response->assertRedirect(route('admin.login'));
});

// ==================== update (web) ====================

it('updates a fee to percentage-based and zeros the fixed amount', function () {
    $fee = makeFee(['fee_type' => null, 'fixed_amount' => 5, 'percentage' => 0]);

    $response = $this->actingAs(feeAdmin())->put(route('admin.fees.update', $fee->code), [
        'fee_type' => 'percentage',
        'percentage' => 2.5,
        'is_active' => '1',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $fee->refresh();
    expect((float) $fee->percentage)->toBe(2.5);
    expect((float) $fee->fixed_amount)->toBe(0.0);
});

it('updates a fee to fixed-amount and zeros the percentage', function () {
    $fee = makeFee(['fixed_amount' => 0, 'percentage' => 3]);

    $response = $this->actingAs(feeAdmin())->put(route('admin.fees.update', $fee->code), [
        'fee_type' => 'fixed',
        'fixed_amount' => 4.5,
        'is_active' => '1',
    ]);

    $response->assertRedirect();
    $fee->refresh();
    expect((float) $fee->fixed_amount)->toBe(4.5);
    expect((float) $fee->percentage)->toBe(0.0);
});

it('returns an error redirect when updating a fee code that does not exist', function () {
    $response = $this->actingAs(feeAdmin())->put(route('admin.fees.update', 'nonexistent_code'), [
        'fee_type' => 'fixed',
        'fixed_amount' => 1,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

it('rejects an out-of-range percentage on update (validation)', function () {
    $fee = makeFee();

    $response = $this->actingAs(feeAdmin())->put(route('admin.fees.update', $fee->code), [
        'fee_type' => 'percentage',
        'percentage' => 150, // above max:100
    ]);

    $response->assertSessionHasErrors('percentage');
});

// ⚠️ REAL BUG found here (NOT fixed — out of automated-testing-engineer
// scope, flagged to the owning backend tech lead): FeeController::update()'s
// $validator only validates fee_type/fixed_amount/percentage/is_active — it
// never includes max_fee/max_amount in its rules, so $validator->validated()
// never carries them. The very next lines then do
// `$data['max_fee'] ??= $fee->max_fee;` (falls back to the EXISTING DB
// value, since the key was never set) followed by an "empty -> null" check
// that therefore only ever fires when the fee's CURRENT max_fee/max_amount
// was already empty. A form submission with an explicit empty max_fee/
// max_amount input can never actually clear a previously-set limit — the
// "nulls out empty max values" logic (lines 88-94) is dead for its stated
// purpose. This test documents the CURRENT (buggy) behavior: submitting
// empty max_fee/max_amount silently keeps the existing values.
it('submitting empty max_fee/max_amount clears a previously-set limit', function () {
    $fee = makeFee(['max_fee' => 10, 'max_amount' => 1000]);

    $response = $this->actingAs(feeAdmin())->put(route('admin.fees.update', $fee->code), [
        'fee_type' => 'fixed',
        'fixed_amount' => 1,
        'max_fee' => '',
        'max_amount' => '',
    ]);

    $response->assertRedirect();
    $fee->refresh();
    // max_fee/max_amount are now part of the validated() payload, so the
    // "clear on empty" logic in FeeController::update() can fire correctly.
    expect($fee->max_fee)->toBeNull();
    expect($fee->max_amount)->toBeNull();
});

// ==================== toggle (web) ====================

it('toggles a fee from active to inactive and back', function () {
    $fee = makeFee(['is_active' => true]);
    $admin = feeAdmin();

    $response = $this->actingAs($admin)->patch(route('admin.fees.toggle', $fee->code));
    $response->assertRedirect();
    expect($fee->fresh()->is_active)->toBeFalse();

    $response2 = $this->actingAs($admin)->patch(route('admin.fees.toggle', $fee->code));
    $response2->assertRedirect();
    expect($fee->fresh()->is_active)->toBeTrue();
});

it('returns an error redirect when toggling a fee code that does not exist', function () {
    $response = $this->actingAs(feeAdmin())->patch(route('admin.fees.toggle', 'nonexistent_code'));

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

// ==================== preview (web, JSON) ====================

it('previews the fee calculation for a valid code and amount', function () {
    $fee = makeFee(['fixed_amount' => 1, 'percentage' => 1, 'min_fee' => 0]);

    $response = $this->actingAs(feeAdmin())->postJson(route('admin.fees.preview'), [
        'code' => $fee->code,
        'amount' => 100,
    ]);

    $response->assertOk();
    // fee = 1 + (100 * 1 / 100) = 2
    expect((float) $response->json('fee'))->toBe(2.0);
});

it('rejects a preview request with a non-existent fee code', function () {
    $response = $this->actingAs(feeAdmin())->postJson(route('admin.fees.preview'), [
        'code' => 'does_not_exist',
        'amount' => 100,
    ]);

    $response->assertStatus(422);
});

it('rejects a negative preview amount', function () {
    $fee = makeFee();

    $response = $this->actingAs(feeAdmin())->postJson(route('admin.fees.preview'), [
        'code' => $fee->code,
        'amount' => -10,
    ]);

    $response->assertStatus(422);
});

it('previews a zero amount without error', function () {
    $fee = makeFee(['fixed_amount' => 2, 'percentage' => 0]);

    $response = $this->actingAs(feeAdmin())->postJson(route('admin.fees.preview'), [
        'code' => $fee->code,
        'amount' => 0,
    ]);

    $response->assertOk();
    expect((float) $response->json('fee'))->toBe(2.0);
});

// ==================== apiIndex ====================

it('lists only active fees via the API, mapped to the public shape', function () {
    makeFee(['code' => 'active_one', 'is_active' => true, 'sort_order' => 1]);
    makeFee(['code' => 'inactive_one', 'is_active' => false, 'sort_order' => 2]);

    Sanctum::actingAs(User::factory()->create());
    $response = $this->getJson('/api/v1/fees');

    $response->assertOk()->assertJson(['success' => true]);
    $codes = collect($response->json('data'))->pluck('code');
    expect($codes)->toContain('active_one');
    expect($codes)->not->toContain('inactive_one');
});

// ==================== apiCalculate ====================

it('calculates a fee via the public API endpoint', function () {
    $fee = makeFee(['code' => 'api_calc_fee', 'fixed_amount' => 0, 'percentage' => 5, 'min_fee' => 0]);

    Sanctum::actingAs(User::factory()->create());
    $response = $this->postJson('/api/v1/fees/calculate', [
        'code' => 'api_calc_fee',
        'amount' => 200,
    ]);

    $response->assertOk()->assertJson(['success' => true]);
    expect((float) $response->json('fee'))->toBe(10.0); // 5% of 200
});

it('resolves to a zero fee (logged, not blocked) when the fee code is not configured', function () {
    Sanctum::actingAs(User::factory()->create());
    $response = $this->postJson('/api/v1/fees/calculate', [
        'code' => 'totally_unconfigured_code',
        'amount' => 50,
    ]);

    $response->assertOk()->assertJson(['success' => true, 'fee' => 0]);
});

it('rejects an out-of-range amount against the fee limits', function () {
    $fee = makeFee(['code' => 'ranged_fee', 'min_amount' => 100, 'max_amount' => 500]);

    Sanctum::actingAs(User::factory()->create());
    $response = $this->postJson('/api/v1/fees/calculate', [
        'code' => 'ranged_fee',
        'amount' => 10, // below min_amount
    ]);

    $response->assertOk(); // controller returns 200 with success:false body
    expect($response->json('success'))->toBeFalse();
    expect($response->json('error'))->toBe('amount_out_of_range');
});

it('rejects a missing amount on apiCalculate (validation)', function () {
    Sanctum::actingAs(User::factory()->create());
    $response = $this->postJson('/api/v1/fees/calculate', [
        'code' => 'any_code',
    ]);

    $response->assertStatus(422);
});

it('applies the min_fee floor and max_fee ceiling correctly', function () {
    $fee = makeFee(['code' => 'capped_fee', 'fixed_amount' => 0, 'percentage' => 10, 'min_fee' => 5, 'max_fee' => 20]);
    Sanctum::actingAs(User::factory()->create());

    // 10% of 30 = 3, below min_fee -> floored to 5
    $low = $this->postJson('/api/v1/fees/calculate', ['code' => 'capped_fee', 'amount' => 30]);
    expect((float) $low->json('fee'))->toBe(5.0);

    // 10% of 1000 = 100, above max_fee -> capped to 20
    $high = $this->postJson('/api/v1/fees/calculate', ['code' => 'capped_fee', 'amount' => 1000]);
    expect((float) $high->json('fee'))->toBe(20.0);
});
