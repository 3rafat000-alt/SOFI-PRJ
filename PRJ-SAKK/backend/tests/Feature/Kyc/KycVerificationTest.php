<?php

use App\Enums\KycStatus;
use App\Models\KycVerification;
use App\Models\User;
use App\Services\KycService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

/**
 * 3-level KYC system: unverified → standard → verified
 * L0: none required
 * L1: email + phone + id_document
 * L2: email + phone + id_document + selfie
 * Auto-approve + admin review flag.
 */

function kycUser(array $attrs = []): User
{
    return User::factory()->create(array_merge([
        'kyc_status' => KycStatus::PENDING,
        'kyc_level' => 0,
        'email_verified_at' => null,
        'phone_verified_at' => null,
    ], $attrs));
}

function fundUsd(User $user, float $amount): void
{
    $user->wallets()->where('currency', 'USD')->update([
        'balance' => $amount,
        'available_balance' => $amount,
    ]);
}

// ──────────────── Structure ────────────────

it('returns exactly three active levels with dual-currency limits', function () {
    Sanctum::actingAs(kycUser());

    $res = $this->getJson('/api/v1/kyc/levels')->assertStatus(200);

    $levels = $res->json('data');
    expect($levels)->toHaveCount(3);
    expect($levels[0]['limits'])->toHaveKeys(['USD', 'SYP']);
    expect($levels[0]['limits']['USD'])->toHaveKeys(['daily', 'monthly', 'single']);
    expect($levels[1]['name_ar'])->toBe('موثّق أساسي');
    expect($levels[2]['name_ar'])->toBe('موثّق كامل');
    // Balance limits and cards limits present
    expect($levels[1])->toHaveKey('balance_limit');
    expect($levels[1])->toHaveKey('cards_limit');
    expect($levels[1]['balance_limit'])->toHaveKeys(['USD', 'SYP']);
});

it('reports unverified status for a fresh user', function () {
    Sanctum::actingAs(kycUser());

    $this->getJson('/api/v1/kyc/status')
        ->assertStatus(200)
        ->assertJsonPath('data.current_level', 0)
        ->assertJsonPath('data.is_verified', false)
        ->assertJsonPath('data.is_standard', false)
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.missing_requirements', ['email', 'phone', 'id_document']);
});

// ──────────────── Email / Phone OTP ────────────────

it('verifies email via OTP', function () {
    $user = kycUser();
    Sanctum::actingAs($user);

    $code = $this->postJson('/api/v1/kyc/email/send')
        ->assertStatus(200)
        ->json('code');

    expect($code)->not->toBeNull();

    $this->postJson('/api/v1/kyc/email/verify', ['code' => $code])
        ->assertStatus(200)
        ->assertJsonPath('success', true);

    expect($user->fresh()->email_verified_at)->not->toBeNull();
});

it('rejects an invalid email OTP', function () {
    $user = kycUser();
    Sanctum::actingAs($user);
    $this->postJson('/api/v1/kyc/email/send');

    $this->postJson('/api/v1/kyc/email/verify', ['code' => '000000'])
        ->assertStatus(400);

    expect($user->fresh()->email_verified_at)->toBeNull();
});

it('verifies phone via OTP', function () {
    $user = kycUser(['phone' => '+963933000111']);
    Sanctum::actingAs($user);

    $code = $this->postJson('/api/v1/kyc/phone/send')->assertStatus(200)->json('code');

    $this->postJson('/api/v1/kyc/phone/verify', ['code' => $code])
        ->assertStatus(200)
        ->assertJsonPath('success', true);

    expect($user->fresh()->phone_verified_at)->not->toBeNull();
});

// ──────────────── Standard (L1) — email + phone + id_document ────────────────

