<?php

namespace App\Support;

use App\Models\SystemSetting;

/**
 * Pure content + inline-keyboard builder for the @SakkSupportBot menu.
 *
 * No I/O of its own except reading the admin-managed support settings; the
 * controller wires the returned ['text' => ..., 'markup' => ...] into the
 * TelegramSupportService send/edit calls. Bilingual (ar default · en toggle),
 * driven by a stateless callback-data scheme so no per-chat language column is
 * needed: every button carries its own language.
 *
 * Callback-data scheme (kept well under Telegram's 64-byte cap):
 *   m:<lang>          main menu
 *   h:<lang>          help / commands
 *   f:<lang>          FAQ list
 *   q:<lang>:<index>  one FAQ answer
 *   c:<lang>          contact channels
 *   a:<lang>          app download
 *   k:<lang>          account-link instructions
 */
class TelegramMenu
{
    public const LANGS = ['ar', 'en'];

    /** Bilingual FAQ — mirrors the in-app help screen. */
    private const FAQ = [
        [
            'q' => ['ar' => 'كيف أحوّل الأموال لشخص آخر؟', 'en' => 'How do I send money to someone?'],
            'a' => [
                'ar' => 'من الرئيسية اضغط زر QR ثم «إرسال»، أدخل رقم حساب المستلم أو امسح رمزه، ثم المبلغ وأكّد.',
                'en' => 'On Home tap the QR button → “Send”, enter the recipient’s account number or scan their code, then the amount and confirm.',
            ],
        ],
        [
            'q' => ['ar' => 'كم يستغرق وصول التحويل؟', 'en' => 'How long does a transfer take?'],
            'a' => [
                'ar' => 'التحويلات بين مستخدمي صكّ فورية وبدون رسوم.',
                'en' => 'Transfers between SAKK users are instant and free.',
            ],
        ],
        [
            'q' => ['ar' => 'كيف أوثّق حسابي؟', 'en' => 'How do I verify my account?'],
            'a' => [
                'ar' => 'من الإعدادات ← توثيق الهوية، أكمل الخطوات لرفع حدودك وتفعيل كامل الخدمات.',
                'en' => 'Settings → Identity Verification, complete the steps to raise your limits and unlock all services.',
            ],
        ],
        [
            'q' => ['ar' => 'كيف أكسب الكاش باك؟', 'en' => 'How do I earn cashback?'],
            'a' => [
                'ar' => 'تكسب كاش باك ومكافآت على عملياتك تلقائياً، وتظهر في بطاقة «الكاش باك» بالرئيسية.',
                'en' => 'You earn cashback and rewards on your activity automatically — see the “Cashback” card on Home.',
            ],
        ],
        [
            'q' => ['ar' => 'نسيت كيف أستعيد حسابي؟', 'en' => 'How do I recover my account?'],
            'a' => [
                'ar' => 'تواصل مع فريق الدعم هنا أو عبر التطبيق وسنساعدك في استعادة الوصول بأمان.',
                'en' => 'Contact support here or in the app and we’ll help you regain access safely.',
            ],
        ],
    ];

    public static function publicUrl(): string
    {
        return rtrim((string) config('services.telegram_support.public_url', 'https://sakk.zanjour.com'), '/');
    }

    private static function lang(string $lang): string
    {
        return in_array($lang, self::LANGS, true) ? $lang : 'ar';
    }

    // ──────────────────────────── screens ────────────────────────────

