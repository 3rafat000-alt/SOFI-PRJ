<?php

use App\Models\User;
use App\Enums\KycStatus;
use Laravel\Sanctum\Sanctum;

it('validates transfer lookup - identifier is required', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/transfer/lookup?identifier=');

    $response->assertStatus(422)
        ->assertJsonPath('errors.identifier.0', 'أدخل وسم المستلم أو بريده أو رقم هاتفه.');
});

it('validates transfer - identifier is required', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/transfer', [
        'amount' => 100,
        'currency' => 'USD'
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.identifier.0', 'المستقبل مطلوب.');
});

it('validates transfer - amount is required', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/transfer', [
        'identifier' => 'user@example.com',
        'currency' => 'USD'
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.amount.0', 'المبلغ مطلوب.');
});

it('validates transfer - amount must be numeric', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/transfer', [
        'identifier' => 'user@example.com',
        'amount' => 'abc',
        'currency' => 'USD'
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.amount.0', 'المبلغ يجب أن يكون رقماً.');
});

it('validates transfer - amount must be positive', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/transfer', [
        'identifier' => 'user@example.com',
        'amount' => -100,
        'currency' => 'USD'
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.amount.0', 'المبلغ يجب أن يكون 0.01 على الأقل.');
});

it('validates transfer - currency is required', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/transfer', [
        'identifier' => 'user@example.com',
        'amount' => 100
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.currency.0', 'العملة مطلوبة.');
});

it('validates transfer - currency must be USD or SYP', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/transfer', [
        'identifier' => 'user@example.com',
        'amount' => 100,
        'currency' => 'EUR'
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.currency.0', 'العملة يجب أن تكون USD أو SYP.');
});

it('validates transfer - note must not exceed max length', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);
    Sanctum::actingAs($user);

    $longNote = str_repeat('a', 141);
    $response = $this->postJson('/api/v1/transfer', [
        'identifier' => 'user@example.com',
        'amount' => 100,
        'currency' => 'USD',
        'note' => $longNote
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.note.0', 'الملاحظة طويلة جداً (140 حرف كحد أقصى).');
});
