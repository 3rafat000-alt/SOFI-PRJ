<?php

use App\Enums\KycStatus;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('forbids guarded fields in admin updateUser request', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $target = User::factory()->create(['kyc_status' => KycStatus::PENDING]);

    $before = $target->fresh();

    Sanctum::actingAs($admin);

    $response = $this->putJson("/api/v1/admin/users/{$target->id}", [
        'first_name' => 'Updated',
        'status' => 'banned',
        'kyc_status' => 'verified',
        'kyc_level' => 3,
        'is_admin' => false,
        'balance' => 999999,
        'email_verified_at' => now()->toDateTimeString(),
        'kyc_verified_at' => now()->toDateTimeString(),
    ]);

    $response->assertOk();
    $fresh = $target->fresh();

    // Allowed field changed
    expect($fresh->first_name)->toBe('Updated');

    // Guarded fields unchanged
    expect((string) $fresh->status->value)->toBe((string) $before->status->value);
    expect((string) $fresh->kyc_status->value)->toBe((string) $before->kyc_status->value);
    expect($fresh->kyc_level)->toBe($before->kyc_level);
    expect($fresh->kyc_verified_at)->toBeNull();
    expect((string) $fresh->email_verified_at)->toBe((string) $before->email_verified_at);
});
