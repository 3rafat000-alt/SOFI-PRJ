<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private array $registerPayload;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerPayload = [
            'name' => 'سارة أحمد',
            'email' => 'sara@example.com',
            'password' => 'SecureP@ss123',
            'password_confirmation' => 'SecureP@ss123',
            'workspace_name' => 'فريق التسويق',
            'locale' => 'ar',
            'timezone' => 'Asia/Riyadh',
        ];
    }

    /** @test */
    public function it_registers_a_new_user_with_workspace(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->registerPayload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email', 'locale', 'timezone', 'created_at'],
                    'workspace' => ['id', 'name', 'slug', 'role', 'plan'],
                    'token',
                ],
                'meta' => ['request_id', 'timestamp'],
            ]);

        $this->assertDatabaseHas('users', ['email' => 'sara@example.com']);
        $this->assertDatabaseHas('workspaces', ['name' => 'فريق التسويق']);
        $this->assertDatabaseHas('workspace_user', ['role' => 'owner']);
    }

    /** @test */
    public function it_fails_registration_with_duplicate_email(): void
    {
        $this->postJson('/api/v1/auth/register', $this->registerPayload);
        $response = $this->postJson('/api/v1/auth/register', $this->registerPayload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_fails_registration_with_missing_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    /** @test */
    public function it_fails_registration_with_short_password(): void
    {
        $this->registerPayload['password'] = '123';
        $this->registerPayload['password_confirmation'] = '123';
        $response = $this->postJson('/api/v1/auth/register', $this->registerPayload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function it_logs_in_valid_user(): void
    {
        $this->postJson('/api/v1/auth/register', $this->registerPayload);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'sara@example.com',
            'password' => 'SecureP@ss123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['user', 'workspace', 'workspaces', 'token'],
                'meta',
            ]);
    }

    /** @test */
    public function it_fails_login_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_logs_out_authenticated_user(): void
    {
        $registerResponse = $this->postJson('/api/v1/auth/register', $this->registerPayload);
        $token = $registerResponse->json('data.token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJsonPath('data.message', 'Logged out successfully.');
    }

    /** @test */
    public function it_fails_logout_without_token(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');
        $response->assertStatus(401);
    }

    /** @test */
    public function it_returns_current_user_profile(): void
    {
        $registerResponse = $this->postJson('/api/v1/auth/register', $this->registerPayload);
        $token = $registerResponse->json('data.token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email', 'locale', 'timezone', 'created_at', 'workspaces'],
                'meta',
            ]);
    }

    /** @test */
    public function it_requires_authentication_for_me(): void
    {
        $response = $this->getJson('/api/v1/auth/me');
        $response->assertStatus(401);
    }

    /** @test */
    public function it_sends_password_reset_link(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        Password::shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => 'test@example.com'])
            ->andReturn(Password::RESET_LINK_SENT);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['message'], 'meta']);
    }

    /** @test */
    public function it_fails_forgot_password_with_missing_email(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', []);
        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_resets_password(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('oldpassword'),
        ]);

        $token = Password::createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'test@example.com',
            'password' => 'NewP@ssword456',
            'password_confirmation' => 'NewP@ssword456',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['message'], 'meta']);
    }

    /** @test */
    public function it_fails_reset_password_with_mismatched_confirmation(): void
    {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'sometoken',
            'email' => 'test@example.com',
            'password' => 'NewP@ssword456',
            'password_confirmation' => 'DifferentP@ss',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function register_uses_ar_locale_by_default(): void
    {
        $payload = $this->registerPayload;
        unset($payload['locale'], $payload['timezone']);

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(201);
        $this->assertEquals('ar', $response->json('data.user.locale'));
    }

    /** @test */
    public function register_creates_workspace_with_owner_role(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->registerPayload);

        $response->assertStatus(201);
        $this->assertEquals('owner', $response->json('data.workspace.role'));
        $this->assertEquals('free', $response->json('data.workspace.plan'));
    }
}
