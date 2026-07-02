<?php

use App\Enums\UserStatus;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Gap-fill for Admin\UserController: show() (detail page + eager-loaded tabs)
 * and the legacy quick-action suspend()/activate() routes — not covered by
 * UserModuleTest (which focuses on updateStatus/bulk/kyc-doc/export).
 */

function ucgAdmin(): User
{
    return User::factory()->create(['is_admin' => true]);
}

function ucgUser(array $attrs = []): User
{
    return User::factory()->create($attrs);
}

it('show renders the user detail page with computed tx KPIs', function () {
    $admin = ucgAdmin();
    $user = ucgUser();

    $response = $this->actingAs($admin)->get(route('admin.users.show', $user));

    $response->assertOk()->assertViewIs('admin.users.show');
    expect($response->viewData('user')->id)->toBe($user->id);
    expect($response->viewData('txCount'))->toBe(0);
    expect($response->viewData('txVolume'))->toBe(0.0);
});

it('show returns 404 for a non-existent user', function () {
    $admin = ucgAdmin();

    $this->actingAs($admin)->get(route('admin.users.show', 999999))->assertNotFound();
});

it('blocks a non-admin from viewing a user detail page', function () {
    $viewer = ucgUser(['is_admin' => false]);
    $target = ucgUser();

    $status = $this->actingAs($viewer)
        ->get(route('admin.users.show', $target))
        ->status();

    expect($status)->toBeIn([403, 302]);
});

it('suspend (legacy quick action) sets status to suspended and writes ActivityLog', function () {
    $admin = ucgAdmin();
    $user = ucgUser(['status' => UserStatus::ACTIVE]);

    $this->actingAs($admin)
        ->post(route('admin.users.suspend', $user))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($user->fresh()->status)->toBe(UserStatus::SUSPENDED);
    expect(
        ActivityLog::where('user_id', $user->id)->where('action', 'users.suspend')->exists()
    )->toBeTrue();
});

it('activate (legacy quick action) sets status to active and writes ActivityLog', function () {
    $admin = ucgAdmin();
    $user = ucgUser(['status' => UserStatus::SUSPENDED]);

    $this->actingAs($admin)
        ->post(route('admin.users.activate', $user))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($user->fresh()->status)->toBe(UserStatus::ACTIVE);
    expect(
        ActivityLog::where('user_id', $user->id)->where('action', 'users.activate')->exists()
    )->toBeTrue();
});

it('blocks a non-admin from the legacy suspend route', function () {
    $viewer = ucgUser(['is_admin' => false]);
    $target = ucgUser(['status' => UserStatus::ACTIVE]);

    $status = $this->actingAs($viewer)
        ->post(route('admin.users.suspend', $target))
        ->status();

    expect($status)->toBeIn([403, 302]);
    expect($target->fresh()->status)->toBe(UserStatus::ACTIVE);
});
