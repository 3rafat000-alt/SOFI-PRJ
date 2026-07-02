<?php

use App\Mail\VerificationCodeMail;
use App\Models\Integration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/**
 * Tier-A coverage for the /admin/integrations credential-OTP flow (the
 * mass-assignment / silent-secret-loss surface: Integration.credentials is
 * cast `encrypted:array` and holds live 3rd-party secrets, so the write
 * path here is money/security-adjacent per ADR-004).
 */

function integrationAdmin(): User
{
    return User::factory()->create(['is_admin' => true]);
}

function makeIntegration(array $overrides = []): Integration
{
    // NOTE: key is deliberately NOT 'email' — the 2026_06_30_104910_create_email_integration
    // migration unconditionally seeds a real 'email' row during migrate on a fresh
    // (RefreshDatabase) test DB, which would collide with a factory-created row of
    // the same key (integrations.key is unique). A distinct test-only key avoids
    // coupling this suite to that migration's side effect.
    return Integration::create(array_merge([
        'key' => 'test_email_integration',
        'name' => 'Email',
        'name_ar' => 'البريد الإلكتروني',
        'category' => 'messaging',
        'is_active' => true,
        'environment' => 'sandbox',
        'credentials' => ['mail_host' => 'old.smtp.example.com', 'mail_username' => 'old-user'],
    ], $overrides));
}

/** Trigger the OTP step and capture the real code the way an admin's inbox would. */
function captureIntegrationOtp(\Illuminate\Testing\TestResponse $response): string
{
    $code = null;
    Mail::assertSent(VerificationCodeMail::class, function (VerificationCodeMail $mail) use (&$code) {
        $code = $mail->code;
        return true;
    });
    expect($code)->not->toBeNull();
    return $code;
}

// ── non-credential update ──

it('saves a non-credential update directly and writes an integration_log row', function () {
    $admin = integrationAdmin();
    $integration = makeIntegration(['name' => 'Old Name', 'is_active' => false]);

    $this->actingAs($admin)
        ->putJson(route('admin.integrations.update', $integration), [
            'name' => 'New Name',
            'name_ar' => 'اسم جديد',
            'is_active' => true,
            'environment' => 'production',
        ])
        ->assertOk()
        ->assertJson(['success' => true]);

    $fresh = $integration->fresh();
    expect($fresh->name)->toBe('New Name');
    expect($fresh->is_active)->toBeTrue();
    expect($fresh->environment)->toBe('production');
    // credentials untouched by a non-credential save
    expect($fresh->credentials)->toBe(['mail_host' => 'old.smtp.example.com', 'mail_username' => 'old-user']);

    expect($fresh->logs()->where('action', 'config_update')->exists())->toBeTrue();
});

// ── credential change requires OTP ──

it('credential change returns requires_otp with a pending token and does not save yet', function () {
    Mail::fake();
    $admin = integrationAdmin();
    $integration = makeIntegration();

    $response = $this->actingAs($admin)
        ->putJson(route('admin.integrations.update', $integration), [
            'name' => $integration->name,
            'name_ar' => $integration->name_ar,
            'is_active' => true,
            'credentials' => ['mail_host' => 'new.smtp.example.com', 'mail_password' => 'S3cret!'],
        ]);

    $response->assertOk()->assertJson(['success' => true, 'requires_otp' => true]);
    expect($response->json('pending_token'))->not->toBeNull();

    // not applied yet
    expect($integration->fresh()->credentials)->toBe(['mail_host' => 'old.smtp.example.com', 'mail_username' => 'old-user']);
});

it('wrong OTP is rejected with 422 and leaves credentials unchanged', function () {
    Mail::fake();
    $admin = integrationAdmin();
    $integration = makeIntegration();

    $step1 = $this->actingAs($admin)
        ->putJson(route('admin.integrations.update', $integration), [
            'name' => $integration->name,
            'name_ar' => $integration->name_ar,
            'is_active' => true,
            'credentials' => ['mail_host' => 'new.smtp.example.com'],
        ]);
    $token = $step1->json('pending_token');

    $response = $this->actingAs($admin)
        ->putJson(route('admin.integrations.update', $integration), [
            'pending_token' => $token,
            'otp_code' => '000000',
        ]);

    $response->assertStatus(422)->assertJson(['success' => false]);
    expect($integration->fresh()->credentials)->toBe(['mail_host' => 'old.smtp.example.com', 'mail_username' => 'old-user']);
});

