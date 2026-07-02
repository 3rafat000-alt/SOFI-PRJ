<?php

use App\Models\User;
use App\Services\PinService;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->service = app(PinService::class);
});

it('verifies correct pin', function () {
    $user = User::factory()->make([
        'pin_code' => Hash::make('1234'),
    ]);

    expect($this->service->verify($user, '1234'))->toBeTrue();
});

it('rejects incorrect pin', function () {
    $user = User::factory()->make([
        'pin_code' => Hash::make('1234'),
    ]);

    expect($this->service->verify($user, '5678'))->toBeFalse();
});

it('returns false when no pin set', function () {
    $user = User::factory()->make([
        'pin_code' => null,
    ]);

    expect($this->service->verify($user, '1234'))->toBeFalse();
});

it('hashes pin for storage', function () {
    $hashed = $this->service->hash('9999');

    expect($hashed)->not->toBe('9999');
    expect(Hash::check('9999', $hashed))->toBeTrue();
});
