<?php

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use Laravel\Sanctum\Sanctum;

it('GET /api/v1/admin/dashboard - requires admin role', function () {
    $user = User::factory()->create(['is_admin' => false]);
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/admin/dashboard');
    $response->assertStatus(403);
});

it('GET /api/v1/admin/dashboard - returns stats for admin', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    Sanctum::actingAs($admin);

    User::factory()->count(5)->create();
    Wallet::factory()->count(3)->create();
    Transaction::factory()->count(10)->create();

    $response = $this->getJson('/api/v1/admin/dashboard');
    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                'stats' => ['total_users', 'total_wallets', 'total_transactions'],
                'volume' => ['today', 'this_month', 'all_time', 'fees_collected'],
                'charts' => ['transactions_by_day', 'users_by_day'],
                'kyc_breakdown' => ['verified', 'pending', 'submitted', 'rejected'],
            ],
        ]);
});

it('GET /api/v1/admin/users - validates search input', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/v1/admin/users?search=' . str_repeat('a', 300));
    $response->assertStatus(422);
});

it('GET /api/v1/admin/users - filters by status', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $active = User::factory()->create(['status' => 'active']);
    $suspended = User::factory()->create(['status' => 'suspended']);
    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/v1/admin/users?status=active');
    $response->assertStatus(200)
        ->assertJsonPath('success', true);
});

it('PUT /api/v1/admin/users/{id} - updates user for admin', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['first_name' => 'Old']);
    Sanctum::actingAs($admin);

    $response = $this->putJson("/api/v1/admin/users/{$user->id}", [
        'first_name' => 'New',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.first_name', 'New');
});

it('DELETE /api/v1/admin/users/{id} - prevents deleting admin users', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $target = User::factory()->create(['is_admin' => true]);
    Sanctum::actingAs($admin);

    $response = $this->deleteJson("/api/v1/admin/users/{$target->id}");
    $response->assertStatus(422)
        ->assertJsonPath('success', false);
});

it('GET /api/v1/admin/transactions - validates date range', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/v1/admin/transactions?from=2026-01-01&to=2025-01-01');
    $response->assertStatus(422);
});

it('POST /api/v1/admin/wallets/{id}/freeze - requires reason', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $wallet = Wallet::factory()->create();
    Sanctum::actingAs($admin);

    $response = $this->postJson("/api/v1/admin/wallets/{$wallet->id}/freeze", []);
    $response->assertStatus(422);
});

it('GET /api/v1/admin/export/users - streams CSV for admin', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    User::factory()->count(5)->create();
    Sanctum::actingAs($admin);

    $response = $this->get('/api/v1/admin/export/users');
    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'text/csv; charset=utf-8');
});

it('GET /api/v1/admin/export/invalid - returns error', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    Sanctum::actingAs($admin);

    $response = $this->get('/api/v1/admin/export/invalid');
    $response->assertStatus(422)
        ->assertJsonPath('success', false);
});