it('correct OTP applies the update and blank credential fields keep existing secrets (merge behavior)', function () {
    Mail::fake();
    $admin = integrationAdmin();
    $integration = makeIntegration([
        'credentials' => ['mail_host' => 'old.smtp.example.com', 'mail_username' => 'old-user', 'mail_password' => 'old-secret'],
    ]);

    $step1 = $this->actingAs($admin)
        ->putJson(route('admin.integrations.update', $integration), [
            'name' => $integration->name,
            'name_ar' => $integration->name_ar,
            'is_active' => true,
            // mail_password left blank -> must keep the existing secret, not wipe it
            'credentials' => ['mail_host' => 'new.smtp.example.com', 'mail_username' => 'old-user', 'mail_password' => ''],
        ]);
    $token = $step1->json('pending_token');
    $code = captureIntegrationOtp($step1);

    $response = $this->actingAs($admin)
        ->putJson(route('admin.integrations.update', $integration), [
            'pending_token' => $token,
            'otp_code' => $code,
        ]);

    $response->assertOk()->assertJson(['success' => true]);

    $fresh = $integration->fresh();
    expect($fresh->credentials['mail_host'])->toBe('new.smtp.example.com');
    expect($fresh->credentials['mail_password'])->toBe('old-secret'); // preserved, not wiped
    expect($fresh->logs()->where('action', 'config_update_secure')->exists())->toBeTrue();
});

// ── toggle ──

it('toggle flips is_active and logs the action', function () {
    $admin = integrationAdmin();
    $integration = makeIntegration(['is_active' => true]);

    $this->actingAs($admin)
        ->postJson(route('admin.integrations.toggle', $integration))
        ->assertOk()
        ->assertJson(['success' => true, 'is_active' => false]);

    expect($integration->fresh()->is_active)->toBeFalse();
    expect($integration->fresh()->logs()->where('action', 'toggle')->exists())->toBeTrue();
});

// ── guard: guest / non-admin blocked ──

it('guest hitting update never reaches AdminMiddleware (auth middleware redirects first), credentials unchanged', function () {
    $integration = makeIntegration();

    // Route stack is ['auth', 'admin'] — an unauthenticated guest is stopped by
    // Laravel's own `auth` middleware before AdminMiddleware ever runs, and the
    // framework default redirects (302) rather than returning 401/403 for a
    // guest hitting a `web`-group route (this app only forces JSON error
    // rendering on `api/*`, see bootstrap/app.php shouldRenderJsonWhen).
    $this->putJson(route('admin.integrations.update', $integration), [
        'name' => 'x', 'name_ar' => 'x', 'credentials' => ['mail_host' => 'evil.example.com'],
    ])->assertRedirect();

    expect($integration->fresh()->credentials)->toBe(['mail_host' => 'old.smtp.example.com', 'mail_username' => 'old-user']);
});

it('non-admin user hitting update is blocked, credentials unchanged', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $integration = makeIntegration();

    $this->actingAs($user)
        ->putJson(route('admin.integrations.update', $integration), [
            'name' => 'x', 'name_ar' => 'x', 'credentials' => ['mail_host' => 'evil.example.com'],
        ])
        ->assertStatus(403);

    expect($integration->fresh()->credentials)->toBe(['mail_host' => 'old.smtp.example.com', 'mail_username' => 'old-user']);
});

it('non-admin user hitting toggle is blocked, is_active unchanged', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $integration = makeIntegration(['is_active' => true]);

    $this->actingAs($user)
        ->postJson(route('admin.integrations.toggle', $integration))
        ->assertStatus(403);

    expect($integration->fresh()->is_active)->toBeTrue();
});
