<?php

use App\Enums\CardBrand;
use App\Enums\CardStatus;
use App\Enums\CardType;

// CardBrand
it('CardBrand has required cases', function () {
    expect(CardBrand::cases())->toHaveCount(2);
    expect(CardBrand::VISA->value)->toBe('visa');
    expect(CardBrand::MASTERCARD->value)->toBe('mastercard');
});

it('CardBrand labels are correct', function () {
    expect(CardBrand::VISA->label())->toBe('Visa');
    expect(CardBrand::MASTERCARD->label())->toBe('Mastercard');
});

// CardStatus
it('CardStatus has required cases', function () {
    expect(CardStatus::cases())->toHaveCount(5);
    expect(CardStatus::ACTIVE->value)->toBe('active');
    expect(CardStatus::FROZEN->value)->toBe('frozen');
    expect(CardStatus::EXPIRED->value)->toBe('expired');
    expect(CardStatus::CANCELLED->value)->toBe('cancelled');
    expect(CardStatus::PENDING->value)->toBe('pending');
});

it('CardStatus labels are correct', function () {
    expect(CardStatus::ACTIVE->label())->toBe('Active');
    expect(CardStatus::FROZEN->label())->toBe('Frozen');
    expect(CardStatus::EXPIRED->label())->toBe('Expired');
    expect(CardStatus::CANCELLED->label())->toBe('Cancelled');
    expect(CardStatus::PENDING->label())->toBe('Pending Activation');
});

it('CardStatus Arabic labels are not empty', function () {
    foreach (CardStatus::cases() as $case) {
        expect($case->labelAr())->not->toBeEmpty();
    }
});

it('CardStatus isActive works correctly', function () {
    expect(CardStatus::ACTIVE->isActive())->toBeTrue();
    expect(CardStatus::FROZEN->isActive())->toBeFalse();
    expect(CardStatus::CANCELLED->isActive())->toBeFalse();
});

// CardType
it('CardType has required cases', function () {
    expect(CardType::cases())->toHaveCount(2);
    expect(CardType::VIRTUAL->value)->toBe('virtual');
    expect(CardType::PHYSICAL->value)->toBe('physical');
});

it('CardType labels are correct', function () {
    expect(CardType::VIRTUAL->label())->toBe('Virtual Card');
    expect(CardType::PHYSICAL->label())->toBe('Physical Card');
});
