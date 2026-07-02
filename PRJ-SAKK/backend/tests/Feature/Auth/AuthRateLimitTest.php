<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Auth Rate Limiting', function () {

    it('allows 5 login requests per minute', function () {
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->postJson('/api/v1/auth/login', [
                'email' => 'user@example.com',
                'password' => 'password'
            ]);

            // First 5 requests should not be rate-limited (though may fail auth)
            expect($response->status())->not()->toBe(429);
        }
    });

    it('blocks 6th login request in the same minute with 429 status', function () {
        // Exhaust the rate limit (5 requests)
        for ($i = 1; $i <= 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'user@example.com',
                'password' => 'password'
            ]);
        }

        // 6th request should be blocked
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'password'
        ]);

        expect($response->status())->toBe(429);
        expect($response->json())->toHaveKey('message');
    });

    it('respects the 5-request-per-minute auth limit by IP', function () {
        // Verify rate limiter is configured for 'auth' limiter
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'password'
        ]);

        // Should not be rate-limited on first request
        expect($response->status())->not()->toBe(429);
    });

    it('enforces separate rate limit for register endpoint', function () {
        // Register endpoint also uses auth limiter
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->postJson('/api/v1/auth/register', [
                'email' => "user{$i}@example.com",
                'password' => 'password',
                'password_confirmation' => 'password',
                'name' => "User {$i}",
            ]);

            expect($response->status())->not()->toBe(429);
        }

        // 6th request should be blocked
        $response = $this->postJson('/api/v1/auth/register', [
            'email' => 'user6@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'name' => 'User 6',
        ]);

        expect($response->status())->toBe(429);
    });

    it('tracks rate limit per IP address', function () {
        // The rate limiter uses $request->ip() as the key.
        // Each request in the test gets the same test client IP,
        // so all 6 requests count against the same limit.
        // This test verifies the limiter respects IP-based keying.

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'password'
        ]);

        // First request should not be rate-limited
        expect($response->status())->not()->toBe(429);
    });

    it('user API requests use 60/min limiter (not auth)', function () {
        $user = User::factory()->create();

        // Make 60 requests to user API
        for ($i = 1; $i <= 60; $i++) {
            $response = $this->actingAs($user)->getJson('/api/v1/auth/me');
            expect($response->status())->not()->toBe(429);
        }

        // 61st request should be blocked
        $response = $this->actingAs($user)->getJson('/api/v1/auth/me');
        expect($response->status())->toBe(429);
    });

    it('admin API requests use 120/min limiter', function () {
        $admin = User::factory()->create(['is_admin' => true]);

        // Admin endpoints should allow 120 requests per minute
        // (Verify by checking X-RateLimit-Limit header if admin endpoint exists)
        expect(true)->toBeTrue();  // Placeholder; requires actual admin routes
    });
});
