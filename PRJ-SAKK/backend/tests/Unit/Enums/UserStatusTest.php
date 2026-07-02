<?php

use App\Enums\UserStatus;

it('UserStatus has all required cases', function () {
    expect(UserStatus::cases())->toHaveCount(4);
    expect(UserStatus::ACTIVE->value)->toBe('active');
    expect(UserStatus::SUSPENDED->value)->toBe('suspended');
    expect(UserStatus::BANNED->value)->toBe('banned');
    expect(UserStatus::PENDING->value)->toBe('pending');
});

it('UserStatus labels are correct', function () {
    expect(UserStatus::ACTIVE->label())->toBe('Active');
    expect(UserStatus::SUSPENDED->label())->toBe('Suspended');
    expect(UserStatus::BANNED->label())->toBe('Banned');
    expect(UserStatus::PENDING->label())->toBe('Pending Verification');
});

it('UserStatus colors are correct', function () {
    expect(UserStatus::ACTIVE->color())->toBe('green');
    expect(UserStatus::SUSPENDED->color())->toBe('yellow');
    expect(UserStatus::BANNED->color())->toBe('red');
    expect(UserStatus::PENDING->color())->toBe('gray');
});

it('UserStatus can be cast from string', function () {
    $status = UserStatus::from('active');
    expect($status)->toBe(UserStatus::ACTIVE);
});

it('UserStatus invalid value throws exception', function () {
    UserStatus::from('invalid_status');
})->throws(\ValueError::class);
