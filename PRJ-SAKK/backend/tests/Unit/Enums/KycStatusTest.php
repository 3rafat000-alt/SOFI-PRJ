<?php

use App\Enums\KycStatus;

it('KycStatus has all required cases', function () {
    expect(KycStatus::cases())->toHaveCount(4);
    expect(KycStatus::PENDING->value)->toBe('pending');
    expect(KycStatus::SUBMITTED->value)->toBe('submitted');
    expect(KycStatus::VERIFIED->value)->toBe('verified');
    expect(KycStatus::REJECTED->value)->toBe('rejected');
});

it('KycStatus labels are correct', function () {
    expect(KycStatus::PENDING->label())->toBe('Unverified');
    expect(KycStatus::SUBMITTED->label())->toBe('Under Review');
    expect(KycStatus::VERIFIED->label())->toBe('Verified');
    expect(KycStatus::REJECTED->label())->toBe('Rejected');
});

it('KycStatus Arabic labels are correct', function () {
    expect(KycStatus::PENDING->labelAr())->toBe('غير موثّق');
    expect(KycStatus::SUBMITTED->labelAr())->toBe('قيد المراجعة');
    expect(KycStatus::VERIFIED->labelAr())->toBe('موثّق');
    expect(KycStatus::REJECTED->labelAr())->toBe('مرفوض');
});

it('KycStatus isVerified returns correct mapping', function () {
    expect(KycStatus::VERIFIED->isVerified())->toBeTrue();
    expect(KycStatus::PENDING->isVerified())->toBeFalse();
    expect(KycStatus::REJECTED->isVerified())->toBeFalse();
});
