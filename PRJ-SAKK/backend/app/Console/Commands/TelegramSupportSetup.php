<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\TelegramSupportService;
use Illuminate\Console\Command;

/**
 * One-shot setup for the support bot (@SakkSupportBot): register its webhook
 * and apply branding. Run after TELEGRAM_SUPPORT_BOT_TOKEN is set.
 *
 *   php artisan telegram:support-setup
 *   php artisan telegram:support-setup --url=https://sakk.zanjour.com/api/v1/telegram/support/webhook
 */
class TelegramSupportSetup extends Command
{
    protected $signature = 'telegram:support-setup {--url= : Override the webhook URL}';
    protected $description = 'Register the support bot webhook + apply branding';

    private const NAME = 'صكّ · SAKK — الدعم';
    private const SHORT = 'الدعم الفني لمحفظة صكّ (SAKK). أرسل مشكلتك وسنرد عليك.';
    private const ABOUT = "الدعم الفني الرسمي لمحفظة صكّ (SAKK).\n"
        . "أرسل رسالتك هنا وسيتم فتح تذكرة دعم يتابعها فريقنا.\n\n"
        . "اربط حسابك من: التطبيق ← الأمان ← ربط تلجرام.";

    public function handle(TelegramSupportService $support): int
    {
        if (!$support->configured()) {
            $this->error('TELEGRAM_SUPPORT_BOT_TOKEN is not set.');
            return self::FAILURE;
        }

        $me = $support->getMe();
        if (!$me) {
            $this->error('Could not reach the support bot (getMe failed). Check the token.');
            return self::FAILURE;
        }
        $this->info("Bot: @{$me['username']} (id {$me['id']})");

        $url = (string) ($this->option('url') ?: rtrim((string) config('app.url'), '/') . '/api/v1/telegram/support/webhook');
        $res = $support->setWebhook($url);
        if (!($res['ok'] ?? false)) {
            $this->error('setWebhook failed: ' . ($res['description'] ?? 'unknown'));
            return self::FAILURE;
        }
        $this->info("Webhook registered: {$url}");

        $support->setMyName(self::NAME);
        $support->setMyDescription(self::ABOUT);
        $support->setMyShortDescription(self::SHORT);
        $support->setMyCommands([['command' => 'start', 'description' => 'بدء محادثة الدعم']]);
        $this->info('Branding applied. Set the profile photo in @BotFather → /setuserpic.');

        return self::SUCCESS;
    }
}
