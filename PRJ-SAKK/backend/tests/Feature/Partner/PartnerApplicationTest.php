<?php

use App\Enums\KycStatus;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

function partnerUser(): User
{
    return User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);
}

function makeAgentFor(User $user): Agent
{
    return Agent::create([
        'user_id' => $user->id,
        'name' => 'صرافة الأمين',
        'address' => 'شارع الثورة',
        'city' => 'دمشق',
        'latitude' => 0,
        'longitude' => 0,
        'phone' => '0999111222',
        'kyc_status' => 'pending',
        'is_active' => false,
        'is_verified' => false,
    ]);
}

it('submits an agent application', function () {
    Sanctum::actingAs(partnerUser());

    $res = $this->postJson('/api/v1/partner/apply', [
        'type' => 'agent',
        'name' => 'صرافة الأمين',
        'phone' => '0999111222',
        'address' => 'شارع الثورة',
        'city' => 'دمشق',
        'services' => ['cash_in', 'cash_out'],
    ]);

    $res->assertStatus(201);
    expect($res->json('data.type'))->toBe('agent');
    expect($res->json('data.kyc_status'))->toBe('pending');
    expect($res->json('data.is_active'))->toBeFalse();
});

it('submits a merchant application with a business type', function () {
    Sanctum::actingAs(partnerUser());

    $res = $this->postJson('/api/v1/partner/apply', [
        'type' => 'merchant',
        'store_name' => 'متجر النور',
        'store_type' => 'physical',
        'phone' => '0999111222',
    ]);

    $res->assertStatus(201);
    expect($res->json('data.type'))->toBe('merchant');
    expect($res->json('data.merchant_type'))->toBe('physical');
    expect($res->json('data.kyc_status'))->toBe('pending');
});

it('rejects a merchant application without a business type', function () {
    Sanctum::actingAs(partnerUser());

    $this->postJson('/api/v1/partner/apply', [
        'type' => 'merchant',
        'store_name' => 'متجر النور',
        'phone' => '0999111222',
    ])->assertStatus(422);
});

it('prevents duplicate agent applications', function () {
    $user = partnerUser();
    Sanctum::actingAs($user);
    makeAgentFor($user);

    $this->postJson('/api/v1/partner/apply', [
        'type' => 'agent',
        'name' => 'آخر',
        'phone' => '0999111222',
        'address' => 'شارع',
        'city' => 'حلب',
    ])->assertStatus(422);
});

it('uploads a document for an agent application', function () {
    Storage::fake('public');
    $user = partnerUser();
    Sanctum::actingAs($user);
    makeAgentFor($user);

    $res = $this->postJson('/api/v1/partner/documents', [
        'type' => 'agent',
        'document_type' => 'id_card',
        'file' => UploadedFile::fake()->image('id.jpg'),
    ]);

    $res->assertStatus(201);
    expect($res->json('data.document_type'))->toBe('id_card');
});

it('returns the current application status', function () {
    $user = partnerUser();
    Sanctum::actingAs($user);
    makeAgentFor($user);

    $res = $this->getJson('/api/v1/partner/application');
    $res->assertStatus(200);
    expect($res->json('data.agent'))->not->toBeNull();
    expect($res->json('data.merchant'))->toBeNull();
});

it('requires authentication', function () {
    $this->getJson('/api/v1/partner/application')->assertStatus(401);
    $this->postJson('/api/v1/partner/apply')->assertStatus(401);
});
