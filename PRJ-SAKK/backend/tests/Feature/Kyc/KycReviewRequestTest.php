<?php

use App\Models\KycVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/**
 * App\Http\Requests\Admin\KycReviewRequest — gates
 * API\AdminController::reviewKycVerification (POST /api/v1/admin/kyc-verifications/{id}/review).
 * authorize(): only $user->is_admin. rules(): decision required in:approved,rejected;
 * reason required_if decision=rejected, max 500.
 */

function kycAdmin(): User
{
    return User::factory()->create(['is_admin' => true]);
}

function pendingVerification(): KycVerification
{
    $user = User::factory()->create();

    $verification = new KycVerification([
        'user_id' => $user->id,
        'verification_type' => 'id_document',
        'document_type' => 'national_id',
        'document_path' => 'kyc/doc.jpg',
        'level' => 1,
    ]);
    $verification->forceFill(['status' => 'pending'])->save();

    return $verification;
}

it('authorize: rejects a non-admin user with 403', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $verification = pendingVerification();
    Sanctum::actingAs($user);

    $this->postJson("/api/v1/admin/kyc-verifications/{$verification->id}/review", [
        'decision' => 'approved',
    ])->assertForbidden();

    expect($verification->fresh()->status)->toBe('pending');
});

it('authorize: rejects an unauthenticated request', function () {
    $verification = pendingVerification();

    $this->postJson("/api/v1/admin/kyc-verifications/{$verification->id}/review", [
        'decision' => 'approved',
    ])->assertStatus(401);
});

it('approves a verification and levels up the user', function () {
    $admin = kycAdmin();
    $verification = pendingVerification();
    Sanctum::actingAs($admin);

    $this->postJson("/api/v1/admin/kyc-verifications/{$verification->id}/review", [
        'decision' => 'approved',
    ])->assertOk()->assertJsonPath('success', true);

    $fresh = $verification->fresh();
    expect($fresh->status)->toBe('approved');
    expect($fresh->reviewed_by)->toBe($admin->id);
    expect($fresh->reviewed_at)->not->toBeNull();
});

it('rejects a verification with a reason and stores rejection_reason', function () {
    $admin = kycAdmin();
    $verification = pendingVerification();
    Sanctum::actingAs($admin);

    $this->postJson("/api/v1/admin/kyc-verifications/{$verification->id}/review", [
        'decision' => 'rejected',
        'reason' => 'الصورة غير واضحة',
    ])->assertOk()->assertJsonPath('success', true);

    $fresh = $verification->fresh();
    expect($fresh->status)->toBe('rejected');
    expect($fresh->rejection_reason)->toBe('الصورة غير واضحة');
});

it('rules: requires a reason when decision is rejected', function () {
    $admin = kycAdmin();
    $verification = pendingVerification();
    Sanctum::actingAs($admin);

    $this->postJson("/api/v1/admin/kyc-verifications/{$verification->id}/review", [
        'decision' => 'rejected',
    ])->assertStatus(422)->assertJsonValidationErrors(['reason']);

    expect($verification->fresh()->status)->toBe('pending');
});

it('rules: decision is required', function () {
    $admin = kycAdmin();
    $verification = pendingVerification();
    Sanctum::actingAs($admin);

    $this->postJson("/api/v1/admin/kyc-verifications/{$verification->id}/review", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['decision']);
});

it('rules: decision must be in approved,rejected', function () {
    $admin = kycAdmin();
    $verification = pendingVerification();
    Sanctum::actingAs($admin);

    $this->postJson("/api/v1/admin/kyc-verifications/{$verification->id}/review", [
        'decision' => 'maybe',
    ])->assertStatus(422)->assertJsonValidationErrors(['decision']);
});

it('rules: reason cannot exceed 500 characters', function () {
    $admin = kycAdmin();
    $verification = pendingVerification();
    Sanctum::actingAs($admin);

    $this->postJson("/api/v1/admin/kyc-verifications/{$verification->id}/review", [
        'decision' => 'rejected',
        'reason' => str_repeat('a', 501),
    ])->assertStatus(422)->assertJsonValidationErrors(['reason']);
});

it('returns 404 for a non-existent verification id', function () {
    $admin = kycAdmin();
    Sanctum::actingAs($admin);

    $this->postJson('/api/v1/admin/kyc-verifications/999999/review', [
        'decision' => 'approved',
    ])->assertNotFound();
});
