<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

/**
 * P0.1 — Admin console login brute-force / credential-stuffing guard.
 *
 * The admin login POST (web route `admin.login.submit`) MUST be rate-limited by the
 * named `admin-login` limiter: 5 attempts / minute / IP, the 6th returns HTTP 429.
 * This is pre-auth defence — it fires BEFORE Auth::attempt, so wrong creds, right
 * creds, or a non-admin all hit the same wall once the per-IP budget is spent.
 *
 * Source under test:
 *   - app/Providers/AppServiceProvider.php  → RateLimiter::for('admin-login', 5/min by ip)
 *   - routes/web.php                        → POST /admin/login ->middleware('throttle:admin-login')
 *
 * Determinism: CACHE_STORE=array + SESSION_DRIVER=array (phpunit.xml) keep every hit
 * in-process — no Redis, no real sleeps. The throttle middleware keys on $request->ip(),
 * which defaults to 127.0.0.1 for ALL test requests, so to keep methods independent we
 * (a) clear the limiter in beforeEach and (b) give each test its own client IP. The
 * decay window is irrelevant inside a single method, so Carbon::setTestNow is not used.
 *
 * Web route => HTML / redirect response (NOT JSON): assert the numeric status only,
 * never ->assertJson. CSRF is auto-skipped under tests (VerifyCsrfToken::runningUnitTests),
 * so a plain $this->post() needs no token.
 */

// Reset the named limiter before each test so a prior method's spent budget can never
// bleed across (array cache is per-process; this makes isolation explicit, not implicit).
beforeEach(function () {
    RateLimiter::clear('admin-login');
});

// Helper: POST the admin login form from an explicit source IP so each test owns its
// own per-IP throttle bucket (throttle:admin-login keys by $request->ip()).
function postAdminLogin(string $ip, array $payload = []): \Illuminate\Testing\TestResponse
{
    return test()->withServerVariables(['REMOTE_ADDR' => $ip])
        ->post(route('admin.login.submit'), array_merge([
            'email' => 'admin@sakk.com',
            'password' => 'wrong-password',
        ], $payload));
}

it('allows the first 5 admin login attempts without throttling', function () {
    // Bad credentials => 302 back-with-errors. We only assert it is NOT throttled yet.
    for ($i = 1; $i <= 5; $i++) {
        $response = postAdminLogin('10.0.0.1');
        expect($response->status())->not->toBe(429);
    }
});

it('blocks the 6th admin login attempt in the same minute with 429', function () {
    // Spend the full 5-attempt budget for this IP.
    for ($i = 1; $i <= 5; $i++) {
        postAdminLogin('10.0.0.2');
    }

    // The 6th attempt is over budget => throttled.
    $response = postAdminLogin('10.0.0.2');

    expect($response->status())->toBe(429);
});

it('throttles even a valid admin credential set once the per-IP limit is exceeded', function () {
    // Proves the gate protects the ROUTE (pre-auth), not merely the "bad creds" path:
    // the throttle short-circuits before Auth::attempt, so even correct admin creds 429.
    //
    // User model casts 'password' => 'hashed' (app/Models/User.php), so we pass the
    // PLAINTEXT here — Eloquent hashes it on save. Passing bcrypt() would double-hash.
    // 'is_admin' is intentionally NOT fillable (SEC-002), so set it explicitly; the
    // assertion is 429-only, so authentication never actually has to succeed.
    User::factory()->create([
        'email' => 'real-admin@sakk.com',
        'password' => 'secret123',
        'is_admin' => true,
    ]);

    // Burn the 5-attempt budget with wrong creds from this IP.
    for ($i = 1; $i <= 5; $i++) {
        postAdminLogin('10.0.0.3');
    }

    // Now even the correct credentials are blocked by the throttle.
    $response = postAdminLogin('10.0.0.3', [
        'email' => 'real-admin@sakk.com',
        'password' => 'secret123',
    ]);

    expect($response->status())->toBe(429);
});

it('registers the admin-login limiter at 5 attempts per minute', function () {
    // Config-level proof, independent of route wiring: resolve the named limiter and
    // execute its closure with a synthetic request to read the configured cap.
    $limiter = RateLimiter::limiter('admin-login');
    expect($limiter)->not->toBeNull();

    $limit = $limiter(\Illuminate\Http\Request::create(
        '/admin/login',
        'POST',
        [],
        [],
        [],
        ['REMOTE_ADDR' => '10.0.0.9'],
    ));

    expect($limit->maxAttempts)->toBe(5);
});