    /** Main menu — the home screen for /start, /menu, and every back button. */
    public static function main(string $lang): array
    {
        $lang = self::lang($lang);
        $text = $lang === 'ar'
            ? "🟣 <b>مرحباً بك في دعم صكّ</b>\n\nاختر من القائمة بالأسفل، أو <b>اكتب رسالتك مباشرةً</b> لفتح تذكرة دعم وسيردّ عليك فريقنا."
            : "🟣 <b>Welcome to SAKK Support</b>\n\nPick an option below, or <b>just type your message</b> to open a support ticket and our team will reply.";

        $rows = $lang === 'ar'
            ? [
                [self::btn('❓ الأسئلة الشائعة', "f:ar"), self::btn('📞 تواصل معنا', "c:ar")],
                [self::btn('📲 تحميل التطبيق', "a:ar"), self::btn('🔗 ربط الحساب', "k:ar")],
                [self::urlBtn('💬 تواصل عبر واتساب', self::whatsappUrl())],
                [self::btn('🆘 الأوامر', "h:ar"), self::btn('🌐 English', "m:en")],
            ]
            : [
                [self::btn('❓ FAQ', "f:en"), self::btn('📞 Contact', "c:en")],
                [self::btn('📲 Download app', "a:en"), self::btn('🔗 Link account', "k:en")],
                [self::urlBtn('💬 Chat on WhatsApp', self::whatsappUrl())],
                [self::btn('🆘 Commands', "h:en"), self::btn('🌐 العربية', "m:ar")],
            ];

        return self::screen($text, $rows);
    }

    public static function help(string $lang): array
    {
        $lang = self::lang($lang);
        $text = $lang === 'ar'
            ? "🆘 <b>أوامر البوت</b>\n\n/start — القائمة الرئيسية\n/faq — الأسئلة الشائعة\n/contact — قنوات التواصل\n/app — تحميل التطبيق\n/link — ربط الحساب\n\n💡 أو اكتب مشكلتك مباشرةً لفتح تذكرة دعم."
            : "🆘 <b>Bot commands</b>\n\n/start — main menu\n/faq — frequently asked questions\n/contact — contact channels\n/app — download the app\n/link — link your account\n\n💡 Or just type your issue to open a support ticket.";

        return self::screen($text, [[self::back($lang)]]);
    }

    public static function faqList(string $lang): array
    {
        $lang = self::lang($lang);
        $title = $lang === 'ar' ? "❓ <b>الأسئلة الشائعة</b>\n\nاختر سؤالاً:" : "❓ <b>FAQ</b>\n\nPick a question:";

        $rows = [];
        foreach (self::FAQ as $i => $item) {
            $rows[] = [self::btn($item['q'][$lang], "q:$lang:$i")];
        }
        $rows[] = [self::back($lang)];

        return self::screen($title, $rows);
    }

    public static function faqAnswer(string $lang, int $index): array
    {
        $lang = self::lang($lang);
        $item = self::FAQ[$index] ?? null;
        if ($item === null) {
            return self::faqList($lang);
        }
        $text = "❓ <b>{$item['q'][$lang]}</b>\n\n{$item['a'][$lang]}";
        $rows = [[
            self::btn($lang === 'ar' ? '⬅️ الأسئلة' : '⬅️ Questions', "f:$lang"),
            self::btn($lang === 'ar' ? '🏠 الرئيسية' : '🏠 Home', "m:$lang"),
        ]];

        return self::screen($text, $rows);
    }

