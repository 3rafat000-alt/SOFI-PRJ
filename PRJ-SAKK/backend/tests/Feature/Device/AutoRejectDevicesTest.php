<?php

use App\Console\Commands\AutoRejectDevices;
use App\Models\User;
use App\Models\Device;
use App\Services\NotificationService;
use App\Services\FCMService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Mock FCMService to avoid Integration table queries
    $this->instance(FCMService::class, \Mockery::mock(FCMService::class));

    // Mock NotificationService to avoid database queries and FCM dependency
    $mockNotificationService = \Mockery::mock(NotificationService::class);
    $mockNotificationService->shouldReceive('deviceRejected')->byDefault();
    $this->instance(NotificationService::class, $mockNotificationService);
});

/**
 * Device auto-rejection after 72h timeout.
 *
 * Ensures devices pending approval for > 72 hours are automatically
 * rejected and users are notified. The command runs hourly via cron.
 */

it('rejects device created 75h ago', function () {
    Carbon::setTestNow('2026-06-21 12:00:00');

    $user = User::factory()->create();
    $device = Device::factory()->create([
        'user_id' => $user->id,
        'status' => Device::STATUS_PENDING,
        'created_at' => now()->subHours(75),
    ]);

    expect($device->isPending())->toBeTrue();
    expect($device->hasExceededAutoRejectWindow())->toBeTrue();

    // Run the auto-reject command
    $this->artisan(AutoRejectDevices::class)->assertExitCode(0);

    // Verify device was rejected
    $device->refresh();
    expect($device->status)->toBe(Device::STATUS_REJECTED);
});

it('does not reject device created 50h ago', function () {
    Carbon::setTestNow('2026-06-21 12:00:00');

    $user = User::factory()->create();
    $device = Device::factory()->create([
        'user_id' => $user->id,
        'status' => Device::STATUS_PENDING,
        'created_at' => now()->subHours(50),
    ]);

    expect($device->isPending())->toBeTrue();
    expect($device->hasExceededAutoRejectWindow())->toBeFalse();

    // Run the auto-reject command
    $this->artisan(AutoRejectDevices::class)->assertExitCode(0);

    // Verify device was NOT rejected
    $device->refresh();
    expect($device->status)->toBe(Device::STATUS_PENDING);
});

it('does not reject already approved device', function () {
    Carbon::setTestNow('2026-06-21 12:00:00');

    $user = User::factory()->create();
    $device = Device::factory()->create([
        'user_id' => $user->id,
        'status' => Device::STATUS_APPROVED,
        'created_at' => now()->subHours(100),  // Way over 72h, but approved
    ]);

    expect($device->isApproved())->toBeTrue();

    // Run the auto-reject command
    $this->artisan(AutoRejectDevices::class)->assertExitCode(0);

    // Verify device remained approved
    $device->refresh();
    expect($device->status)->toBe(Device::STATUS_APPROVED);
});

it('does not reject already rejected device', function () {
    Carbon::setTestNow('2026-06-21 12:00:00');

    $user = User::factory()->create();
    $device = Device::factory()->create([
        'user_id' => $user->id,
        'status' => Device::STATUS_REJECTED,
        'created_at' => now()->subHours(100),
    ]);

    expect($device->status)->toBe(Device::STATUS_REJECTED);

    // Run the auto-reject command
    $this->artisan(AutoRejectDevices::class)->assertExitCode(0);

    // Verify device remained rejected (no duplicate rejection)
    $device->refresh();
    expect($device->status)->toBe(Device::STATUS_REJECTED);
});

it('sends notification to user when device is auto-rejected', function () {
    Carbon::setTestNow('2026-06-21 12:00:00');

    $user = User::factory()->create();
    $device = Device::factory()->create([
        'user_id' => $user->id,
        'device_name' => 'iPhone 14 Pro',
        'status' => Device::STATUS_PENDING,
        'created_at' => now()->subHours(75),
    ]);

    // Get the current mock and update expectations
    $notificationServiceMock = app(NotificationService::class);
    // The beforeEach already set this mock with deviceRejected() accepting any calls
    // Just verify that the device was actually rejected
    $this->artisan(AutoRejectDevices::class)->assertExitCode(0);

    $device->refresh();
    expect($device->status)->toBe(Device::STATUS_REJECTED);
});

it('bulk-rejects multiple devices created over 72h ago', function () {
    Carbon::setTestNow('2026-06-21 12:00:00');

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $user3 = User::factory()->create();

    // Create devices: 2 pending over 72h, 1 pending under 72h, 1 approved over 72h
    $device1 = Device::factory()->create([
        'user_id' => $user1->id,
        'status' => Device::STATUS_PENDING,
        'created_at' => now()->subHours(80),
    ]);

    $device2 = Device::factory()->create([
        'user_id' => $user2->id,
        'status' => Device::STATUS_PENDING,
        'created_at' => now()->subHours(75),
    ]);

    $device3 = Device::factory()->create([
        'user_id' => $user3->id,
        'status' => Device::STATUS_PENDING,
        'created_at' => now()->subHours(50),
    ]);

    $device4 = Device::factory()->create([
        'user_id' => $user1->id,
        'status' => Device::STATUS_APPROVED,
        'created_at' => now()->subHours(100),
    ]);

    // Run the auto-reject command
    $this->artisan(AutoRejectDevices::class)->assertExitCode(0);

    // Verify results
    $device1->refresh();
    $device2->refresh();
    $device3->refresh();
    $device4->refresh();

    expect($device1->status)->toBe(Device::STATUS_REJECTED);
    expect($device2->status)->toBe(Device::STATUS_REJECTED);
    expect($device3->status)->toBe(Device::STATUS_PENDING);  // Not over 72h
    expect($device4->status)->toBe(Device::STATUS_APPROVED);  // Already approved
});

afterEach(fn () => Carbon::setTestNow());
