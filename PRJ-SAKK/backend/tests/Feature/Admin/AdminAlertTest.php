<?php

use App\Models\AdminAlert;
use App\Models\User;
use App\Services\AdminNotificationService;

it('creates a broadcast withdrawal alert for admins', function () {
    $user = User::factory()->create(['first_name' => 'سامر', 'last_name' => 'النمري']);

    AdminNotificationService::withdrawalRequested($user, 250.0, 'USD');

    $alert = AdminAlert::latest()->first();
    expect($alert->title)->toBe('طلب سحب جديد');
    expect($alert->type)->toBe('warning');
    expect($alert->admin_id)->toBeNull();        // null = visible to every admin
    expect($alert->read_at)->toBeNull();         // unread
    expect($alert->message)->toContain('250');
});

it('creates an agent application alert linked to document review', function () {
    AdminNotificationService::partnerApplicationSubmitted(User::factory()->create(), 'agent');

    expect(AdminAlert::where('title', 'طلب وكيل جديد')->exists())->toBeTrue();
});

it('creates a merchant application alert', function () {
    AdminNotificationService::partnerApplicationSubmitted(User::factory()->create(), 'merchant');

    expect(AdminAlert::where('title', 'طلب تاجر جديد')->exists())->toBeTrue();
});

it('creates a pending-kyc review alert', function () {
    AdminNotificationService::pendingKyc(User::factory()->create(), 'مستند الهوية');

    $alert = AdminAlert::where('title', 'طلب تحقق KYC جديد')->first();
    expect($alert)->not->toBeNull();
    expect($alert->type)->toBe('warning');
});

function alertAdmin(): User
{
    $u = User::factory()->create();
    $u->forceFill(['is_admin' => true])->save();

    return $u;
}

it('renders admin alerts in the topbar bell', function () {
    AdminNotificationService::notify('تنبيه إداري للجرس', 'رسالة الاختبار', 'warning');

    $this->actingAs(alertAdmin())
        ->get('/admin/notifications')
        ->assertOk()
        ->assertSee('تنبيه إداري للجرس');
});

it('marks all admin alerts read via the bell route', function () {
    AdminNotificationService::notify('غير مقروء', 'x', 'info');

    $this->actingAs(alertAdmin())->post('/admin/alerts/read-all')->assertOk();

    expect(AdminAlert::whereNull('read_at')->count())->toBe(0);
});

it('dismisses an admin alert via the bell route', function () {
    $alert = AdminNotificationService::notify('للحذف', 'y', 'info');

    $this->actingAs(alertAdmin())->post("/admin/alerts/{$alert->id}/dismiss")->assertOk();

    expect(AdminAlert::find($alert->id))->toBeNull();
});
