<?php

use App\Models\Wallet;
use App\Models\User;

it('Wallet model has correct fillable attributes', function () {
    $wallet = new Wallet();
    $fillable = $wallet->getFillable();
    
    expect($fillable)->toContain('user_id');
    expect($fillable)->toContain('currency');
    expect($fillable)->toContain('balance');
    expect($fillable)->toContain('is_default');
    expect($fillable)->toContain('deposit_address');
    expect($fillable)->toContain('network');
});

it('Wallet model has correct casts', function () {
    $wallet = new Wallet();
    $casts = $wallet->getCasts();
    
    expect($casts)->toHaveKey('balance', 'decimal:8');
    expect($casts)->toHaveKey('is_active', 'boolean');
    expect($casts)->toHaveKey('is_default', 'boolean');
    expect($casts)->toHaveKey('is_frozen', 'boolean');
});

it('Wallet model defines correct relationships', function () {
    $wallet = new Wallet();
    
    expect($wallet->user())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    expect($wallet->cards())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    expect($wallet->transactions())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

it('Wallet has credit, debit, hold, canSpend methods', function () {
    $wallet = new Wallet();
    
    expect(method_exists($wallet, 'credit'))->toBeTrue();
    expect(method_exists($wallet, 'debit'))->toBeTrue();
    expect(method_exists($wallet, 'canSpend'))->toBeTrue();
    expect(method_exists($wallet, 'hold'))->toBeTrue();
    expect(method_exists($wallet, 'release'))->toBeTrue();
});

it('Wallet has getFormattedBalance and getIsCrypto accessors', function () {
    $wallet = new Wallet();
    
    expect(method_exists($wallet, 'getFormattedBalanceAttribute'))->toBeTrue();
    expect(method_exists($wallet, 'getIsCryptoAttribute'))->toBeTrue();
});
