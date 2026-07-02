<?php

use App\Models\User;
use App\Models\Wallet;
use App\Models\AuditLog;
use App\Services\AuditLogService;
use App\Enums\KycStatus;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

it('logs wallet deposits via audit service', function () {
    $user = User::factory()->create();
    $auditService = app(AuditLogService::class);

    $auditService->logWalletTransaction(
        $user,
        1,
        'deposit',
        500,
        'USD',
        ['transaction_id' => 123]
    );

    $auditLog = AuditLog::where('action', 'wallet.deposit')
        ->where('model_id', 1)
        ->first();

    expect($auditLog)->not->toBeNull();
    expect($auditLog->model_type)->toBe('Wallet');
    expect($auditLog->new_values['amount'])->toBe(500);
    expect($auditLog->metadata['transaction_id'])->toBe(123);
});

it('logs wallet withdrawals via audit service', function () {
    $user = User::factory()->create();
    $auditService = app(AuditLogService::class);

    $auditService->logWalletTransaction(
        $user,
        2,
        'withdraw',
        100,
        'USD'
    );

    $auditLog = AuditLog::where('action', 'wallet.withdraw')
        ->where('model_id', 2)
        ->first();

    expect($auditLog)->not->toBeNull();
    expect($auditLog->model_type)->toBe('Wallet');
    expect($auditLog->new_values['amount'])->toBe(100);
});

it('logs card operations via audit service', function () {
    $user = User::factory()->create();
    $auditService = app(AuditLogService::class);

    $auditService->logCardTransaction(
        $user,
        5,
        'load',
        250,
        ['transaction_id' => 456]
    );

    $auditLog = AuditLog::where('action', 'card.load')
        ->where('model_id', 5)
        ->first();

    expect($auditLog)->not->toBeNull();
    expect($auditLog->model_type)->toBe('VirtualCard');
    expect($auditLog->new_values['amount'])->toBe(250);
});

it('logs failed transactions', function () {
    $auditService = app(AuditLogService::class);
    $user = User::factory()->create();

    $auditService->logFailure(
        'wallet.deposit',
        'Wallet',
        1,
        'Insufficient balance',
        ['amount' => 100]
    );

    $auditLog = AuditLog::where('action', 'wallet.deposit')
        ->whereJsonContains('metadata->status', 'failed')
        ->first();

    expect($auditLog)->not->toBeNull();
    expect($auditLog->new_values['reason'])->toBe('Insufficient balance');
});

it('retrieves model audit trail', function () {
    // Directly create audit logs for a model
    $modelId = 999;

    AuditLog::create([
        'user_id' => null,
        'action' => 'wallet.deposit',
        'model_type' => 'Wallet',
        'model_id' => $modelId,
        'new_values' => ['amount' => 100],
        'ip_address' => '127.0.0.1',
        'user_agent' => 'test',
        'device_type' => 'test',
        'metadata' => ['status' => 'completed'],
    ]);

    sleep(1); // Ensure different timestamp

    AuditLog::create([
        'user_id' => null,
        'action' => 'wallet.withdraw',
        'model_type' => 'Wallet',
        'model_id' => $modelId,
        'new_values' => ['amount' => 50],
        'ip_address' => '127.0.0.1',
        'user_agent' => 'test',
        'device_type' => 'test',
        'metadata' => ['status' => 'completed'],
    ]);

    $auditService = app(AuditLogService::class);
    $trail = $auditService->getModelAudit('Wallet', $modelId);

    expect(count($trail))->toBe(2);
    expect(in_array('wallet.deposit', array_column($trail, 'action')))->toBeTrue();
    expect(in_array('wallet.withdraw', array_column($trail, 'action')))->toBeTrue();
});
