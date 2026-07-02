<?php

use App\Models\User;
use App\Enums\UserStatus;
use App\Enums\KycStatus;
use Laravel\Sanctum\Sanctum;

it('POST /api/v1/auth/register - creates new user and returns token', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'first_name' => 'أحمد',
        'last_name' => 'محمد',
        'email' => 'ahmed@test.com',
        'password' => 'Pass123!word',
        'password_confirmation' => 'Pass123!word',
    ]);
    
    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'user' => ['id', 'first_name', 'last_name', 'email', 'status'],
                'token',
            ],
            'message',
        ]);
    
    $this->assertDatabaseHas('users', ['email' => 'ahmed@test.com']);
});

it('POST /api/v1/auth/register - validation fails with invalid data', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'first_name' => '',
        'email' => 'invalid-email',
        'password' => '123',
    ]);
    
    $response->assertStatus(422);
});

it('POST /api/v1/auth/register - creates default USD wallet', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@test.com',
        'password' => 'Pass123!word',
        'password_confirmation' => 'Pass123!word',
    ]);
    
    $userId = $response->json('data.user.id');
    $this->assertDatabaseHas('wallets', [
        'user_id' => $userId,
        'currency' => 'USD',
        'is_default' => true,
    ]);
});

it('POST /api/v1/auth/login - authenticates valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'user@test.com',
        'password' => bcrypt('password123'),
        'status' => UserStatus::ACTIVE,
        'kyc_status' => KycStatus::VERIFIED,
    ]);
    
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'user@test.com',
        'password' => 'password123',
    ]);
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['user', 'token'],
        ]);
});

it('POST /api/v1/auth/login - rejects invalid credentials', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'nonexistent@test.com',
        'password' => 'wrongpassword',
    ]);
    
    $response->assertStatus(401);
});

it('POST /api/v1/auth/logout - revokes token', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    
    $response = $this->postJson('/api/v1/auth/logout');
    
    $response->assertStatus(200);
});

it('GET /api/v1/auth/me - returns authenticated user', function () {
    $user = User::factory()->create([
        'first_name' => 'أحمد',
        'kyc_status' => KycStatus::VERIFIED,
    ]);
    Sanctum::actingAs($user);
    
    $response = $this->getJson('/api/v1/auth/me');
    
    $response->assertStatus(200)
        ->assertJsonPath('data.first_name', 'أحمد');
});

it('GET /api/v1/auth/me - returns 401 for unauthenticated', function () {
    $response = $this->getJson('/api/v1/auth/me');
    
    $response->assertStatus(401);
});

it('POST /api/v1/auth/pin - sets user PIN', function () {
    $user = User::factory()->create(['password' => bcrypt('password123')]);
    Sanctum::actingAs($user);
    
    $response = $this->postJson('/api/v1/auth/pin', [
        'pin' => '123456',
        'password' => 'password123',
    ]);
    
    $response->assertStatus(200);
    $this->assertDatabaseHas('users', ['id' => $user->id]);
    expect(Hash::check('123456', $user->fresh()->pin_code))->toBeTrue();
});

it('POST /api/v1/auth/pin/verify - verifies correct PIN', function () {
    $user = User::factory()->create(['pin_code' => '123456']);
    Sanctum::actingAs($user);
    
    $response = $this->postJson('/api/v1/auth/pin/verify', [
        'pin' => '123456',
    ]);
    
    $response->assertStatus(200)
        ->assertJsonPath('success', true);
});

it('POST /api/v1/auth/pin/verify - rejects wrong PIN', function () {
    $user = User::factory()->create(['pin_code' => '123456']);
    Sanctum::actingAs($user);
    
    $response = $this->postJson('/api/v1/auth/pin/verify', [
        'pin' => '000000',
    ]);
    
    $response->assertStatus(422);
});

it('PUT /api/v1/auth/password - changes password', function () {
    $user = User::factory()->create(['password' => bcrypt('oldpassword')]);
    Sanctum::actingAs($user);
    
    $response = $this->putJson('/api/v1/auth/password', [
        'current_password' => 'oldpassword',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);
    
    $response->assertStatus(200);
});

it('GET /api/health - returns ok status', function () {
    $response = $this->getJson('/api/health');
    
    $response->assertStatus(200)
        ->assertJson([
            'status' => 'ok',
            'version' => '1.0.0',
        ]);
});
