<?php

namespace App\Providers;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Policies\TransactionPolicy;
use App\Policies\UserPolicy;
use App\Policies\WalletPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── Agent System ───────────────────────────────────────────
        $this->app->singleton(\App\Services\Agent\AgentCryptographicSigner::class);
        $this->app->singleton(\App\Services\Agent\AgentWebhookService::class);

        $this->app->singleton(\App\Services\Agent\FinancialVerificationAgent::class);
        $this->app->singleton(\App\Services\Agent\KycVerificationAgent::class);

        $this->app->singleton(\App\Services\Agent\AgentOrchestrator::class);
    }

    public function boot(): void
    {
        $this->registerPolicies();
        $this->configureRateLimiting();
    }

    protected function registerPolicies(): void
    {
        \Gate::policy(User::class, UserPolicy::class);
        \Gate::policy(Wallet::class, WalletPolicy::class);
        \Gate::policy(Transaction::class, TransactionPolicy::class);
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('admin', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        // OTP / verification-code dispatch (phone & email codes). Strict per-account
        // cap to defeat code-flooding and SMS/email-bombing of a victim's number.
        // 3 sends/min keyed by user id (fallback IP) — 4th attempt is throttled (429).
        RateLimiter::for('otp', function (Request $request) {
            return Limit::perMinute(3)->by($request->user()?->id ?: $request->ip());
        });

        // Admin panel login (pre-auth, web). Strict per-IP cap to defeat credential
        // stuffing / brute-force against the admin console. Keyed by IP only because
        // login is pre-authentication (no user yet). 5 attempts/min — 6th is 429.
        RateLimiter::for('admin-login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // 2FA challenge verify (pre-final-auth, web). Same strict cap as the
        // password step — prevents brute-forcing the 6-digit TOTP/recovery code.
        RateLimiter::for('admin-2fa', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Company / merchant / agent self-service portal logins each get their own
        // bucket so an attacker hammering one portal cannot exhaust the shared quota
        // and lock legitimate admin/other-portal logins out of their 5/min budget.
        RateLimiter::for('company-login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('merchant-login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('agent-login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}
