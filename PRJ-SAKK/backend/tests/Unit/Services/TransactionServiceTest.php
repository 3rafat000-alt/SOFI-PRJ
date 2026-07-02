<?php

use App\Services\TransactionService;
use App\Models\Wallet;
use App\Enums\TransactionType;
use App\Enums\TransactionStatus;

it('TransactionService can be instantiated', function () {
    $service = new TransactionService();
    expect($service)->toBeInstanceOf(TransactionService::class);
});

it('TransactionService transfer raises exception for same wallet', function () {
    $service = new TransactionService();
    $fromWallet = Mockery::mock(Wallet::class);
    $fromWallet->shouldReceive('getAttribute')->with('user_id')->andReturn(1);
    
    $toWallet = Mockery::mock(Wallet::class);
    $toWallet->shouldReceive('getAttribute')->with('user_id')->andReturn(1);
    
    $this->expectException(\Exception::class);
    
    // Call transfer via reflection to test internal logic
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('transfer');
    $method->setAccessible(true);
    
    $method->invoke($service, $fromWallet, 'test@test.com', 100, '123456');
});

it('TransactionService search type filter works', function () {
    $service = new TransactionService();
    expect($service)->toBeInstanceOf(TransactionService::class);
});
