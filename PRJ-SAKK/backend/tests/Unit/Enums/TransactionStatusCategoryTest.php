<?php

use App\Enums\TransactionStatus;
use App\Enums\TransactionCategory;

// TransactionStatus
it('TransactionStatus has all required cases', function () {
    expect(TransactionStatus::cases())->toHaveCount(7);
    expect(TransactionStatus::PENDING->value)->toBe('pending');
    expect(TransactionStatus::PROCESSING->value)->toBe('processing');
    expect(TransactionStatus::COMPLETED->value)->toBe('completed');
    expect(TransactionStatus::FAILED->value)->toBe('failed');
    expect(TransactionStatus::CANCELLED->value)->toBe('cancelled');
    expect(TransactionStatus::REVERSED->value)->toBe('reversed');
    expect(TransactionStatus::REFUNDED->value)->toBe('refunded');
});

it('TransactionStatus labels are correct', function () {
    expect(TransactionStatus::PENDING->label())->toBe('Pending');
    expect(TransactionStatus::COMPLETED->label())->toBe('Completed');
    expect(TransactionStatus::FAILED->label())->toBe('Failed');
});

it('TransactionStatus Arabic labels are not empty', function () {
    foreach (TransactionStatus::cases() as $case) {
        expect($case->labelAr())->not->toBeEmpty();
    }
});

it('TransactionStatus isCompleted works', function () {
    expect(TransactionStatus::COMPLETED->isCompleted())->toBeTrue();
    expect(TransactionStatus::PENDING->isCompleted())->toBeFalse();
    expect(TransactionStatus::FAILED->isCompleted())->toBeFalse();
});

// TransactionCategory
it('TransactionCategory has all required cases', function () {
    expect(TransactionCategory::cases())->toHaveCount(11);
    expect(TransactionCategory::PAYROLL->value)->toBe('payroll');
    expect(TransactionCategory::WALLET->value)->toBe('wallet');
    expect(TransactionCategory::CARD->value)->toBe('card');
    expect(TransactionCategory::CRYPTO->value)->toBe('crypto');
    expect(TransactionCategory::EXCHANGE->value)->toBe('exchange');
    expect(TransactionCategory::P2P->value)->toBe('p2p');
    expect(TransactionCategory::FEE->value)->toBe('fee');
    expect(TransactionCategory::REWARD->value)->toBe('reward');
    expect(TransactionCategory::ADJUSTMENT->value)->toBe('adjustment');
});

it('TransactionCategory labels are correct', function () {
    expect(TransactionCategory::WALLET->label())->toBe('Wallet');
    expect(TransactionCategory::CARD->label())->toBe('Card');
});

it('TransactionCategory Arabic labels are not empty', function () {
    foreach (TransactionCategory::cases() as $case) {
        expect($case->labelAr())->not->toBeEmpty();
    }
});
