<?php

use App\Models\SystemSetting;

function validRegisterPayload(string $email): array
{
    return [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => $email,
        'password' => 'Pass123!word',
        'password_confirmation' => 'Pass123!word',
    ];
}

it('rejects registration with 403 when registration_open is false, even with a valid payload', function () {
    SystemSetting::set('registration_open', false, 'boolean');

    $response = $this->postJson('/api/v1/auth/register', validRegisterPayload('closed@test.com'));

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'التسجيل مغلق حالياً',
        ]);

    $this->assertDatabaseMissing('users', ['email' => 'closed@test.com']);
});

it('allows registration through when registration_open is true', function () {
    SystemSetting::set('registration_open', true, 'boolean');

    $response = $this->postJson('/api/v1/auth/register', validRegisterPayload('open@test.com'));

    $response->assertStatus(201);
    $this->assertDatabaseHas('users', ['email' => 'open@test.com']);
});
