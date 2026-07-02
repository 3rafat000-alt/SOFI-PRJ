<?php

namespace App\Providers;

use App\Models\ServiceConfig;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

/**
 * SEV-1 fix: makes admin-panel edits to ServiceConfig rows (`whatsapp`,
 * `telegram`, `sms`, `mail`) actually take effect at runtime. Before this
 * provider, ServiceConfig::forKey() had zero callers — every runtime service
 * (WhatsAppService, TelegramService, SmsService, mail) read `.env`/config
 * only, so admin edits silently changed nothing.
 *
 * Zero-downtime contract (binding — do not weaken):
 *  - VALUE-LEVEL override only: a DB row's NON-EMPTY credential/setting
 *    overrides the matching `services.*` (or `mail.*`) config key; any
 *    empty/missing DB value leaves the `.env`-sourced value exactly as-is.
 *  - Enabled-state: if a ServiceConfig row EXISTS, its `is_active` governs
 *    the channel's `enabled` flag outright (including forcing it OFF even
 *    when `.env` says on). If the row does NOT exist, `.env` behavior is
 *    completely untouched (no row = no opinion).
 *  - This must NEVER take the app down. The whole boot body is guarded:
 *    DB unreachable, `service_configs` table missing (installer runs BEFORE
 *    migrations), or decryption throwing (wrong APP_KEY) all silently no-op.
 *  - OTP delivery (WhatsApp/Telegram, live today) is not disabled or
 *    rerouted by this provider — it can only be tightened (forced off) by
 *    an admin explicitly setting is_active=false on that channel's row, the
 *    same authority the panel already implies.
 *
 * Timing note: config() overrides here apply on the NEXT request after an
 * admin save (ServiceConfig::forKey's cache — key `service_config:{key}`,
 * 600s TTL — is flushed on save via the model's `saved`/`deleted` boot
 * hooks, so the very next boot() re-reads fresh). Long-running queue
 * workers hold config in memory for the life of the worker process and
 * need a restart (`php artisan queue:restart`) to pick up a change.
 */
class ServiceConfigOverrideProvider extends ServiceProvider
{
    /** @var list<string> */
    private const KEYS = ['whatsapp', 'telegram', 'sms', 'mail'];

    public function boot(): void
    {
        try {
            if (!Schema::hasTable('service_configs')) {
                return; // installer runs before migrations — table may not exist yet
            }
        } catch (\Throwable $e) {
            // DB unreachable at boot -> fail-open, .env config untouched.
            report($e);
            return;
        }

        foreach (self::KEYS as $key) {
            // Each key isolated: one corrupt/undecryptable row (e.g. wrong
            // APP_KEY after a rotation, or a hand-edited row) must not skip
            // the remaining keys' overrides.
            try {
                $this->applyOverride($key);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    private function applyOverride(string $key): void
    {
        $row = ServiceConfig::forKey($key);
        if (!$row) {
            return; // no row on this DB -> .env behavior untouched
        }

        $credentials = $row->credentials ?? [];
        $settings = $row->settings ?? [];

        if ($key === 'mail') {
            $this->overrideMail($credentials, $row->is_active);
            return;
        }

        // whatsapp / telegram / sms all live under services.<key>.*
        $overrides = [];

        foreach ($credentials as $field => $value) {
            if ($value === null || $value === '') {
                continue; // empty DB value -> keep .env value
            }
            $overrides["services.{$key}.{$field}"] = $value;
        }

        foreach ($settings as $field => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $overrides["services.{$key}.{$field}"] = $value;
        }

        // is_active on an existing row is authoritative for the enabled flag,
        // including forcing it OFF even when .env has it on.
        $overrides["services.{$key}.enabled"] = (bool) $row->is_active;

        if (!empty($overrides)) {
            config($overrides);
        }
    }

    /**
     * Mail is special-cased: it has no `services.mail.*` namespace — it
     * lives under `mail.mailers.smtp.*` + `mail.from.*`.
     *
     * @param  array<string,mixed>  $credentials
     */
    private function overrideMail(array $credentials, bool $isActive): void
    {
        $overrides = [];

        $map = [
            'mail_host' => 'mail.mailers.smtp.host',
            'mail_port' => 'mail.mailers.smtp.port',
            'mail_username' => 'mail.mailers.smtp.username',
            'mail_password' => 'mail.mailers.smtp.password',
            'mail_from_address' => 'mail.from.address',
            'mail_from_name' => 'mail.from.name',
        ];

        foreach ($map as $field => $configKey) {
            $value = $credentials[$field] ?? null;
            if ($value === null || $value === '') {
                continue; // empty DB value -> keep .env value
            }
            $overrides[$configKey] = $value;
        }

        // is_active on an existing 'mail' row governs which mailer is used:
        // active -> force smtp (the DB-configured transport); inactive ->
        // force 'log' so admin-disabling the channel actually stops sends
        // without touching MAIL_MAILER in .env.
        $overrides['mail.default'] = $isActive ? 'smtp' : 'log';

        if (!empty($overrides)) {
            config($overrides);
        }
    }
}
