<?php

/**
 * SecurityHeaders middleware — defensive HTTP headers on every response.
 *
 * No RefreshDatabase / no `uses(...)`: every route exercised here is public and
 * static (admin login Blade + /api/health closure) and touches NO database, so
 * the test stays DB-free and fast.
 *
 * Wiring: SecurityHeaders is appended as a GLOBAL middleware in bootstrap/app.php
 * (`$middleware->append(\App\Http\Middleware\SecurityHeaders::class)`), so it runs
 * on every response — both the `web` and `api` groups. InstallerGuard is prepended
 * to both groups but is a no-op under the `testing` environment
 * (InstallerGuard::handle returns $next($request) early when environment is
 * 'testing'), so these public routes return their normal 200 in tests.
 *
 * Header source of truth: app/Http/Middleware/SecurityHeaders.php. Five headers are
 * ALWAYS emitted; Strict-Transport-Security (HSTS) is CONDITIONAL — only when the
 * request is secure ($request->isSecure()). A plain $this->get() runs over HTTP, so
 * HSTS is ABSENT by design; an https:// URL flips Request::isSecure() to true and
 * makes HSTS appear.
 *
 * Routes used:
 *   web: GET /admin/login  (web.php:41, public, returns 200)
 *   api: GET /api/health   (api.php:458, public closure, returns 200 JSON)
 */

// The five headers SecurityHeaders sets unconditionally, with their exact values.
$alwaysOnHeaders = [
    'X-Frame-Options' => 'DENY',
    'X-Content-Type-Options' => 'nosniff',
    'Referrer-Policy' => 'strict-origin-when-cross-origin',
    'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
];

it('sets all security headers on a web route', function () use ($alwaysOnHeaders) {
    $response = $this->get('/admin/login');

    $response->assertOk();

    foreach ($alwaysOnHeaders as $name => $value) {
        $response->assertHeader($name, $value);
    }

    // CSP is long/brittle — assert it is present and carries the key directives,
    // not the full exact string.
    $response->assertHeader('Content-Security-Policy');
    $csp = $response->headers->get('Content-Security-Policy');
    expect($csp)->toContain("default-src 'self'");
    expect($csp)->toContain("frame-ancestors 'none'");
});

it('sets all security headers on an api route', function () use ($alwaysOnHeaders) {
    $response = $this->getJson('/api/health');

    $response->assertOk();

    foreach ($alwaysOnHeaders as $name => $value) {
        $response->assertHeader($name, $value);
    }

    $response->assertHeader('Content-Security-Policy');
    $csp = $response->headers->get('Content-Security-Policy');
    expect($csp)->toContain("default-src 'self'");
    expect($csp)->toContain("frame-ancestors 'none'");
});

it('omits HSTS on plain http and emits it on https for the web route', function () {
    // Plain HTTP request → isSecure() === false → no Strict-Transport-Security.
    $this->get('http://localhost/admin/login')
        ->assertOk()
        ->assertHeaderMissing('Strict-Transport-Security');

    // https:// URL → Request::isSecure() === true → HSTS emitted with the 1-year policy.
    $this->get('https://localhost/admin/login')
        ->assertOk()
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

it('omits HSTS on plain http and emits it on https for the api route', function () {
    $this->get('http://localhost/api/health')
        ->assertOk()
        ->assertHeaderMissing('Strict-Transport-Security');

    $this->get('https://localhost/api/health')
        ->assertOk()
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});
