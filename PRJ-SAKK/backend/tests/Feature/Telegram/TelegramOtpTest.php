<?php

use App\Models\User;
use App\Services\KycService;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

/**
 * Telegram OTP channel — account linking via webhook + OTP fan-out preference.
 */

function tgEnable(): void
{
    config()->set('services.telegram.enabled', true);
    config()->set('services.telegram.bot_token', '123456:TEST-TOKEN');
    config()->set('services.telegram.bot_username', 'sakk_otp_bot');
    config()->set('services.telegram.webhook_secret', 'test-secret');
}

it('binds a chat to the account when a valid /start token arrives', function () {
    tgEnable();
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => []])]);

    $user = User::factory()->create(['telegram_chat_id' => null]);
    $token = app(TelegramService::class)->makeLinkToken($user);

    $res = $this->postJson('/api/v1/telegram/webhook', [
        'message' => [
            'chat' => ['id' => 998877],
            'from' => ['username' => 'malik'],
            'text' => "/start {$token}",
        ],
    ], ['X-Telegram-Bot-Api-Secret-Token' => 'test-secret']);

    $res->assertOk();
    expect($user->fresh()->telegram_chat_id)->toBe('998877')
        ->and($user->fresh()->telegram_username)->toBe('malik')
        ->and($user->fresh()->telegram_linked_at)->not->toBeNull();
});

it('does not bind on an invalid/expired token', function () {
    tgEnable();
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

    $user = User::factory()->create(['telegram_chat_id' => null]);

    $this->postJson('/api/v1/telegram/webhook', [
        'message' => ['chat' => ['id' => 555], 'text' => '/start bogus-token'],
    ], ['X-Telegram-Bot-Api-Secret-Token' => 'test-secret'])->assertOk();

    expect($user->fresh()->telegram_chat_id)->toBeNull();
});

it('rejects webhook calls with a wrong secret token', function () {
    tgEnable();
    config()->set('services.telegram.webhook_secret', 'right-secret');

    $user = User::factory()->create(['telegram_chat_id' => null]);
    $token = app(TelegramService::class)->makeLinkToken($user);

    $this->postJson('/api/v1/telegram/webhook', [
        'message' => ['chat' => ['id' => 111], 'text' => "/start {$token}"],
    ], ['X-Telegram-Bot-Api-Secret-Token' => 'wrong'])->assertOk();

    expect($user->fresh()->telegram_chat_id)->toBeNull();
});

it('delivers the phone OTP over Telegram when the account is linked', function () {
    tgEnable();
    Http::fake([
        'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]]),
    ]);

    $user = User::factory()->create([
        'phone' => '0982183111',
        'phone_verified_at' => null,
        'telegram_chat_id' => '424242',
    ]);

    $result = app(KycService::class)->sendPhoneVerification($user);

    expect($result['success'])->toBeTrue();
    Http::assertSent(fn ($req) => str_contains($req->url(), 'api.telegram.org')
        && str_contains($req->url(), '/sendMessage'));
});

it('returns a deep link from the authenticated link endpoint', function () {
    tgEnable();

    Sanctum::actingAs(User::factory()->create());

    $res = $this->getJson('/api/v1/telegram/link')
        ->assertOk()
        ->assertJsonPath('bot_username', 'sakk_otp_bot')
        ->assertJsonStructure(['deep_link', 'linked', 'expires_in_minutes']);

    expect($res->json('deep_link'))->toContain('t.me/sakk_otp_bot?start=');
});
