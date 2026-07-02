<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('updates the authenticated user fcm token', function () {
    $user = User::factory()->create(['fcm_token' => null]);
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/notifications/fcm-token', ['fcm_token' => 'tok-abc-123'])
        ->assertOk()
        ->assertJson(['success' => true]);

    expect($user->fresh()->fcm_token)->toBe('tok-abc-123');
});

it('overwrites a previously stored token', function () {
    $user = User::factory()->create(['fcm_token' => 'old-token']);
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/notifications/fcm-token', ['fcm_token' => 'new-token'])
        ->assertOk();

    expect($user->fresh()->fcm_token)->toBe('new-token');
});

it('validates the fcm token is required', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/notifications/fcm-token', [])->assertStatus(422);
});

it('rejects an unauthenticated fcm token update', function () {
    $this->postJson('/api/v1/notifications/fcm-token', ['fcm_token' => 'x'])
        ->assertStatus(401);
});
