<?php

use App\Enums\KycStatus;
use App\Enums\UserStatus;
use App\Http\Resources\UserResource;
use App\Models\User;

it('formats user resource correctly', function () {
    $user = User::factory()->create();

    $resource = new UserResource($user);
    $array = $resource->toArray(request());

    expect($array['id'])->toBe($user->id);
    expect($array['uuid'])->toBe($user->uuid);
    expect($array['first_name'])->toBe($user->first_name);
    expect($array['last_name'])->toBe($user->last_name);
    expect($array['full_name'])->toBe($user->full_name);
    expect($array['email'])->toBe($user->email);
    expect($array['phone'])->toBe($user->phone);
});

it('includes KYC information', function () {
    $user = User::factory()->create([
        'kyc_status' => KycStatus::VERIFIED,
        'kyc_level' => 2,
        'kyc_verified_at' => now(),
    ]);

    $resource = new UserResource($user);
    $array = $resource->toArray(request());

    expect($array['kyc_status']['value'])->toBe('verified');
    expect($array['is_kyc_verified'])->toBeTrue();
    expect($array['kyc_level'])->toBe(2);
});

it('includes status information', function () {
    $user = User::factory()->create(['status' => UserStatus::ACTIVE]);

    $resource = new UserResource($user);
    $array = $resource->toArray(request());

    expect($array['status']['value'])->toBe('active');
    expect($array['is_active'])->toBeTrue();
});

it('includes security flags', function () {
    $user = User::factory()->create([
        'pin_code' => bcrypt('1234'),
        'two_factor_enabled' => true,
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
    ]);

    $resource = new UserResource($user);
    $array = $resource->toArray(request());

    expect($array['has_pin'])->toBeTrue();
    expect($array['two_factor_enabled'])->toBeTrue();
    expect($array['email_verified'])->toBeTrue();
    expect($array['phone_verified'])->toBeTrue();
});

it('exposes referral code', function () {
    $user = User::factory()->create();

    $resource = new UserResource($user);
    $array = $resource->toArray(request());

    expect($array['referral_code'])->not->toBeEmpty();
});
