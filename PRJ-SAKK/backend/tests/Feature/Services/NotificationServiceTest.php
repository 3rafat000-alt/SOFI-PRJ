<?php

use App\Models\User;
use App\Models\Transaction;
use App\Models\UserNotification;
use App\Models\Wallet;
use App\Services\NotificationService;
use App\Services\FCMService;

beforeEach(function () {
    $this->fcmMock = Mockery::mock(FCMService::class);
    $this->fcmMock->shouldReceive('send')->andReturn(true)->byDefault();
    $this->service = new NotificationService($this->fcmMock);
});

it('sends transfer received notification', function () {
    $user = User::factory()->create();
    $transaction = Transaction::factory()->make(['id' => 1, 'reference' => 'REF123']);

    $this->service->transferReceived($user, $transaction, 500, 'أحمد');

    $notification = UserNotification::where('user_id', $user->id)->first();
    expect($notification)->not->toBeNull();
    expect($notification->template_code)->toBe('p2p_received');
    expect($notification->title)->toBe('تحويل مستلم');
    expect($notification->data['transaction_id'])->toBe('1');
});

it('sends transfer sent notification', function () {
    $user = User::factory()->create();
    $transaction = Transaction::factory()->make(['id' => 2, 'reference' => 'REF456']);

    $this->service->transferSent($user, $transaction, 300, 'سارة');

    $notification = UserNotification::where('user_id', $user->id)->first();
    expect($notification->template_code)->toBe('p2p_sent');
    expect($notification->data['reference'])->toBe('REF456');
});

it('sends payment request received notification', function () {
    $user = User::factory()->create();

    $this->service->paymentRequestReceived($user, 200, 'محمد', 'uuid-123');

    $notification = UserNotification::where('user_id', $user->id)->first();
    expect($notification->template_code)->toBe('payment_request');
    expect($notification->data['payment_request_uuid'])->toBe('uuid-123');
});

it('sends payment request accepted notification', function () {
    $user = User::factory()->create();

    $this->service->paymentRequestAccepted($user, 150, 'ليلى', 'uuid-456');

    $notification = UserNotification::where('user_id', $user->id)->first();
    expect($notification->template_code)->toBe('payment_request_accepted');
});

it('sends payment request rejected notification', function () {
    $user = User::factory()->create();

    $this->service->paymentRequestRejected($user, 100, 'خالد', 'uuid-789');

    $notification = UserNotification::where('user_id', $user->id)->first();
    expect($notification->template_code)->toBe('payment_request_rejected');
});

it('sends KYC level upgraded notification', function () {
    $user = User::factory()->create();

    $this->service->kycLevelUpgraded($user, 2);

    $notification = UserNotification::where('user_id', $user->id)->first();
    expect($notification->template_code)->toBe('kyc_level_upgrade');
    expect($notification->body)->toContain('2');
});

it('sends KYC document verified notification', function () {
    $user = User::factory()->create();

    $this->service->kycDocumentVerified($user, 'هوية');

    $notification = UserNotification::where('user_id', $user->id)->first();
    expect($notification->template_code)->toBe('document_verified');
});

it('sends KYC rejected notification', function () {
    $user = User::factory()->create();

    $this->service->kycRejected($user, 'صورة غير واضحة');

    $notification = UserNotification::where('user_id', $user->id)->first();
    expect($notification->template_code)->toBe('kyc_rejected');
});

it('sends device approved notification', function () {
    $user = User::factory()->create();

    $this->service->deviceApproved($user, 'iPhone 15');

    $notification = UserNotification::where('user_id', $user->id)->first();
    expect($notification->template_code)->toBe('device_approved');
});

it('sends device rejected notification', function () {
    $user = User::factory()->create();

    $this->service->deviceRejected($user, 'Samsung S25');

    $notification = UserNotification::where('user_id', $user->id)->first();
    expect($notification->template_code)->toBe('device_rejected');
});

it('sends cashback earned notification', function () {
    $user = User::factory()->create();

    $this->service->cashbackEarned($user, 25, 'مكافأة');

    $notification = UserNotification::where('user_id', $user->id)->first();
    expect($notification->template_code)->toBe('cashback_earned');
});

it('sends deposit received notification', function () {
    $user = User::factory()->create();

    $this->service->depositReceived($user, 1000, 'USD');

    $notification = UserNotification::where('user_id', $user->id)->first();
    expect($notification->template_code)->toBe('deposit_received');
});

it('sends FCM push when user has fcm_token', function () {
    $user = User::factory()->create(['fcm_token' => 'fcm-token-123']);
    $transaction = Transaction::factory()->make(['id' => 1, 'reference' => 'REF']);

    $this->fcmMock->shouldReceive('send')
        ->with('fcm-token-123', Mockery::any(), Mockery::any(), Mockery::any())
        ->once()
        ->andReturn(true);

    $this->service->transferReceived($user, $transaction, 100, 'تست');
});

it('does not fail when FCM send fails', function () {
    $user = User::factory()->create(['fcm_token' => 'invalid-token']);
    $transaction = Transaction::factory()->make(['id' => 1, 'reference' => 'REF']);

    $this->fcmMock->shouldReceive('send')
        ->andThrow(new \Exception('FCM failure'));

    // Should not throw
    $this->service->transferReceived($user, $transaction, 100, 'تست');

    $notification = UserNotification::where('user_id', $user->id)->first();
    expect($notification)->not->toBeNull();
});

it('does not send FCM when user has no fcm_token', function () {
    $user = User::factory()->create(['fcm_token' => null]);
    $transaction = Transaction::factory()->make(['id' => 1, 'reference' => 'REF']);

    $this->fcmMock->shouldNotReceive('send');

    $this->service->transferReceived($user, $transaction, 100, 'تست');
});

it('handles exception gracefully during notification', function () {
    $user = User::factory()->create();
    $transaction = Transaction::factory()->make(['id' => 1, 'reference' => 'REF']);

    $this->fcmMock->shouldReceive('send')
        ->andThrow(new \RuntimeException('Network error'));

    // Should not throw exception to caller
    $this->service->transferReceived($user, $transaction, 100, 'تست');

    expect(UserNotification::where('user_id', $user->id)->count())->toBe(1);
});