it('reaches standard level only after an admin approves the id document', function () {
    Storage::fake('private');
    $user = kycUser(['email_verified_at' => now(), 'phone_verified_at' => now()]);
    $admin = User::factory()->create();
    $svc = app(KycService::class);
    Sanctum::actingAs($user);

    $svc->syncUserLevel($user);
    expect($user->fresh()->kyc_level)->toBe(0); // id_document missing

    $this->post('/api/v1/kyc/id-document', [
        'document_type' => 'national_id',
        'front_image' => UploadedFile::fake()->image('id.jpg'),
    ], ['Accept' => 'application/json'])->assertStatus(200);

    // Submitted, pending manual acceptance → NOT levelled up yet.
    expect($user->fresh()->kyc_level)->toBe(0);

    $idV = KycVerification::where('user_id', $user->id)->where('verification_type', 'id_document')->first();
    expect($idV->status)->toBe('pending');
    $svc->reviewVerification($idV, $admin, 'approved');

    $fresh = $user->fresh();
    expect($fresh->kyc_level)->toBe(1);
    expect($fresh->kyc_status)->toBe(KycStatus::PENDING); // not fully verified yet
});

it('allows standard user to transfer up to standard limits', function () {
    $sender = kycUser([
        'kyc_level' => 1,
        'kyc_status' => KycStatus::PENDING,
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'pin_code' => Hash::make('123456'),
    ]);
    $recipient = kycUser();
    fundUsd($sender, 2000);
    Sanctum::actingAs($sender);

    // Standard single limit = 500 → 600 should be rejected
    $this->postJson('/api/v1/transfer', [
        'identifier' => 'SK' . str_pad((string) $recipient->id, 8, '0', STR_PAD_LEFT),
        'amount' => 600,
        'currency' => 'USD',
        'pin' => '123456',
    ])->assertStatus(422);

    // 400 within limit → ok
    $this->postJson('/api/v1/transfer', [
        'identifier' => 'SK' . str_pad((string) $recipient->id, 8, '0', STR_PAD_LEFT),
        'amount' => 400,
        'currency' => 'USD',
        'pin' => '123456',
    ])->assertStatus(200);
});

// ──────────────── Verified (L2) — add selfie ────────────────

it('becomes fully verified only after an admin approves id + selfie', function () {
    Storage::fake('private');
    $user = kycUser(['email_verified_at' => now(), 'phone_verified_at' => now()]);
    $admin = User::factory()->create();
    $svc = app(KycService::class);
    Sanctum::actingAs($user);

    // Submit ID + selfie — both land PENDING.
    $this->post('/api/v1/kyc/id-document', [
        'document_type' => 'national_id',
        'front_image' => UploadedFile::fake()->image('id.jpg'),
    ], ['Accept' => 'application/json'])->assertStatus(200);
    $this->post('/api/v1/kyc/selfie', [
        'selfie' => UploadedFile::fake()->image('selfie.jpg'),
    ], ['Accept' => 'application/json'])->assertStatus(200);

    // Nothing approved yet → still unverified.
    expect($user->fresh()->kyc_level)->toBe(0);

    // Admin approves ID → standard (L1)
    $idV = KycVerification::where('user_id', $user->id)->where('verification_type', 'id_document')->first();
    $svc->reviewVerification($idV, $admin, 'approved');
    expect($user->fresh()->kyc_level)->toBe(1);

    // Admin approves selfie → verified (L2)
    $selfieV = KycVerification::where('user_id', $user->id)->where('verification_type', 'selfie')->first();
    $svc->reviewVerification($selfieV, $admin, 'approved');

    $fresh = $user->fresh();
    expect($fresh->kyc_level)->toBe(2);
    expect($fresh->kyc_status)->toBe(KycStatus::VERIFIED);
});

it('keeps submitted documents pending until an admin accepts', function () {
    Storage::fake('private');
    $user = kycUser(['email_verified_at' => now(), 'phone_verified_at' => now()]);
    Sanctum::actingAs($user);

    $this->post('/api/v1/kyc/id-document', [
        'document_type' => 'passport',
        'front_image' => UploadedFile::fake()->image('id.jpg'),
    ], ['Accept' => 'application/json'])->assertStatus(200);

    $v = KycVerification::where('user_id', $user->id)->where('verification_type', 'id_document')->first();
    expect($v->status)->toBe('pending');
    expect($v->reviewed_by)->toBeNull();

    // User is NOT levelled up while the document is pending.
    expect($user->fresh()->kyc_level)->toBe(0);

    $this->getJson('/api/v1/kyc/status')
        ->assertJsonPath('data.verifications.id_document.pending_review', true);
});

