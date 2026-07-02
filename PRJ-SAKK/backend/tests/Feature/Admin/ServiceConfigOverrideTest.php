<?php

use App\Models\ServiceConfig;
use App\Providers\ServiceConfigOverrideProvider;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * SEV-1 regression guard: ServiceConfig rows for whatsapp/telegram/sms/mail
 * must actually change runtime config() — before this provider they were a
 * write-only admin panel (ServiceConfig::forKey had zero callers). Also
 * locks the zero-downtime fail-open contract (missing table / decrypt
 * failure must never break the app / other keys).
 *
 * The provider runs once at application boot (registered in
 * bootstrap/providers.php), which already happened for the test kernel
 * before this file's rows exist. We re-invoke boot() directly after
 * seeding/mutating rows to exercise it deterministically per-test, exactly
 * mirroring what happens on the NEXT real HTTP request after an admin save.
 */

function reapplyServiceConfigOverrides(): void
{
    (new ServiceConfigOverrideProvider(app()))->boot();
}

afterEach(function () {
    // Overrides mutate the shared config() repository — reset the keys this
    // suite touches so state doesn't leak into other test files.
    config([
        'services.whatsapp.enabled' => env('WHATSAPP_OTP_ENABLED', false),
        'services.telegram.enabled' => env('TELEGRAM_OTP_ENABLED', false),
        'services.telegram.bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'services.sms.enabled' => env('SMS_OTP_ENABLED', false),
        'mail.default' => env('MAIL_MAILER', 'log'),
    ]);
});

it('a non-empty credential value overrides the matching services.* config key', function () {
    ServiceConfig::create([
        'key' => 'telegram', 'name' => 'Telegram', 'name_ar' => 'تلجرام', 'group' => 'messaging',
        'is_active' => true,
        'credentials' => ['bot_token' => 'DB-OVERRIDE-TOKEN'],
        'settings' => [],
    ]);

    reapplyServiceConfigOverrides();

    expect(config('services.telegram.bot_token'))->toBe('DB-OVERRIDE-TOKEN');
});

it('an empty credential value falls back to the existing env-sourced config, not null/empty', function () {
    config(['services.telegram.bot_username' => 'env_bot_username']);

    ServiceConfig::create([
        'key' => 'telegram', 'name' => 'Telegram', 'name_ar' => 'تلجرام', 'group' => 'messaging',
        'is_active' => true,
        'credentials' => ['bot_token' => 'DB-TOKEN', 'bot_username' => ''], // blank -> keep env
        'settings' => [],
    ]);

    reapplyServiceConfigOverrides();

    expect(config('services.telegram.bot_token'))->toBe('DB-TOKEN');
    expect(config('services.telegram.bot_username'))->toBe('env_bot_username');
});

it('is_active=false on an existing row forces the channel disabled even when env has it enabled', function () {
    config(['services.telegram.enabled' => true]); // simulate TELEGRAM_OTP_ENABLED=true in .env
    config(['services.telegram.bot_token' => 'env-configured-token']);

    ServiceConfig::create([
        'key' => 'telegram', 'name' => 'Telegram', 'name_ar' => 'تلجرام', 'group' => 'messaging',
        'is_active' => false, // admin explicitly disabled the channel
        'credentials' => [],
        'settings' => [],
    ]);

    reapplyServiceConfigOverrides();

    expect(config('services.telegram.enabled'))->toBeFalse();
    expect(app(TelegramService::class)->enabled())->toBeFalse();
});

it('a missing ServiceConfig row leaves env behavior completely untouched', function () {
    config(['services.sms.enabled' => true, 'services.sms.endpoint' => 'https://env.example.com']);
    // no ServiceConfig row for 'sms' created

    reapplyServiceConfigOverrides();

    expect(config('services.sms.enabled'))->toBeTrue();
    expect(config('services.sms.endpoint'))->toBe('https://env.example.com');
});

