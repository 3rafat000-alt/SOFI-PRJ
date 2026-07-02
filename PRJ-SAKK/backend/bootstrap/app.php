<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'company' => \App\Http\Middleware\CompanyMiddleware::class,
            'merchant' => \App\Http\Middleware\MerchantMiddleware::class,
            'agent' => \App\Http\Middleware\AgentMiddleware::class,
            'installer' => \App\Http\Middleware\InstallerMiddleware::class,
            // Hard-block executable/active-content uploads (svg, html, php) at the
            // edge with a 400 before they reach any FormRequest. Laravel's `image`
            // rule permits SVG (an XSS vector), so this is the authoritative gate
            // on every document-upload route.
            'block-dangerous-uploads' => \App\Http\Middleware\BlockDangerousUploads::class,
            // Disables the virtual-cards surface until Stripe Issuing is configured.
            'cards.enabled' => \App\Http\Middleware\EnsureCardsEnabled::class,
            // Dual-mode auth for payment-request endpoints: app token (M2M) OR
            // Sanctum token (mobile user). Replaces auth:sanctum on that group.
            'app-token' => \App\Http\Middleware\ExternalAppToken::class,
            // Server-side idempotency guard on money-out routes (item 5,
            // generalizes W-SEV-5). `:required` = missing key rejected 400
            // (crypto withdraw only); default = fail-open (transfer, wallet
            // withdraw) — see routes/api.php for per-route mode.
            'idempotency' => \App\Http\Middleware\VerifyIdempotencyKey::class,
        ]);

        $middleware->prependToGroup('web', \App\Http\Middleware\InstallerGuard::class);
        $middleware->prependToGroup('api', \App\Http\Middleware\InstallerGuard::class);

        // Defensive guard (SEV-5): forces api/* requests to be treated as JSON
        // (expectsJson()/wantsJson() key off Accept, not Content-Type) so a
        // caller that sends Content-Type: application/json but omits Accept
        // still gets a JSON response/validation-error instead of a 302 redirect.
        $middleware->appendToGroup('api', \App\Http\Middleware\ForceJsonResponses::class);

        // Admin-controlled kill-switch (SystemSetting 'maintenance_mode', distinct
        // from Laravel's own storage/framework/down). Runs after InstallerGuard so
        // an incomplete install still routes to the installer first. The
        // middleware itself always allows /admin/* through so the flag can be
        // switched back off.
        $middleware->appendToGroup('web', \App\Http\Middleware\CheckMaintenanceMode::class);
        $middleware->appendToGroup('api', \App\Http\Middleware\CheckMaintenanceMode::class);

        // External webhooks authenticate via HMAC signature (Sign/Timestamp headers),
        // not the session — they cannot carry a CSRF token, so without this every
        // provider POST 419s before reaching the controller. Signature is verified
        // in-controller (e.g. CCPaymentWebhookController::verifySignature).
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
        ]);

        // Defensive HTTP security headers (CSP, X-Frame-Options, nosniff, HSTS-on-HTTPS)
        // on every response — web and api. nginx may add its own in prod; this is the
        // app-level fallback so headers exist on artisan serve / non-nginx paths too.
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Trust the reverse proxy chain (cloudflared -> Caddy -> php-fpm on loopback)
        // for the real client IP ONLY.
        //
        // The load-bearing security decision here is the `headers:` argument, NOT the
        // `at:` list. We trust X-Forwarded-FOR ONLY and deliberately do NOT trust
        // X-Forwarded-Host / -Proto. Reason: Caddy's `php_fastcgi` REWRITES
        // X-Forwarded-Host and X-Forwarded-Proto to the upstream hop values
        // (localhost / http), overwriting whatever cloudflared sent. If those headers
        // were trusted, Symfony would prefer them and every generated URL would
        // collapse to http://localhost — which the public-host CSP (script-src /
        // form-action 'self') then blocks, killing asset loads and the login POST
        // (symptom: "login button dead" on sakk.zanjour.com). The tunnel vhost instead
        // feeds the REAL host+scheme through the fastcgi env (HTTP_HOST = the public
        // host, HTTPS = on), so leaving Host/Proto UNTRUSTED makes Laravel read those
        // correct values directly. This header restriction is what fixes the outage.
        //
        // `at:` is scoped to loopback only. Over the php-fpm UNIX socket the connecting
        // peer (REMOTE_ADDR) is always 127.0.0.1 / ::1 — the local Caddy hop — so
        // loopback is the complete and exact set of proxies that can physically reach
        // this process. Note: Laravel special-cases `at: '*'` (Http/Middleware/
        // TrustProxies.php line 83) to trust REMOTE_ADDR directly WITHOUT an IpUtils
        // match, so `'*'` would also function correctly over this socket; it is NOT
        // scoped here because the explicit loopback list is the tightest correct value
        // and removes any unused wildcard/RFC1918 spoofing surface should the FPM bind
        // ever change.
        $middleware->trustProxies(
            at: ['127.0.0.1', '::1'],
            headers: Request::HEADER_X_FORWARDED_FOR,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
