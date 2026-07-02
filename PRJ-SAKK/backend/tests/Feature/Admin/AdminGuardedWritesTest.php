<?php

use App\Enums\CardStatus;
use App\Enums\KycStatus;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Models\Agent;
use App\Models\KycDocument;
use App\Models\KycVerification;
use App\Models\Merchant;
use App\Models\User;
use App\Models\VirtualCard;
use App\Models\Wallet;
use App\Services\CardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/**
 * Regression guard for the systemic "guarded field set via update()/create()" bug class.
 * After SEC-002/003 moved fields to guarded (not $fillable), several admin write paths kept
 * using mass-assignment, which SILENTLY DROPS those fields. These tests pin that the trusted
 * admin writes now actually persist (via forceFill / KycService::reviewVerification).
 */

function guardedWritesAdmin(): User
{
    return User::factory()->create(['is_admin' => true]);
}

// ── API admin KYC (User.kyc_status / kyc_verified_at are guarded) ──

it('API admin approveKyc persists user kyc_status VERIFIED (was silently dropped)', function () {
    $admin = guardedWritesAdmin();
    $user = User::factory()->create(['kyc_status' => KycStatus::PENDING]);

    Sanctum::actingAs($admin);
    $this->postJson("/api/v1/admin/kyc/{$user->id}/approve")->assertStatus(200);

    $fresh = $user->fresh();
    expect($fresh->kyc_status)->toBe(KycStatus::VERIFIED);
    expect($fresh->kyc_verified_at)->not->toBeNull();
});

it('API admin rejectKyc persists user kyc_status REJECTED (was silently dropped)', function () {
    $admin = guardedWritesAdmin();
    $user = User::factory()->create(['kyc_status' => KycStatus::PENDING]);

    Sanctum::actingAs($admin);
    $this->postJson("/api/v1/admin/kyc/{$user->id}/reject", ['reason' => 'مستندات غير صالحة'])
        ->assertStatus(200);

    expect($user->fresh()->kyc_status)->toBe(KycStatus::REJECTED);
});

// ── Web admin KYC (KycVerification.status/reviewed_* + User.kyc_* are guarded) ──

it('web admin KYC approve persists the verification record as approved (was dropped)', function () {
    $admin = guardedWritesAdmin();
    $user = User::factory()->create();
    $doc = KycDocument::create([
        'user_id' => $user->id,
        'document_type' => 'national_id',
        'file_path' => 'kyc/documents/test.pdf',
        'file_name' => 'test.pdf',
        'file_type' => 'application/pdf',
        'file_size' => 1024,
        'status' => 'pending',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.users.kyc.approve', [$user, $doc]))
        ->assertOk();

    $fresh = $doc->fresh();
    expect($fresh->status->value)->toBe('approved');
    expect($fresh->verified_by)->toBe($admin->id);
    expect($fresh->verified_at)->not->toBeNull();
});

it('web admin KYC reject persists rejection + reason (was dropped)', function () {
    $admin = guardedWritesAdmin();
    $user = User::factory()->create();
    $doc = KycDocument::create([
        'user_id' => $user->id,
        'document_type' => 'national_id',
        'file_path' => 'kyc/documents/test.pdf',
        'file_name' => 'test.pdf',
        'file_type' => 'application/pdf',
        'file_size' => 1024,
        'status' => 'pending',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.users.kyc.reject', [$user, $doc]), ['reason' => 'مستند غير واضح'])
        ->assertOk();

    $fresh = $doc->fresh();
    expect($fresh->status->value)->toBe('rejected');
    expect($fresh->verified_by)->toBe($admin->id);
    expect($fresh->rejection_reason)->toBe('مستند غير واضح');
});

// ── Admin Agent management (commission_rate/is_verified are guarded on Agent) ──

it('admin agent update persists guarded commission_rate + is_verified (was dropped)', function () {
    $admin = guardedWritesAdmin();
    $agent = (new Agent())->forceFill([
        'name' => 'Old', 'address' => 'A', 'city' => 'Damascus',
        'latitude' => 33.5, 'longitude' => 36.3, 'commission_rate' => 1.0, 'is_verified' => false,
    ]);
    $agent->save();

    $this->actingAs($admin)
        ->put(route('admin.agents.update', $agent), [
            'name' => 'New Agent', 'address' => 'B', 'city' => 'Aleppo',
            'latitude' => 36.2, 'longitude' => 37.1,
            'commission_rate' => 7.5, 'is_verified' => true,
        ])->assertRedirect();

    $fresh = $agent->fresh();
    expect((float) $fresh->commission_rate)->toBe(7.5);
    expect($fresh->is_verified)->toBeTrue();
});

it('admin agent store persists guarded commission_rate (was dropped)', function () {
    $admin = guardedWritesAdmin();

    $this->actingAs($admin)
        ->post(route('admin.agents.store'), [
            'name' => 'Fresh Agent', 'address' => 'C', 'city' => 'Homs',
            'latitude' => 34.7, 'longitude' => 36.7, 'commission_rate' => 3.25,
        ])->assertRedirect();

    $agent = Agent::where('name', 'Fresh Agent')->firstOrFail();
    expect((float) $agent->commission_rate)->toBe(3.25);
});

// ── Admin Merchant management (commission_rate guarded; is_verified fillable) ──

it('admin merchant update persists guarded commission_rate (was dropped)', function () {
    $admin = guardedWritesAdmin();
    $merchant = (new Merchant())->forceFill([
        'store_name' => 'Old Store', 'type' => 'physical', 'commission_rate' => 1.0,
    ]);
    $merchant->save();

    $this->actingAs($admin)
        ->put(route('admin.merchants.update', $merchant), [
            'store_name' => 'New Store', 'type' => 'both', 'commission_rate' => 9.25,
        ])->assertRedirect();

    expect((float) $merchant->fresh()->commission_rate)->toBe(9.25);
});

// ── Admin user suspend/activate (User.status is guarded) ──

it('admin user suspend persists User.status SUSPENDED (was dropped)', function () {
    $admin = guardedWritesAdmin();
    $user = User::factory()->create(['status' => UserStatus::ACTIVE]);

    $this->actingAs($admin)
        ->post(route('admin.users.suspend', $user))
        ->assertRedirect();

    expect($user->fresh()->status)->toBe(UserStatus::SUSPENDED);
});

it('admin user activate persists User.status ACTIVE (was dropped)', function () {
    $admin = guardedWritesAdmin();
    $user = User::factory()->create(['status' => UserStatus::SUSPENDED]);

    $this->actingAs($admin)
        ->post(route('admin.users.activate', $user))
        ->assertRedirect();

    expect($user->fresh()->status)->toBe(UserStatus::ACTIVE);
});

// ── Card cancel (VirtualCard.status/balance are guarded) ──

it('CardService cancelCard persists status CANCELLED (was dropped)', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->create(['user_id' => $user->id]);
    $card = VirtualCard::factory()->create([
        'user_id' => $user->id, 'wallet_id' => $wallet->id, 'balance' => 0,
    ]);

    $result = app(CardService::class)->cancelCard($card, $wallet);

    expect($result['success'])->toBeTrue();
    expect($card->fresh()->status)->toBe(CardStatus::CANCELLED);
});
