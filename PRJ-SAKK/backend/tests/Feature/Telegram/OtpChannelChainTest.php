<?php

use App\Models\User;
use App\Services\KycService;
use Illuminate\Support\Facades\Http;

/**
 * OTP delivery priority chain:
 *   1. Telegram bot (linked, free) → 2. Telegram Gateway (auto-detect, paid)
 *   → 3. WhatsApp → 4. SMS. First success wins.
 */

function enableWhatsApp(): void
{
    config()->set('services.whatsapp.enabled', true);
    config()->set('services.whatsapp.base_url', 'http://127.0.0.1:2785');
    config()->set('services.whatsapp.session_id', 'sakk-otp');
    config()->set('services.whatsapp.api_key', 'k');
}

function enableGateway(): void
{
    config()->set('services.telegram_gateway.enabled', true);
    config()->set('services.telegram_gateway.token', 'gw-token');
}

function otpUser(array $attrs = []): User
{
    return User::factory()->create(array_merge([
        'phone' => '0982183111',
        'phone_verified_at' => null,
        'telegram_chat_id' => null,
    ], $attrs));
}

function fakeAllChannels(array $overrides = []): void
{
    Http::fake(array_merge([
        'gatewayapi.telegram.org/checkSendAbility' => Http::response(['ok' => true, 'result' => ['request_id' => 'r1']]),
        'gatewayapi.telegram.org/sendVerificationMessage' => Http::response(['ok' => true, 'result' => ['request_id' => 'r1']]),
        'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]]),
        '127.0.0.1:2785/*' => Http::response(['id' => 'wa1']),
        'sms.local/*' => Http::response(['ok' => true]),
    ], $overrides));
}

it('uses Telegram Gateway (auto-detect) when the number has Telegram and no bot link', function () {
    enableGateway();
    enableWhatsApp();
    fakeAllChannels();

    app(KycService::class)->sendPhoneVerification(otpUser());

    Http::assertSent(fn ($r) => str_contains($r->url(), 'gatewayapi.telegram.org/sendVerificationMessage'));
    Http::assertNotSent(fn ($r) => str_contains($r->url(), 'messages/send-text')); // WhatsApp skipped
});

it('falls back to WhatsApp when the number is not on Telegram', function () {
    enableGateway();
    enableWhatsApp();
    fakeAllChannels([
        'gatewayapi.telegram.org/checkSendAbility' => Http::response(['ok' => false, 'error' => 'PHONE_NOT_ON_TELEGRAM']),
    ]);

    app(KycService::class)->sendPhoneVerification(otpUser());

    Http::assertSent(fn ($r) => str_contains($r->url(), 'messages/send-text'));
    Http::assertNotSent(fn ($r) => str_contains($r->url(), 'sendVerificationMessage'));
});

it('prefers the free linked bot over the paid Gateway', function () {
    config()->set('services.telegram.enabled', true);
    config()->set('services.telegram.bot_token', '1:TESTBOT');
    enableGateway();
    fakeAllChannels();

    app(KycService::class)->sendPhoneVerification(otpUser(['telegram_chat_id' => '55501']));

    Http::assertSent(fn ($r) => str_contains($r->url(), 'api.telegram.org') && str_contains($r->url(), '/sendMessage'));
    Http::assertNotSent(fn ($r) => str_contains($r->url(), 'gatewayapi.telegram.org')); // Gateway not even checked
});

it('falls through to SMS when every richer channel is unavailable', function () {
    // Richer channels off so the chain reaches the last link.
    config()->set('services.telegram.enabled', false);
    config()->set('services.telegram_gateway.enabled', false);
    config()->set('services.whatsapp.enabled', false);
    config()->set('services.sms.enabled', true);
    config()->set('services.sms.endpoint', 'https://sms.local/send');
    fakeAllChannels();

    $res = app(KycService::class)->sendPhoneVerification(otpUser());

    expect($res['success'])->toBeTrue();
    Http::assertSent(fn ($r) => str_contains($r->url(), 'sms.local/send'));
});
