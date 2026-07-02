<?php

use App\Models\AdminAlert;
use App\Models\User;
use App\Services\AdminNotificationService;

it('notifies admins with basic info alert', function () {
    $alert = AdminNotificationService::notify('Test Title', 'Test Message', 'info', null, null);

    expect($alert)->toBeInstanceOf(AdminAlert::class);
    expect($alert->title)->toBe('Test Title');
    expect($alert->message)->toBe('Test Message');
    expect($alert->type)->toBe('info');
    expect($alert->admin_id)->toBeNull();
});

it('notifies specific admin', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $alert = AdminNotificationService::notify('Title', 'Message', 'warning', null, $admin->id);

    expect($alert->admin_id)->toBe($admin->id);
    expect($alert->type)->toBe('warning');
});

it('creates user registered alert', function () {
    $user = User::factory()->create([
        'first_name' => 'أحمد',
        'last_name' => 'السيد',
    ]);

    AdminNotificationService::userRegistered($user);

    $alert = AdminAlert::first();
    expect($alert->title)->toBe('مستخدم جديد');
    expect($alert->message)->toContain('أحمد', 'السيد');
});

it('creates pending KYC alert', function () {
    $user = User::factory()->create();

    AdminNotificationService::pendingKyc($user, 'هوية');

    $alert = AdminAlert::first();
    expect($alert->title)->toBe('طلب تحقق KYC جديد');
    expect($alert->type)->toBe('warning');
});

it('creates transaction failed alert', function () {
    AdminNotificationService::transactionFailed('REF-123', 'Insufficient funds', 500);

    $alert = AdminAlert::first();
    expect($alert->title)->toBe('معاملة فاشلة');
    expect($alert->type)->toBe('error');
    expect($alert->message)->toContain('REF-123', '500');
});

it('creates system error alert', function () {
    AdminNotificationService::systemError('Database', 'Connection timeout');

    $alert = AdminAlert::first();
    expect($alert->title)->toBe('خطأ في النظام');
    expect($alert->type)->toBe('error');
    expect($alert->message)->toContain('Database', 'Connection timeout');
});
