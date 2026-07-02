<?php

use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;

it('formats transaction resource correctly', function () {
    $transaction = Transaction::factory()->create();

    $resource = new TransactionResource($transaction);
    $array = $resource->toArray(request());

    expect($array)->toHaveKey('id');
    expect($array)->toHaveKey('uuid');
    expect($array)->toHaveKey('reference');
    expect($array)->toHaveKey('type');
    expect($array)->toHaveKey('category');
    expect($array)->toHaveKey('amount');
    expect($array)->toHaveKey('fee');
    expect($array)->toHaveKey('net_amount');
    expect($array)->toHaveKey('status');
    expect($array)->toHaveKey('title');
    expect($array)->toHaveKey('currency');
});

it('includes type metadata correctly', function () {
    $transaction = Transaction::factory()->create();

    $resource = new TransactionResource($transaction);
    $array = $resource->toArray(request());

    expect($array['type'])->toHaveKeys(['value', 'label', 'label_ar', 'icon', 'is_credit', 'is_debit']);
    expect($array['category'])->toHaveKeys(['value', 'label', 'label_ar', 'icon']);
    expect($array['status'])->toHaveKeys(['value', 'label', 'label_ar', 'color', 'is_final']);
});

it('includes exchange fields when present', function () {
    $transaction = Transaction::factory()->create([
        'original_currency' => 'USD',
        'original_amount' => 100,
        'exchange_rate' => 13000,
    ]);

    $resource = new TransactionResource($transaction);
    $array = $resource->toArray(request());

    expect($array['original_currency'])->toBe('USD');
    expect($array['original_amount'])->toBe(100.0);
    expect($array['exchange_rate'])->toBe(13000.0);
});

it('includes failure reason when status is failed', function () {
    $transaction = Transaction::factory()->create([
        'status' => \App\Enums\TransactionStatus::FAILED,
        'failure_reason' => 'Insufficient funds',
    ]);

    $resource = new TransactionResource($transaction);
    $array = $resource->toArray(request());

    expect($array['failure_reason'])->toBe('Insufficient funds');
});

it('includes wallet relation when loaded', function () {
    $wallet = Wallet::factory()->create();
    $transaction = Transaction::factory()->for($wallet)->create();

    $resource = new TransactionResource($transaction);
    // Load relation
    $transaction->load('wallet');
    $array = $resource->toArray(request());

    expect($array['wallet'])->not->toBeNull();
    expect($array['wallet']['id'])->toBe($wallet->id);
});
