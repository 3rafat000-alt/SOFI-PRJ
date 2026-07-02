<?php

use App\Models\SystemSetting;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

it('blocks the public landing page with 503 when maintenance_mode is on', function () {
    SystemSetting::set('maintenance_mode', true, 'boolean');

    $response = $this->get('/');

    $response->assertStatus(503);
});

it('never locks the admin out: /admin/login stays reachable when maintenance_mode is on', function () {
    SystemSetting::set('maintenance_mode', true, 'boolean');

    $response = $this->get(route('admin.login'));

    $response->assertOk();
});

it('never locks the admin out: the authenticated admin area stays reachable when maintenance_mode is on', function () {
    SystemSetting::set('maintenance_mode', true, 'boolean');

    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $response->assertOk();
});

it('serves the public landing page normally when maintenance_mode is off', function () {
    SystemSetting::set('maintenance_mode', false, 'boolean');

    $response = $this->get('/');

    $response->assertOk();
});
