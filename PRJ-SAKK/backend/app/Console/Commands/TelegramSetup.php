<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

/**
 * One-time devops action: point the Telegram bot's webhook at this app and
 * confirm the bot identity. Run after TELEGRAM_BOT_TOKEN is set in .env.
 *
 *   php artisan telegram:setup
 *   php artisan telegram:setup --url=https://sakk.zanjour.com/api/v1/telegram/webhook
 */
class TelegramSetup extends Command
{
    protected $signature = 'telegram:setup {--url= : Override the webhook URL (defaults to APP_URL + /api/v1/telegram/webhook)}';
    protected $description = 'Register the Telegram bot webhook and verify the bot identity';

    public function handle(TelegramService $telegram): int
    {
        if (!$telegram->configured()) {
            $this->error('TELEGRAM_BOT_TOKEN is not set. Add it to .env first.');
            return self::FAILURE;
        }

        $me = $telegram->getMe();
        if (!$me) {
            $this->error('Could not reach Telegram (getMe failed). Check the token / network.');
            return self::FAILURE;
        }
        $this->info("Bot: @{$me['username']} (id {$me['id']})");

        $url = (string) ($this->option('url') ?: rtrim((string) config('app.url'), '/') . '/api/v1/telegram/webhook');
        $this->line("Registering webhook: {$url}");

        $res = $telegram->setWebhook($url);
        if (!($res['ok'] ?? false)) {
            $this->error('setWebhook failed: ' . ($res['description'] ?? 'unknown error'));
            return self::FAILURE;
        }
        $this->info('Webhook registered.');

        $info = $telegram->getWebhookInfo();
        if ($info) {
            $this->line('Current URL: ' . ($info['url'] ?? '—'));
            $this->line('Pending updates: ' . ($info['pending_update_count'] ?? 0));
        }

        return self::SUCCESS;
    }
}
