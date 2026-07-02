<?php

use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletService;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('WalletService can be instantiated', function () {
    $service = app(WalletService::class);
    expect($service)->toBeInstanceOf(WalletService::class);
});

it('WalletService createWallet accepts currency parameter', function () {
    $service = app(WalletService::class);
    $user = Mockery::mock(User::class);
    $walletsMock = Mockery::mock(HasMany::class);
    $walletsMock->shouldReceive('count')->andReturn(0);
    $walletsMock->shouldReceive('create')->andReturn(new Wallet(['currency' => 'USD', 'is_default' => true]));
    $user->shouldReceive('wallets')->andReturn($walletsMock);
    
    $wallet = $service->createWallet($user, 'USD');
    
    expect($wallet->is_default ?? false)->toBeTrue();
});

it('WalletService freeze and unfreeze work correctly', function () {
    $service = app(WalletService::class);
    $wallet = Mockery::mock(Wallet::class);
    
    $wallet->shouldReceive('update')->with(['is_frozen' => true, 'frozen_reason' => 'Security concern'])->once();
    $wallet->shouldReceive('update')->with(['is_frozen' => false, 'frozen_reason' => null])->once();
    
    $service->freeze($wallet, 'Security concern');
    $service->unfreeze($wallet);
});

it('WalletService getStats returns correct structure', function () {
    $service = app(WalletService::class);
    $wallet = Mockery::mock(Wallet::class);
    
    $wallet->shouldReceive('getAttribute')->with('balance')->andReturn(5000.00);
    $wallet->shouldReceive('getAttribute')->with('available_balance')->andReturn(4800.00);
    $wallet->shouldReceive('getAttribute')->with('pending_balance')->andReturn(200.00);
    $wallet->shouldReceive('getAttribute')->with('daily_limit')->andReturn(10000.00);
    $wallet->shouldReceive('getAttribute')->with('daily_spent')->andReturn(0);
    $wallet->shouldReceive('getAttribute')->with('monthly_limit')->andReturn(100000.00);
    $wallet->shouldReceive('getAttribute')->with('monthly_spent')->andReturn(0);
    $wallet->shouldReceive('getAttribute')->with('total_deposits')->andReturn(10000.00);
    $wallet->shouldReceive('getAttribute')->with('total_withdrawals')->andReturn(5000.00);
    $wallet->shouldReceive('getAttribute')->with('total_sent')->andReturn(0);
    $wallet->shouldReceive('getAttribute')->with('total_received')->andReturn(0);
    $wallet->shouldReceive('getAttribute')->with('transaction_count')->andReturn(10);
    
    $mockTxnQuery = Mockery::mock(Illuminate\Database\Eloquent\Relations\HasMany::class);
    $mockTxnQuery->shouldReceive('whereDate')->andReturnSelf();
    $mockTxnQuery->shouldReceive('whereMonth')->andReturnSelf();
    $mockTxnQuery->shouldReceive('whereYear')->andReturnSelf();
    $mockTxnQuery->shouldReceive('completed')->andReturnSelf();
    $mockTxnQuery->shouldReceive('get')->andReturn(collect([]));
    $mockTxnQuery->shouldReceive('where')->andReturnSelf();
    $mockTxnQuery->shouldReceive('sum')->andReturn(0);
    
    $wallet->shouldReceive('transactions')->andReturn($mockTxnQuery);
    
    $stats = $service->getStats($wallet);
    
    expect($stats)->toHaveKey('balance');
    expect($stats)->toHaveKey('limits');
    expect($stats)->toHaveKey('totals');
    expect($stats)->toHaveKey('today');
    expect($stats)->toHaveKey('this_month');
    expect($stats['balance']['current'])->toBe(5000.00);
});
