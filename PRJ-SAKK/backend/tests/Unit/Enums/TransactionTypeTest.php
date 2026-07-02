<?php

use App\Enums\TransactionType;

it('TransactionType has all required cases', function () {
    expect(TransactionType::cases())->toHaveCount(14);
    expect(TransactionType::EXCHANGE->value)->toBe('exchange');
    expect(TransactionType::TRANSFER_OUT->value)->toBe('transfer_out');
    expect(TransactionType::TRANSFER_IN->value)->toBe('transfer_in');
    expect(TransactionType::PAYROLL_OUT->value)->toBe('payroll_out');
    expect(TransactionType::SALARY_IN->value)->toBe('salary_in');
});

it('TransactionType payroll types classify correctly', function () {
    expect(TransactionType::SALARY_IN->isCredit())->toBeTrue();
    expect(TransactionType::PAYROLL_OUT->isDebit())->toBeTrue();
});

it('TransactionType credit types are correct', function () {
    expect(TransactionType::DEPOSIT->isCredit())->toBeTrue();
    expect(TransactionType::CARD_UNLOAD->isCredit())->toBeTrue();
    expect(TransactionType::CARD_REFUND->isCredit())->toBeTrue();
    expect(TransactionType::REWARD->isCredit())->toBeTrue();
});

it('TransactionType debit types are correct', function () {
    expect(TransactionType::WITHDRAWAL->isDebit())->toBeTrue();
    expect(TransactionType::CARD_LOAD->isDebit())->toBeTrue();
    expect(TransactionType::CARD_PAYMENT->isDebit())->toBeTrue();
    expect(TransactionType::FEE->isDebit())->toBeTrue();
});

it('TransactionType isCredit and isDebit are mutually exclusive for non-dual types', function () {
    $creditTypes = [TransactionType::DEPOSIT, 
                    TransactionType::CARD_UNLOAD, TransactionType::CARD_REFUND, TransactionType::REWARD];
    $debitTypes = [TransactionType::WITHDRAWAL,
                   TransactionType::CARD_LOAD, TransactionType::CARD_PAYMENT, TransactionType::FEE];
    
    foreach ($creditTypes as $type) {
        expect($type->isDebit())->toBeFalse("{$type->value} should not be debit");
    }
    foreach ($debitTypes as $type) {
        expect($type->isCredit())->toBeFalse("{$type->value} should not be credit");
    }
});

it('TransactionType English labels are not empty', function () {
    foreach (TransactionType::cases() as $case) {
        expect($case->label())->not->toBeEmpty();
    }
});

it('TransactionType Arabic labels are not empty', function () {
    foreach (TransactionType::cases() as $case) {
        expect($case->labelAr())->not->toBeEmpty();
    }
});

it('TransactionType icons are not empty', function () {
    foreach (TransactionType::cases() as $case) {
        expect($case->icon())->not->toBeEmpty();
    }
});
