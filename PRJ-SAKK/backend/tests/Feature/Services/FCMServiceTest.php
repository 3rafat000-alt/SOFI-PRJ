<?php

use App\Models\Integration;
use App\Services\FCMService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/** A real RSA service account so openssl_sign() produces a valid JWT. */
function fcmTestServiceAccount(): array
{
    static $sa = null;
    if ($sa !== null) {
        return $sa;
    }

    $res = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);
    openssl_pkey_export($res, $privateKey);

    return $sa = [
        'type' => 'service_account',
        'project_id' => 'skk-wallet',
        'private_key' => $privateKey,
        'client_email' => 'fcm@skk-wallet.iam.gserviceaccount.com',
        'token_uri' => 'https://oauth2.googleapis.com/token',
    ];
}

function seedFcmIntegration(): void
{
    Integration::factory()->create([
        'key' => 'notifications',
        'is_active' => true,
        'credentials' => [
            'fcm_service_account' => json_encode(fcmTestServiceAccount()),
            'fcm_project_id' => 'skk-wallet',
        ],
    ]);
}

/** Fake a healthy OAuth exchange; the FCM v1 Response is supplied per test. */
function fakeFcm($fcmResponse): void
{
    Http::fake([
        'oauth2.googleapis.com/token' => Http::response(
            ['access_token' => 'ya29.test-token', 'expires_in' => 3600],
            200
        ),
        'fcm.googleapis.com/*' => $fcmResponse,
    ]);
}

beforeEach(function () {
    Cache::flush(); // avoid cached access tokens leaking across cases
    Http::preventStrayRequests();
});

it('is not configured when no integration exists', function () {
    expect(app(FCMService::class)->isConfigured())->toBeFalse();
});

it('is configured when integration has a service account', function () {
    seedFcmIntegration();

    expect(app(FCMService::class)->isConfigured())->toBeTrue();
});

it('sends push notification successfully', function () {
    seedFcmIntegration();
    fakeFcm(Http::response(['name' => 'projects/skk-wallet/messages/1'], 200));

    $result = app(FCMService::class)->send('device-token', 'Title', 'Body', ['key' => 'val']);

    expect($result)->toBeTrue();
});

it('returns false when FCM rejects the token', function () {
    seedFcmIntegration();
    fakeFcm(Http::response(['error' => ['status' => 'NOT_FOUND']], 404));

    $result = app(FCMService::class)->send('stale-token', 'Title', 'Body');

    expect($result)->toBeFalse();
});

it('returns false when the FCM request errors', function () {
    seedFcmIntegration();
    fakeFcm(Http::response(null, 500));

    $result = app(FCMService::class)->send('device-token', 'Title', 'Body');

    expect($result)->toBeFalse();
});

it('returns false when the OAuth token exchange fails', function () {
    seedFcmIntegration();
    Http::fake([
        'oauth2.googleapis.com/token' => Http::response(['error' => 'invalid_grant'], 400),
        'fcm.googleapis.com/*' => Http::response([], 200),
    ]);

    $result = app(FCMService::class)->send('device-token', 'Title', 'Body');

    expect($result)->toBeFalse();
});

it('returns false when not configured on send', function () {
    expect(app(FCMService::class)->send('token', 'Title', 'Body'))->toBeFalse();
});

it('sends to multiple tokens', function () {
    seedFcmIntegration();
    fakeFcm(Http::response(['name' => 'm'], 200));

    $tokens = ['tok-a', 'tok-b', 'tok-c'];
    $count = app(FCMService::class)->sendToMultiple($tokens, 'Title', 'Body');

    expect($count)->toBe(3);
});

it('returns 0 when sending to an empty token list', function () {
    seedFcmIntegration();

    expect(app(FCMService::class)->sendToMultiple([], 'Title', 'Body'))->toBe(0);
});

it('returns 0 when not configured for batch send', function () {
    expect(app(FCMService::class)->sendToMultiple(['token'], 'Title', 'Body'))->toBe(0);
});

it('sends to a topic', function () {
    seedFcmIntegration();
    fakeFcm(Http::response(['name' => 'm'], 200));

    expect(app(FCMService::class)->sendToTopic('news', 'Title', 'Body'))->toBeTrue();
});

it('returns false when not configured for topic send', function () {
    expect(app(FCMService::class)->sendToTopic('news', 'Title', 'Body'))->toBeFalse();
});

it('tests connection successfully', function () {
    seedFcmIntegration();
    fakeFcm(Http::response([], 200));

    expect(app(FCMService::class)->testConnection()['success'])->toBeTrue();
});

it('tests connection fails when not configured', function () {
    expect(app(FCMService::class)->testConnection()['success'])->toBeFalse();
});

it('tests connection fails on FCM error', function () {
    seedFcmIntegration();
    fakeFcm(Http::response(['error' => ['message' => 'boom']], 500));

    expect(app(FCMService::class)->testConnection()['success'])->toBeFalse();
});
