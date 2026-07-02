<?php

use App\Models\SystemSetting;
use App\Support\TelegramMenu;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('builds the main menu in arabic by default and falls back for unknown langs', function () {
    $ar = TelegramMenu::main('ar');
    $fallback = TelegramMenu::main('fr');

    expect($ar['text'])->toContain('دعم صكّ');
    expect($ar['markup']['inline_keyboard'])->toBeArray();
    expect($fallback)->toBe($ar);
});

it('builds the main menu in english', function () {
    $en = TelegramMenu::main('en');

    expect($en['text'])->toContain('SAKK Support');
    expect($en['markup']['inline_keyboard'][3][1]['callback_data'])->toBe('m:ar');
});

it('builds the help screen for both languages', function () {
    $ar = TelegramMenu::help('ar');
    $en = TelegramMenu::help('en');

    expect($ar['text'])->toContain('أوامر البوت');
    expect($en['text'])->toContain('Bot commands');
    expect($ar['markup']['inline_keyboard'][0][0]['callback_data'])->toBe('m:ar');
});

it('builds the FAQ list with a button per question plus a back button', function () {
    $list = TelegramMenu::faqList('ar');

    expect($list['markup']['inline_keyboard'])->toHaveCount(6); // 5 FAQ + back
    expect($list['markup']['inline_keyboard'][0][0]['callback_data'])->toBe('q:ar:0');
});

it('builds a FAQ answer for a valid index', function () {
    $answer = TelegramMenu::faqAnswer('ar', 0);

    expect($answer['text'])->toContain('كيف أحوّل الأموال');
    expect($answer['markup']['inline_keyboard'][0])->toHaveCount(2);
});

it('falls back to the FAQ list for an out-of-range FAQ index', function () {
    $answer = TelegramMenu::faqAnswer('en', 999);
    $list = TelegramMenu::faqList('en');

    expect($answer)->toBe($list);
});

it('builds the contact screen including phone/email/hours/whatsapp when configured', function () {
    SystemSetting::set('support_whatsapp', '+963999999999', 'string');
    SystemSetting::set('support_phone', '+963888888888', 'string');
    SystemSetting::set('support_email', 'help@sakk.test', 'string');
    SystemSetting::set('support_hours', '9am-5pm', 'string');

    $ar = TelegramMenu::contact('ar');

    expect($ar['text'])->toContain('963999999999');
    expect($ar['text'])->toContain('963888888888');
    expect($ar['text'])->toContain('help@sakk.test');
    expect($ar['text'])->toContain('9am-5pm');
    // WhatsApp URL button present
    expect($ar['markup']['inline_keyboard'][0][0])->toHaveKey('url');
});

it('builds the contact screen without optional lines when settings are empty', function () {
    $en = TelegramMenu::contact('en');

    expect($en['text'])->not->toContain('WhatsApp:');
    expect($en['text'])->not->toContain('Phone:');
    // No WhatsApp button row when unset, just back button
    expect($en['markup']['inline_keyboard'])->toHaveCount(1);
});

it('builds the app download screen with the configured public url', function () {
    config(['services.telegram_support.public_url' => 'https://example.test/']);

    $ar = TelegramMenu::app('ar');

    expect($ar['markup']['inline_keyboard'][0][0]['url'])->toBe('https://example.test/download/sakk.apk?v=1.0.0-1');
    expect($ar['markup']['inline_keyboard'][1][0]['url'])->toBe('https://example.test');
});

it('builds the account-link instructions screen', function () {
    $en = TelegramMenu::link('en');

    expect($en['text'])->toContain('Link your account');
    expect($en['markup']['inline_keyboard'][0][0]['callback_data'])->toBe('a:en');
});

it('greeting is an alias for the main menu', function () {
    expect(TelegramMenu::greeting('ar'))->toBe(TelegramMenu::main('ar'));
});

it('publicUrl trims a trailing slash off the configured url', function () {
    config(['services.telegram_support.public_url' => 'https://example.test/']);
    expect(TelegramMenu::publicUrl())->toBe('https://example.test');
});

it('whatsapp url falls back to the bare wa.me link when unconfigured', function () {
    $en = TelegramMenu::main('en');
    $waButtonRow = collect($en['markup']['inline_keyboard'])
        ->flatten(1)
        ->firstWhere('text', '💬 Chat on WhatsApp');

    expect($waButtonRow['url'])->toBe('https://wa.me/');
});

it('whatsapp url strips non-digits and appends a prefilled greeting', function () {
    SystemSetting::set('support_whatsapp', '+963 999 999 999', 'string');

    $en = TelegramMenu::main('en');
    $waButtonRow = collect($en['markup']['inline_keyboard'])
        ->flatten(1)
        ->firstWhere('text', '💬 Chat on WhatsApp');

    expect($waButtonRow['url'])->toStartWith('https://wa.me/963999999999?text=');
});
