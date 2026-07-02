<?php

use App\Models\Fee;
use App\Services\FeeService;
use Illuminate\Support\Collection;

beforeEach(function () {
    $this->service = app(FeeService::class);
});

it('calculates deposit USDT fee', function () {
    $fee = Fee::factory()->create([
        'code' => Fee::CODE_DEPOSIT_USDT,
        'type' => 'deposit',
        'percentage' => 1.0,
        'fixed_amount' => 0,
        'min_fee' => 0,
        'max_fee' => 10,
        'min_amount' => 10,
        'max_amount' => 10000,
        'currency' => 'USD',
        'is_active' => true,
    ]);

    $result = $this->service->calculateDepositUsdtFee(100);

    expect($result['success'])->toBeTrue();
    expect((float) $result['fee'])->toBe(1.0);
    expect((float) $result['net_amount'])->toBe(99.0);
    expect($result['fee_code'])->toBe(Fee::CODE_DEPOSIT_USDT);
});

it('calculates withdrawal USDT fee', function () {
    $fee = Fee::factory()->create([
        'code' => Fee::CODE_WITHDRAW_USDT,
        'type' => 'withdrawal',
        'percentage' => 0.5,
        'fixed_amount' => 1,
        'min_fee' => 1,
        'max_fee' => null,
        'min_amount' => 5,
        'max_amount' => null,
        'currency' => 'USD',
        'is_active' => true,
    ]);

    $result = $this->service->calculateWithdrawUsdtFee(200);

    expect($result['success'])->toBeTrue();
    expect((float) $result['fee'])->toBe(2.0); // 200 * 0.5% + 1 = 2.0, min_fee=1
    expect((float) $result['net_amount'])->toBe(198.0);
});

it('calculates card fund fee', function () {
    $fee = Fee::factory()->create([
        'code' => Fee::CODE_CARD_FUND,
        'type' => 'card_fund',
        'percentage' => 0,
        'fixed_amount' => 0.50,
        'min_fee' => 0.50,
        'max_fee' => null,
        'min_amount' => 1,
        'max_amount' => 5000,
        'currency' => 'USD',
        'is_active' => true,
    ]);

    $result = $this->service->calculateCardFundFee(100);
    expect($result['success'])->toBeTrue();
    expect((float) $result['fee'])->toBe(0.50);
});

it('calculates card creation fee', function () {
    $fee = Fee::factory()->create([
        'code' => Fee::CODE_CARD_CREATION,
        'type' => 'card_fund',
        'percentage' => 0,
        'fixed_amount' => 10.00,
        'min_fee' => 10.00,
        'max_fee' => 10.00,
        'min_amount' => 0,
        'max_amount' => null,
        'currency' => 'USD',
        'is_active' => true,
    ]);

    $result = $this->service->calculateCardCreationFee();
    expect($result['success'])->toBeTrue();
    expect($result['fee'])->toBe(10.00);
});

it('returns zero fee when fee not configured', function () {
    $result = $this->service->calculateFee('nonexistent_code', 100);

    expect($result['success'])->toBeTrue();
    expect((float) $result['fee'])->toBe(0.0);
    expect((float) $result['net_amount'])->toBe(100.0);
    expect($result['message'])->toContain('Fee not configured');
});

it('returns error when amount out of range', function () {
    Fee::factory()->create([
        'code' => Fee::CODE_DEPOSIT_USDT,
        'type' => 'deposit',
        'percentage' => 1.0,
        'fixed_amount' => 0,
        'min_fee' => 0,
        'max_fee' => null,
        'min_amount' => 10,
        'max_amount' => 100,
        'currency' => 'USD',
        'is_active' => true,
    ]);

    $result = $this->service->calculateDepositUsdtFee(200);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toBe('amount_out_of_range');
});

it('respects min fee cap', function () {
    Fee::factory()->create([
        'code' => Fee::CODE_DEPOSIT_USDT,
        'type' => 'deposit',
        'percentage' => 0.1,
        'fixed_amount' => 0,
        'min_fee' => 5,
        'max_fee' => null,
        'min_amount' => 1,
        'max_amount' => null,
        'currency' => 'USD',
        'is_active' => true,
    ]);

    // 0.1% of 100 = 0.1, but min_fee is 5
    $result = $this->service->calculateDepositUsdtFee(100);
    expect((float) $result['fee'])->toBe(5.0);
});

