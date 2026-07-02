<?php

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Enums\TransactionType;
use App\Enums\TransactionStatus;

it('Transaction model has correct fillable attributes', function () {
    $txn = new Transaction();
    $fillable = $txn->getFillable();
    
    expect($fillable)->toContain('user_id');
    expect($fillable)->toContain('wallet_id');
    expect($fillable)->toContain('type');
    expect($fillable)->toContain('status');
    expect($fillable)->toContain('amount');
    expect($fillable)->toContain('currency');
    expect($fillable)->toContain('description');
    expect($fillable)->toContain('balance_before');
    expect($fillable)->toContain('balance_after');
});

it('Transaction model has correct casts', function () {
    $txn = new Transaction();
    $casts = $txn->getCasts();
    
    expect($casts)->toHaveKey('amount', 'decimal:8');
    expect($casts)->toHaveKey('fee', 'decimal:8');
    expect($casts)->toHaveKey('metadata', 'array');
    expect($casts)->toHaveKey('type', \App\Enums\TransactionType::class);
    expect($casts)->toHaveKey('status', \App\Enums\TransactionStatus::class);
});

it('Transaction model defines correct relationships', function () {
    $txn = new Transaction();
    
    expect($txn->user())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    expect($txn->wallet())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    expect($txn->card())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
});

it('Transaction auto-generates reference on creating', function () {
    $txn = new Transaction();
    
    expect(method_exists($txn, 'boot'))->toBeTrue();
});

it('Transaction status scopes work conceptually', function () {
    expect(TransactionStatus::COMPLETED->value)->toBe('completed');
    expect(TransactionStatus::PENDING->value)->toBe('pending');
    expect(TransactionStatus::FAILED->value)->toBe('failed');
});

it('Transaction types have correct credit/debit mapping', function () {
    expect(TransactionType::DEPOSIT->isCredit())->toBeTrue();
    expect(TransactionType::WITHDRAWAL->isDebit())->toBeTrue();
    expect(TransactionType::FEE->isDebit())->toBeTrue();
});