it('mail credentials override mail.mailers.smtp.* and mail.from.*, is_active governs mail.default', function () {
    ServiceConfig::create([
        'key' => 'mail', 'name' => 'Mail', 'name_ar' => 'البريد', 'group' => 'messaging',
        'is_active' => true,
        'credentials' => [
            'mail_host' => 'db.smtp.example.com',
            'mail_port' => '2525',
            'mail_from_address' => 'noreply@db.example.com',
        ],
        'settings' => [],
    ]);

    reapplyServiceConfigOverrides();

    expect(config('mail.mailers.smtp.host'))->toBe('db.smtp.example.com');
    expect(config('mail.mailers.smtp.port'))->toBe('2525');
    expect(config('mail.from.address'))->toBe('noreply@db.example.com');
    expect(config('mail.default'))->toBe('smtp');
});

it('mail is_active=false forces mail.default to log (admin disabled the channel)', function () {
    ServiceConfig::create([
        'key' => 'mail', 'name' => 'Mail', 'name_ar' => 'البريد', 'group' => 'messaging',
        'is_active' => false,
        'credentials' => ['mail_host' => 'db.smtp.example.com'],
        'settings' => [],
    ]);

    reapplyServiceConfigOverrides();

    expect(config('mail.default'))->toBe('log');
});

it('missing service_configs table does not throw and leaves config untouched (installer-before-migrate scenario)', function () {
    config(['services.whatsapp.enabled' => true]);

    // Simulate the installer window: the table genuinely does not exist yet.
    Schema::drop('service_configs');

    expect(fn () => reapplyServiceConfigOverrides())->not->toThrow(\Throwable::class);
    expect(config('services.whatsapp.enabled'))->toBeTrue();
});

it('an undecryptable credentials value (corrupt row / rotated APP_KEY) does not throw and does not block other keys', function () {
    ServiceConfig::create([
        'key' => 'whatsapp', 'name' => 'WhatsApp', 'name_ar' => 'واتساب', 'group' => 'messaging',
        'is_active' => true, 'credentials' => [], 'settings' => [],
    ]);
    // Bypass the model cast to write genuinely undecryptable bytes directly.
    DB::table('service_configs')->where('key', 'whatsapp')->update(['credentials' => 'not-valid-ciphertext']);
    Cache::forget('service_config:whatsapp');

    ServiceConfig::create([
        'key' => 'telegram', 'name' => 'Telegram', 'name_ar' => 'تلجرام', 'group' => 'messaging',
        'is_active' => true,
        'credentials' => ['bot_token' => 'STILL-APPLIES'],
        'settings' => [],
    ]);

    expect(fn () => reapplyServiceConfigOverrides())->not->toThrow(\Throwable::class);
    expect(config('services.telegram.bot_token'))->toBe('STILL-APPLIES');
});

it('ServiceConfig::forKey cache is flushed on save so the next boot() reads fresh data', function () {
    $row = ServiceConfig::create([
        'key' => 'sms', 'name' => 'SMS', 'name_ar' => 'الرسائل', 'group' => 'messaging',
        'is_active' => true, 'credentials' => ['endpoint' => 'https://v1.example.com'], 'settings' => [],
    ]);
    reapplyServiceConfigOverrides();
    expect(config('services.sms.endpoint'))->toBe('https://v1.example.com');

    // forKey()'s Cache::remember populates the cache on read (600s TTL) —
    // this is expected. The bug this test guards against is the cache NOT
    // being invalidated on save, which would make an admin's update never
    // take effect until the TTL naturally expires.
    expect(Cache::has('service_config:sms'))->toBeTrue();

    $row->update(['credentials' => ['endpoint' => 'https://v2.example.com']]);
    expect(Cache::has('service_config:sms'))->toBeFalse(); // update() -> saved hook -> Cache::forget

    reapplyServiceConfigOverrides();
    expect(config('services.sms.endpoint'))->toBe('https://v2.example.com');
});