    public static function contact(string $lang): array
    {
        $lang = self::lang($lang);
        $wa = self::supportWhatsapp();
        $phone = (string) SystemSetting::get('support_phone', '');
        $email = (string) SystemSetting::get('support_email', '');
        $hours = (string) SystemSetting::get('support_hours', '');

        if ($lang === 'ar') {
            $lines = ["📞 <b>قنوات تواصل دعم صكّ</b>", ''];
            if ($wa !== '') {
                $lines[] = "💬 واتساب: <code>{$wa}</code>";
            }
            if ($phone !== '') {
                $lines[] = "☎️ هاتف: <code>{$phone}</code>";
            }
            if ($email !== '') {
                $lines[] = "✉️ بريد: <code>{$email}</code>";
            }
            if ($hours !== '') {
                $lines[] = "🕐 ساعات العمل: {$hours}";
            }
            $lines[] = '';
            $lines[] = '💡 أو اكتب رسالتك هنا لفتح تذكرة فوراً.';
        } else {
            $lines = ["📞 <b>SAKK support channels</b>", ''];
            if ($wa !== '') {
                $lines[] = "💬 WhatsApp: <code>{$wa}</code>";
            }
            if ($phone !== '') {
                $lines[] = "☎️ Phone: <code>{$phone}</code>";
            }
            if ($email !== '') {
                $lines[] = "✉️ Email: <code>{$email}</code>";
            }
            if ($hours !== '') {
                $lines[] = "🕐 Hours: {$hours}";
            }
            $lines[] = '';
            $lines[] = '💡 Or just type your message here to open a ticket.';
        }

        $rows = [];
        if ($wa !== '') {
            $rows[] = [self::urlBtn($lang === 'ar' ? '💬 فتح واتساب' : '💬 Open WhatsApp', self::whatsappUrl())];
        }
        $rows[] = [self::back($lang)];

        return self::screen(implode("\n", $lines), $rows);
    }

    public static function app(string $lang): array
    {
        $lang = self::lang($lang);
        $base = self::publicUrl();
        $text = $lang === 'ar'
            ? "📲 <b>تطبيق صكّ</b>\n\nحمّل أحدث نسخة لأندرويد، أو زر موقعنا للمزيد."
            : "📲 <b>SAKK app</b>\n\nDownload the latest Android build, or visit our site for more.";

        $rows = [
            [self::urlBtn($lang === 'ar' ? '⬇️ تحميل APK' : '⬇️ Download APK', "{$base}/download/sakk.apk?v=1.0.0-1")],
            [self::urlBtn($lang === 'ar' ? '🌐 الموقع' : '🌐 Website', $base)],
            [self::back($lang)],
        ];

        return self::screen($text, $rows);
    }

    public static function link(string $lang): array
    {
        $lang = self::lang($lang);
        $text = $lang === 'ar'
            ? "🔗 <b>ربط حسابك بتلجرام</b>\n\nلتتابع تذاكرك وتصلك الإشعارات هنا:\n<b>التطبيق ← الأمان ← ربط تلجرام</b>\n\nبعد الربط، اكتب رسالتك في أي وقت لفتح تذكرة دعم."
            : "🔗 <b>Link your account to Telegram</b>\n\nTo track your tickets and get notified here:\n<b>App → Security → Link Telegram</b>\n\nOnce linked, type your message anytime to open a support ticket.";

        $rows = [
            [self::btn($lang === 'ar' ? '📲 تحميل التطبيق' : '📲 Download app', "a:$lang")],
            [self::back($lang)],
        ];

        return self::screen($text, $rows);
    }

    /** Short ticket-opened prompt used when a bare /start arrives. */
    public static function greeting(string $lang): array
    {
        return self::main($lang);
    }

    // ──────────────────────────── helpers ────────────────────────────

    private static function screen(string $text, array $rows): array
    {
        return ['text' => $text, 'markup' => ['inline_keyboard' => $rows]];
    }

    private static function btn(string $text, string $data): array
    {
        return ['text' => $text, 'callback_data' => $data];
    }

    private static function urlBtn(string $text, string $url): array
    {
        return ['text' => $text, 'url' => $url];
    }

    private static function back(string $lang): array
    {
        return self::btn($lang === 'ar' ? '⬅️ رجوع' : '⬅️ Back', "m:$lang");
    }

    private static function supportWhatsapp(): string
    {
        return (string) SystemSetting::get('support_whatsapp', '');
    }

    private static function whatsappUrl(): string
    {
        $digits = preg_replace('/[^0-9]/', '', self::supportWhatsapp());
        $greeting = rawurlencode('مرحباً 👋 أحتاج مساعدة من دعم صكّ.');
        return $digits !== '' ? "https://wa.me/{$digits}?text={$greeting}" : 'https://wa.me/';
    }
}
