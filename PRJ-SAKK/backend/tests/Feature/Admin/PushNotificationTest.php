<?php

use App\Models\AdminNotification;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests(); // FCM is unconfigured in tests → no network anyway
});

function pushAdmin(): User
{
    $u = User::factory()->create();
    $u->forceFill(['is_admin' => true])->save();

    return $u;
}

it('renders the notifications & marketing admin page', function () {
    $this->actingAs(pushAdmin());

    $this->get('/admin/notifications')
        ->assertOk()
        ->assertSee('الإشعارات والتسويق')
        ->assertSee('الجمهور المستهدف');
});

it('records an immediate broadcast', function () {
    User::factory()->create(['is_active' => true, 'fcm_token' => 'tok-1']);
    $this->actingAs(pushAdmin());

    $this->post('/admin/notifications/send', [
        'title' => 'عرض خاص',
        'body' => 'خصم اليوم فقط',
        'type' => 'all',
    ])->assertRedirect();

    $n = AdminNotification::where('title', 'عرض خاص')->first();
    expect($n)->not->toBeNull();
    expect($n->status)->toBe('sent');           // dispatched immediately
    expect($n->type)->toBe('all');
});

it('schedules a broadcast for later instead of sending now', function () {
    $this->actingAs(pushAdmin());

    $this->post('/admin/notifications/send', [
        'title' => 'حملة مجدولة',
        'body' => 'لاحقًا',
        'type' => 'active',
        'scheduled_at' => now()->addDay()->format('Y-m-d\TH:i'),
    ])->assertRedirect();

    $n = AdminNotification::where('title', 'حملة مجدولة')->first();
    expect($n->status)->toBe('scheduled');
    expect($n->sent_at)->toBeNull();
});

it('parses specific user ids from the comma list', function () {
    $this->actingAs(pushAdmin());

    $this->post('/admin/notifications/send', [
        'title' => 'محدّد',
        'body' => 'لأشخاص بعينهم',
        'type' => 'specific',
        'user_ids' => '13, 14, 14, x, 21',
    ])->assertRedirect();

    $n = AdminNotification::where('title', 'محدّد')->first();
    expect($n->user_ids)->toBe([13, 14, 21]); // trimmed, deduped, non-numeric dropped
});

it('rejects a broadcast with no title', function () {
    $this->actingAs(pushAdmin());

    $this->from('/admin/notifications')
        ->post('/admin/notifications/send', ['body' => 'x', 'type' => 'all'])
        ->assertRedirect('/admin/notifications')
        ->assertSessionHasErrors('title');
});

it('blocks non-admins from the page', function () {
    $this->actingAs(User::factory()->create()); // is_admin = false

    // admin middleware redirects non-admins away (does not serve the page)
    $this->get('/admin/notifications')->assertRedirect();
});

it('dispatch-scheduled command sends due notifications', function () {
    $due = AdminNotification::create([
        'admin_id' => pushAdmin()->id,
        'title' => 'due',
        'body' => 'now',
        'type' => 'all',
        'status' => 'scheduled',
        'scheduled_at' => now()->subMinute(),
    ]);

    $this->artisan('notifications:dispatch-scheduled')->assertSuccessful();

    expect($due->fresh()->status)->toBe('sent');
});
