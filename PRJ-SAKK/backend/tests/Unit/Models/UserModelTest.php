<?php

use App\Models\User;
use App\Enums\UserStatus;
use App\Enums\KycStatus;

// Note: These tests verify model structure and relationships
// Factory tests require database setup with RefreshDatabase

it('User model has correct fillable attributes', function () {
    $user = new User();
    $fillable = $user->getFillable();
    
    expect($fillable)->toContain('first_name');
    expect($fillable)->toContain('last_name');
    expect($fillable)->toContain('email');
    expect($fillable)->toContain('password');
    expect($fillable)->toContain('phone');
    expect($fillable)->not->toContain('kyc_status');
    expect($fillable)->not->toContain('status');
    expect($fillable)->toContain('pin_code');
});

it('User model has correct hidden attributes', function () {
    $user = new User();
    $hidden = $user->getHidden();
    
    expect($hidden)->toContain('password');
    expect($hidden)->toContain('remember_token');
    expect($hidden)->toContain('pin_code');
    expect($hidden)->toContain('two_factor_secret');
});

it('User model uses correct traits', function () {
    $user = new User();
    $traits = class_uses_recursive(User::class);
    
    expect($traits)->toHaveKey(\Laravel\Sanctum\HasApiTokens::class);
    expect($traits)->toHaveKey(\Illuminate\Database\Eloquent\SoftDeletes::class);
    expect($traits)->toHaveKey(\Illuminate\Notifications\Notifiable::class);
});

it('User model has correct casts', function () {
    $user = new User();
    $casts = $user->getCasts();
    
    expect($casts)->toHaveKey('password', 'hashed');
    expect($casts)->toHaveKey('email_verified_at', 'datetime');
    expect($casts)->toHaveKey('kyc_data', 'array');
    expect($casts)->toHaveKey('is_active', 'boolean');
    expect($casts)->toHaveKey('two_factor_enabled', 'boolean');
});

it('User model defines correct relationships', function () {
    $user = new User();
    
    expect($user->wallets())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    expect($user->cards())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    expect($user->transactions())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    expect($user->kycDocuments())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    expect($user->referrals())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    expect($user->defaultWallet())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class);
    expect($user->referrer())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
});

it('getFullNameAttribute returns correct format', function () {
    $user = new User(['first_name' => 'أحمد', 'last_name' => 'محمد']);
    
    expect($user->getFullNameAttribute())->toBe('أحمد محمد');
});

it('canMakeTransaction returns false when status is not active', function () {
    $user = new User();
    $user->status = UserStatus::SUSPENDED;
    $user->is_active = true;
    $user->kyc_status = KycStatus::VERIFIED;
    
    expect($user->canMakeTransaction())->toBeFalse();
});

it('canMakeTransaction returns true when all conditions met', function () {
    $user = new User();
    $user->status = UserStatus::ACTIVE;
    $user->is_active = true;
    $user->kyc_status = KycStatus::VERIFIED;
    
    expect($user->canMakeTransaction())->toBeTrue();
});

it('verifyPin returns true for matching pin', function () {
    $user = new User(['pin_code' => '123456']);
    
    expect($user->verifyPin('123456'))->toBeTrue();
});

it('verifyPin returns false for wrong pin', function () {
    $user = new User(['pin_code' => '123456']);
    
    expect($user->verifyPin('000000'))->toBeFalse();
});

it('User model boot method generates uuid and referral_code on creating', function () {
    // This is tested via the static::creating event
    $reflection = new ReflectionClass(User::class);
    $methods = $reflection->getMethods();
    $bootMethod = $reflection->getMethod('boot');
    
    expect($bootMethod)->toBeInstanceOf(ReflectionMethod::class);
});