it('respects max fee cap', function () {
    Fee::factory()->create([
        'code' => Fee::CODE_DEPOSIT_USDT,
        'type' => 'deposit',
        'percentage' => 10,
        'fixed_amount' => 0,
        'min_fee' => 0,
        'max_fee' => 50,
        'min_amount' => 1,
        'max_amount' => null,
        'currency' => 'USD',
        'is_active' => true,
    ]);

    // 10% of 1000 = 100, but max_fee is 50
    $result = $this->service->calculateDepositUsdtFee(1000);
    expect((float) $result['fee'])->toBe(50.0);
});

it('calculates deposit fee by payment method', function () {
    Fee::factory()->create([
        'code' => Fee::CODE_DEPOSIT_USDT,
        'type' => 'deposit',
        'percentage' => 2.0,
        'fixed_amount' => 0,
        'min_fee' => 0,
        'max_fee' => null,
        'min_amount' => 1,
        'max_amount' => null,
        'currency' => 'USD',
        'is_active' => true,
    ]);

    $result = $this->service->calculateDepositFee('ccpayment', 'USDT', 100);
    expect($result['success'])->toBeTrue();
    expect($result['fee'])->toBe(2.0);
});

it('calculates withdrawal fee by payment method', function () {
    Fee::factory()->create([
        'code' => Fee::CODE_WITHDRAW_USDT,
        'type' => 'withdrawal',
        'percentage' => 1.0,
        'fixed_amount' => 0,
        'min_fee' => 0,
        'max_fee' => null,
        'min_amount' => 1,
        'max_amount' => null,
    ]);

    $result = $this->service->calculateWithdrawalFee('ccpayment', 'USDT', 50);
    expect($result['success'])->toBeTrue();
    expect($result['fee'])->toBe(0.5);
});

it('gets all fees grouped by type', function () {
    Fee::factory()->create(['code' => 'deposit_usdt', 'type' => 'deposit']);
    Fee::factory()->create(['code' => 'withdraw_usdt', 'type' => 'withdrawal']);
    Fee::factory()->create(['code' => 'card_creation', 'type' => 'card_fund']);

    $grouped = $this->service->getAllFeesGrouped();

    expect($grouped)->toBeInstanceOf(Collection::class);
    expect($grouped->keys())->toContain('deposit', 'withdrawal', 'card_fund');
});

it('gets fees by type', function () {
    Fee::factory()->create(['code' => 'deposit_usdt', 'type' => 'deposit']);
    Fee::factory()->create(['code' => 'deposit_usdt_2', 'type' => 'deposit']);

    $fees = $this->service->getFeesByType('deposit');
    expect($fees)->toBeInstanceOf(Collection::class);
    expect($fees->count())->toBe(2);
});

it('updates a fee by code', function () {
    Fee::factory()->create([
        'code' => Fee::CODE_DEPOSIT_USDT,
        'percentage' => 1.0,
    ]);

    $updated = $this->service->updateFee(Fee::CODE_DEPOSIT_USDT, ['percentage' => 2.5]);

    expect($updated)->not->toBeNull();
    expect((float) $updated->percentage)->toBe(2.5);
});

it('returns null when updating nonexistent fee', function () {
    $result = $this->service->updateFee('nonexistent', ['percentage' => 5]);
    expect($result)->toBeNull();
});

it('toggles fee active status', function () {
    $fee = Fee::factory()->create([
        'code' => Fee::CODE_DEPOSIT_USDT,
        'is_active' => true,
    ]);

    $toggled = $this->service->toggleFeeStatus(Fee::CODE_DEPOSIT_USDT);
    expect($toggled->is_active)->toBeFalse();

    $toggledAgain = $this->service->toggleFeeStatus(Fee::CODE_DEPOSIT_USDT);
    expect($toggledAgain->is_active)->toBeTrue();
});

it('returns null when toggling nonexistent fee', function () {
    $result = $this->service->toggleFeeStatus('nonexistent');
    expect($result)->toBeNull();
});

it('generates fee preview for UI', function () {
    Fee::factory()->create([
        'code' => 'deposit_usdt',
        'type' => 'deposit',
        'name' => 'Deposit USDT',
        'percentage' => 1.0,
        'fixed_amount' => 0,
        'min_fee' => 0,
        'max_fee' => null,
        'min_amount' => 10,
        'max_amount' => 10000,
        'currency' => 'USD',
        'is_active' => true,
    ]);

    $preview = $this->service->getFeePreview(200);

    expect($preview)->toHaveKey('deposit_usdt');
    expect($preview['deposit_usdt']['example']['amount'])->toBe(200.0);
    expect($preview['deposit_usdt']['structure']['percentage'])->toBe('1%');
});