it('downgrades the user when an admin rejects an approved document', function () {
    Storage::fake('private');
    $user = kycUser(['email_verified_at' => now(), 'phone_verified_at' => now()]);
    $admin = User::factory()->create();
    $svc = app(KycService::class);

    Sanctum::actingAs($user);
    $this->post('/api/v1/kyc/id-document', [
        'document_type' => 'national_id',
        'front_image' => UploadedFile::fake()->image('id.jpg'),
    ], ['Accept' => 'application/json']);
    $this->post('/api/v1/kyc/selfie', [
        'selfie' => UploadedFile::fake()->image('s.jpg'),
    ], ['Accept' => 'application/json']);

    // Admin accepts both → fully verified.
    $idVerification = KycVerification::where('user_id', $user->id)
        ->where('verification_type', 'id_document')->first();
    $selfieVerification = KycVerification::where('user_id', $user->id)
        ->where('verification_type', 'selfie')->first();
    $svc->reviewVerification($idVerification, $admin, 'approved');
    $svc->reviewVerification($selfieVerification, $admin, 'approved');

    expect($user->fresh()->kyc_level)->toBe(2);

    // Admin later rejects the ID document
    $svc->reviewVerification($idVerification, $admin, 'rejected', 'صورة غير واضحة');

    $fresh = $user->fresh();
    expect($fresh->kyc_level)->toBe(0); // dropped: id_document is now rejected
    expect($fresh->kyc_status)->toBe(KycStatus::REJECTED);
});

// ──────────────── Limit enforcement ────────────────

it('blocks an unverified user from exceeding the single-transaction limit', function () {
    $sender = kycUser(['pin_code' => Hash::make('123456')]);  // level 0 → USD single limit = 100
    $recipient = kycUser();
    fundUsd($sender, 500);
    Sanctum::actingAs($sender);

    // 150 > 100 single limit → rejected
    $this->postJson('/api/v1/transfer', [
        'identifier' => 'SK' . str_pad((string) $recipient->id, 8, '0', STR_PAD_LEFT),
        'amount' => 150,
        'currency' => 'USD',
        'pin' => '123456',
    ])->assertStatus(422);

    // 50 within limit → ok
    $this->postJson('/api/v1/transfer', [
        'identifier' => 'SK' . str_pad((string) $recipient->id, 8, '0', STR_PAD_LEFT),
        'amount' => 50,
        'currency' => 'USD',
        'pin' => '123456',
    ])->assertStatus(200);
});

it('allows a fully verified user to transfer larger amounts', function () {
    $sender = kycUser([
        'kyc_level' => 2,
        'kyc_status' => KycStatus::VERIFIED,
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'pin_code' => Hash::make('123456'),
    ]);
    $recipient = kycUser();
    fundUsd($sender, 2000);
    Sanctum::actingAs($sender);

    $this->postJson('/api/v1/transfer', [
        'identifier' => 'SK' . str_pad((string) $recipient->id, 8, '0', STR_PAD_LEFT),
        'amount' => 1500,
        'currency' => 'USD',
        'pin' => '123456',
    ])->assertStatus(200);

    // 2000 - 1500 = 500, + 1% cashback (15) = 515
    expect((float) $sender->wallets()->where('currency', 'USD')->first()->balance)->toBe(515.0);
});

it('enforces the unverified daily limit across multiple transfers', function () {
    $sender = kycUser(['pin_code' => Hash::make('123456')]);  // level 0 → USD daily limit = 100
    $recipient = kycUser();
    fundUsd($sender, 500);
    Sanctum::actingAs($sender);

    $payload = fn ($amt) => [
        'identifier' => 'SK' . str_pad((string) $recipient->id, 8, '0', STR_PAD_LEFT),
        'amount' => $amt,
        'currency' => 'USD',
        'pin' => '123456',
    ];

    $this->postJson('/api/v1/transfer', $payload(80))->assertStatus(200);
    // 80 + 50 = 130 > 100 daily → blocked
    $this->postJson('/api/v1/transfer', $payload(50))->assertStatus(422);
});
