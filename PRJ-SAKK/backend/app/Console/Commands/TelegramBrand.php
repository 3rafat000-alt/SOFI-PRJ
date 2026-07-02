<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

/**
 * One-shot bot branding: name, About text, short description and the command
 * menu for @SakkWalletOtpBot. Run after the token is set. The profile photo is
 * the only piece the Bot API can't set — do that once in @BotFather (/setuserpic).
 *
 *   php artisan telegram:brand
 */
class TelegramBrand extends Command
{
    protected $signature = 'telegram:brand';
    protected $description = 'Apply SAKK branding (name, description, commands) to the OTP bot';

    private const NAME = 'صكّ · SAKK — التحقق';

    private const SHORT = 'بوت رموز التحقق الرسمي لمحفظة صكّ (SAKK). آمن وسريع.';

    private const ABOUT = "بوت رموز التحقق الرسمي لمحفظة صكّ (SAKK).\n"
        . "يصلك هنا رمز الدخول عند تفعيل تلجرام كقناة تحقق.\n\n"
        . "للربط: التطبيق ← الأمان ← ربط تلجرام\n"
        . "🔒 لن نطلب منك كلمة المرور أو الرمز أبداً.";

    public function handle(TelegramService $telegram): int
    {
        if (!$telegram->configured()) {
            $this->error('TELEGRAM_BOT_TOKEN is not set.');
            return self::FAILURE;
        }

        $steps = [
            'name' => fn () => $telegram->setMyName(self::NAME),
            'about' => fn () => $telegram->setMyDescription(self::ABOUT),
            'short description' => fn () => $telegram->setMyShortDescription(self::SHORT),
            'commands' => fn () => $telegram->setMyCommands([
                ['command' => 'start', 'description' => 'بدء وربط الحساب'],
            ]),
        ];

        $ok = true;
        foreach ($steps as $label => $apply) {
            $done = $apply();
            $this->line(($done ? '<info>✓</info>' : '<error>✗</error>') . " {$label}");
            $ok = $ok && $done;
        }

        if (!$ok) {
            $this->warn('Some branding calls failed (Telegram rate-limits name changes — retry later).');
            return self::FAILURE;
        }

        $this->info('Bot branding applied. Set the profile photo in @BotFather → /setuserpic.');
        return self::SUCCESS;
    }
}
