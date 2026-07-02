<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    // ─────────────── REGISTER ───────────────

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'                 => 'أحمد محمد',
            'email'                => 'ahmed@test.com',
            'password'             => 'Password123!',
            'password_confirmation'=> 'Password123!',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['success', 'data' => ['user' => ['id', 'name', 'email'], 'token']]);

        $this->assertDatabaseHas('users', [
            'email' => 'ahmed@test.com',
            'name'  => 'أحمد محمد',
        ]);
    }

    public function test_register_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_register_validates_email_format(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'     => 'test',
            'email'    => 'not-an-email',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_validates_password_strength(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'                 => 'test',
            'email'                => 'test@test.com',
            'password'             => 'weak',
            'password_confirmation'=> 'weak',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_register_requires_password_confirmation(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'     => 'test',
            'email'    => 'test@test.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'duplicate@test.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name'                 => 'test',
            'email'                => 'duplicate@test.com',
            'password'             => 'Password123!',
            'password_confirmation'=> 'Password123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    // ─────────────── LOGIN ───────────────

    public function test_user_can_login(): void
    {
        $password = 'Password123!';
        $user = User::factory()->create([
            'password' => Hash::make($password),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => $password,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['user', 'token']]);
    }

    public function test_login_with_wrong_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'nobody@nowhere.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(401);
    }

    // ─────────────── LOGOUT ───────────────

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);
    }

    public function test_logout_requires_auth(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');
        $response->assertStatus(401);
    }

    // ─────────────── ME ───────────────

    public function test_can_get_authenticated_user(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_me_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/auth/me');
        $response->assertStatus(401);
    }

    // ─────────────── UPDATE PROFILE ───────────────

    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/v1/auth/profile', [
                'name'  => 'الاسم الجديد',
                'phone' => '0999123456',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'id'   => $user->id,
            'name' => 'الاسم الجديد',
        ]);
    }

    // ─────────────── CHANGE PASSWORD ───────────────

    public function test_user_can_change_password(): void
    {
        $oldPassword = 'OldPass123!';
        $user = User::factory()->create([
            'password' => Hash::make($oldPassword),
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/change-password', [
                'current_password'      => $oldPassword,
                'password'              => 'NewPass456!',
                'password_confirmation' => 'NewPass456!',
            ]);

        $response->assertStatus(200);
    }

    public function test_change_password_rejects_wrong_current(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('RealPass123!'),
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/change-password', [
                'current_password'      => 'WrongPass123!',
                'password'              => 'NewPass456!',
                'password_confirmation' => 'NewPass456!',
            ]);

        $response->assertStatus(422);
    }
}
