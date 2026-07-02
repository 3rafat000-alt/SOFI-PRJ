<?php

use App\Http\Resources\WalletResource;
use App\Models\Wallet;
use App\Models\User;

it('formats wallet resource correctly', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create([
        'currency' => 'SYP',
        'balance' => 1500.00,
        'available_balance' => 1400.00,
        'pending_balance' => 100.00,
        'is_active' => true,
        'is_default' => true,
        'is_frozen' => false,
        'daily_limit' => 10000,
        'monthly_limit' => 100000,
        'daily_spent' => 0,
        'monthly_spent' => 0,
        'total_deposits' => 5000,
        'total_withdrawals' => 3500,
        'total_sent' => 0,
        'total_received' => 5000,
        'transaction_count' => 15,
    ]);

    $resource = new WalletResource($wallet);
    $array = $resource->toArray(request());

    expect($array['id'])->toBe($wallet->id);
    expect($array['uuid'])->toBe($wallet->uuid);
    expect($array['currency'])->toBe('SYP');
    expect($array['balance'])->toBe(1500.00);
    expect($array['available_balance'])->toBe(1400.00);
    expect($array['is_active'])->toBeTrue();
    expect($array['is_default'])->toBeTrue();
    expect($array['is_frozen'])->toBeFalse();
    expect($array['daily_remaining'])->toBe(10000.0);
});

it('includes frozen reason when wallet is frozen', function () {
    $wallet = Wallet::factory()->create([
        'is_frozen' => true,
        'frozen_reason' => 'Security concern',
    ]);

    $resource = new WalletResource($wallet);
    $array = $resource->toArray(request());

    expect($array['is_frozen'])->toBeTrue();
    expect($array['frozen_reason'])->toBe('Security concern');
});

it('includes crypto fields conditionally', function () {
    $wallet = Wallet::factory()->create([
        'network' => 'TRC20',
        'deposit_address' => 'TVnaaCjbtFn1krtfGxB6Kj5KnJzQGe47Bk',
    ]);

    $resource = new WalletResource($wallet);
    $array = $resource->toArray(request());

    // is_crypto accessor always returns false
    expect($array['is_crypto'])->toBeFalse();
    // when() helper returns MissingValue, filter() removes it
    // Use resolve() to apply filter() and remove conditionally excluded keys
    $resolved = $resource->resolve(request());
    expect($resolved)->not->toHaveKey('network');
    expect($resolved)->not->toHaveKey('deposit_address');
});
