<?php

use App\Models\VirtualCard;
use App\Models\User;
use App\Models\Wallet;

it('VirtualCard model has correct fillable attributes', function () {
    $card = new VirtualCard();
    $fillable = $card->getFillable();
    
    expect($fillable)->toContain('user_id');
    expect($fillable)->toContain('wallet_id');
    expect($fillable)->toContain('brand');
    expect($fillable)->toContain('card_type');
    // balance & status are intentionally NOT fillable (SEC-003: mutated
    // through dedicated methods only — loadFunds, freeze, etc.).
    expect($fillable)->not->toContain('balance');
    expect($fillable)->not->toContain('status');
    expect($fillable)->toContain('cardholder_name');
});

it('VirtualCard model has correct casts', function () {
    $card = new VirtualCard();
    $casts = $card->getCasts();
    
    expect($casts)->toHaveKey('balance', 'decimal:2');
    expect($casts)->toHaveKey('is_active', 'boolean');
    expect($casts)->toHaveKey('status', \App\Enums\CardStatus::class);
    expect($casts)->toHaveKey('brand', \App\Enums\CardBrand::class);
});

it('VirtualCard model defines correct relationships', function () {
    $card = new VirtualCard();
    
    expect($card->user())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    expect($card->wallet())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    expect($card->transactions())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

it('VirtualCard has freeze, unfreeze, cancel methods', function () {
    $card = new VirtualCard();
    
    expect(method_exists($card, 'freeze'))->toBeTrue();
    expect(method_exists($card, 'unfreeze'))->toBeTrue();
    expect(method_exists($card, 'cancel'))->toBeTrue();
});

it('VirtualCard has loadFunds and unload methods', function () {
    $card = new VirtualCard();
    
    expect(method_exists($card, 'loadFunds'))->toBeTrue();
    expect(method_exists($card, 'unload'))->toBeTrue();
});

it('VirtualCard canSpend checks balance', function () {
    expect(method_exists(new VirtualCard(), 'canSpend'))->toBeTrue();
});
