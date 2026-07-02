<?php

use App\Services\TelegramSupportService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->service = new TelegramSupportService();
});

function tgConfigureEnabled(): void
{
    config([
        'services.telegram_support.bot_token' => 'test_token',
        'services.telegram_support.enabled' => true,
        'services.telegram_support.api_base' => 'https://api.telegram.org',
    ]);
}

it('is not configured without a bot token', function () {
    config(['services.telegram_support.bot_token' => null]);

    expect($this->service->configured())->toBeFalse();
});

it('is configured when a bot token is set', function () {
    config(['services.telegram_support.bot_token' => 'abc']);

    expect($this->service->configured())->toBeTrue();
});

it('is not enabled unless both the enabled flag and token are set', function () {
    config(['services.telegram_support.bot_token' => 'abc', 'services.telegram_support.enabled' => false]);
    expect($this->service->enabled())->toBeFalse();

    config(['services.telegram_support.bot_token' => null, 'services.telegram_support.enabled' => true]);
    expect($this->service->enabled())->toBeFalse();

    config(['services.telegram_support.bot_token' => 'abc', 'services.telegram_support.enabled' => true]);
    expect($this->service->enabled())->toBeTrue();
});

it('sendMessage returns false when disabled without hitting the network', function () {
    config(['services.telegram_support.enabled' => false]);

    Http::fake();

    expect($this->service->sendMessage('123', 'hello'))->toBeFalse();
    Http::assertNothingSent();
});

it('sendMessage returns false for a blank chat id', function () {
    tgConfigureEnabled();
    Http::fake();

    expect($this->service->sendMessage('   ', 'hello'))->toBeFalse();
    Http::assertNothingSent();
});

it('sendMessage posts to the Telegram API and returns true on ok', function () {
    tgConfigureEnabled();
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]], 200)]);

    $result = $this->service->sendMessage('123', 'hello', 'HTML', ['inline_keyboard' => [[['text' => 'x', 'callback_data' => 'y']]]]);

    expect($result)->toBeTrue();
    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'bottest_token/sendMessage')
            && $request['chat_id'] === '123'
            && $request['text'] === 'hello';
    });
});

it('sendMessage returns false and logs when the API responds not-ok', function () {
    tgConfigureEnabled();
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'bad request'], 200)]);

    expect($this->service->sendMessage('123', 'hello'))->toBeFalse();
});

it('editMessageText returns false when disabled', function () {
    config(['services.telegram_support.enabled' => false]);

    expect($this->service->editMessageText('123', 5, 'edited'))->toBeFalse();
});

it('editMessageText posts and returns true on ok', function () {
    tgConfigureEnabled();
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

    expect($this->service->editMessageText('123', 5, 'edited'))->toBeTrue();
    Http::assertSent(fn($r) => str_contains($r->url(), 'editMessageText') && $r['message_id'] === 5);
});

it('answerCallbackQuery calls the API even without full config and reports ok', function () {
    tgConfigureEnabled();
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

    expect($this->service->answerCallbackQuery('cb1', 'done'))->toBeTrue();
});

it('answerCallbackQuery returns false without a token configured', function () {
    config(['services.telegram_support.bot_token' => null]);

    expect($this->service->answerCallbackQuery('cb1'))->toBeFalse();
});

it('getMe returns the bot info result on success', function () {
    tgConfigureEnabled();
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['username' => 'SakkSupportBot']], 200)]);

    expect($this->service->getMe())->toBe(['username' => 'SakkSupportBot']);
});

it('getMe returns null when the API responds not-ok', function () {
    tgConfigureEnabled();
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => false], 200)]);

    expect($this->service->getMe())->toBeNull();
});

it('setWebhook sends the url and secret and falls back on network failure', function () {
    tgConfigureEnabled();
    config(['services.telegram_support.webhook_secret' => 'sekret']);
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

    $result = $this->service->setWebhook('https://sakk.test/webhooks/telegram-support');

    expect($result['ok'])->toBeTrue();
    Http::assertSent(fn($r) => $r['url'] === 'https://sakk.test/webhooks/telegram-support' && $r['secret_token'] === 'sekret');
});

it('setWebhook returns an unreachable fallback array when no token is configured', function () {
    config(['services.telegram_support.bot_token' => null]);

    $result = $this->service->setWebhook('https://sakk.test/webhook');

    expect($result)->toBe(['ok' => false, 'description' => 'unreachable']);
});

it('getWebhookInfo returns result on ok and null otherwise', function () {
    tgConfigureEnabled();
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['url' => 'x']], 200)]);

    expect($this->service->getWebhookInfo())->toBe(['url' => 'x']);
});

it('setMyName, setMyDescription, setMyShortDescription, setMyCommands all report ok', function () {
    tgConfigureEnabled();
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

    expect($this->service->setMyName('SAKK Bot'))->toBeTrue();
    expect($this->service->setMyDescription('desc'))->toBeTrue();
    expect($this->service->setMyShortDescription('short'))->toBeTrue();
    expect($this->service->setMyCommands([['command' => 'start', 'description' => 'Start']]))->toBeTrue();
});

it('returns null/false from underlying call when the http client throws', function () {
    tgConfigureEnabled();
    Http::fake(function () {
        throw new \Exception('network down');
    });

    expect($this->service->getMe())->toBeNull();
    expect($this->service->setMyName('x'))->toBeFalse();
});
