<?php

namespace Database\Seeders;

use App\Models\NotificationChannel;
use App\Models\NotificationTemplate;
use App\Models\ServiceConfig;
use Illuminate\Database\Seeder;

class SystemConfigSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedServiceConfigs();
        $this->seedNotificationChannels();
        $this->seedTemplates();
    }

    // ── Section 1: 3rd-party & security ──
    private function seedServiceConfigs(): void
    {
        $services = [
            ['key' => 'sms', 'name' => 'SMS Gateway', 'name_ar' => 'بوابة الرسائل', 'group' => 'messaging', 'icon' => 'sms', 'driver' => 'twilio',
                'settings' => ['otp_length' => 6, 'otp_ttl' => 300]],
            ['key' => 'mail', 'name' => 'Mail / SMTP', 'name_ar' => 'البريد الإلكتروني', 'group' => 'messaging', 'icon' => 'mail', 'driver' => 'smtp',
                'settings' => ['from_name' => 'SAKK', 'monthly_reports' => true]],
            ['key' => 'firebase_otp', 'name' => 'Firebase Phone OTP', 'name_ar' => 'تحقق الهاتف (Firebase)', 'group' => 'security', 'icon' => 'verified_user', 'driver' => 'firebase',
                'settings' => ['enabled_for_login' => true, 'enabled_for_sensitive' => true]],
            ['key' => 'recaptcha', 'name' => 'Google reCAPTCHA', 'name_ar' => 'حماية reCAPTCHA', 'group' => 'security', 'icon' => 'security', 'driver' => 'google',
                'settings' => ['version' => 'v3', 'min_score' => 0.5, 'enabled_on' => ['login', 'register', 'forgot_password']]],
            ['key' => 'whatsapp', 'name' => 'WhatsApp OTP (OpenWA)', 'name_ar' => 'واتساب — رمز التحقق', 'group' => 'messaging', 'icon' => 'chat', 'driver' => 'openwa',
                'settings' => ['default_country' => '963', 'channel' => 'otp']],
            ['key' => 'telegram', 'name' => 'Telegram OTP', 'name_ar' => 'تلجرام — رمز التحقق', 'group' => 'messaging', 'icon' => 'send', 'driver' => 'telegram',
                'settings' => ['channel' => 'otp']],
        ];

        foreach ($services as $s) {
            // is_active mirrors the CURRENT .env-driven enabled state for
            // whatsapp/telegram/sms/mail on first creation only — this is a
            // zero-downtime seed, not a config migration: it never copies
            // secrets (credentials always start empty; env fallback in
            // ServiceConfigOverrideProvider keeps the live channel working),
            // and firstOrCreate never touches a row that already exists.
            ServiceConfig::firstOrCreate(['key' => $s['key']], array_merge($s, [
                'is_active' => $this->defaultIsActive($s['key']),
                'credentials' => [], // filled by admin; stored encrypted
            ]));
        }
    }

    /** Mirrors config('services.<key>.enabled') for OTP channels; mail is active when an SMTP host is already configured via .env. */
    private function defaultIsActive(string $key): bool
    {
        return match ($key) {
            'whatsapp' => (bool) config('services.whatsapp.enabled', false),
            'telegram' => (bool) config('services.telegram.enabled', false),
            'sms' => (bool) config('services.sms.enabled', false),
            'mail' => filled(config('mail.mailers.smtp.host')),
            default => false,
        };
    }

    // ── Section 2: notification channel matrix ──
    private function seedNotificationChannels(): void
    {
        // event => [label_ar, [recipients], default channels]
        $events = [
            'forgot_password' => ['نسيان كلمة المرور', ['customer', 'merchant', 'agent'], ['email', 'sms']],
            'pin_change' => ['تغيير رمز PIN المالي', ['customer', 'merchant'], ['email', 'sms', 'push']],
            'withdraw_request' => ['طلب سحب أموال', ['customer', 'merchant', 'admin'], ['email', 'push', 'in_app']],
            'refund_request' => ['طلب استرداد مرتجع', ['customer', 'merchant', 'admin'], ['email', 'in_app']],
            'deposit_alert' => ['تنبيه إيداع / شحن ذاتي', ['customer', 'merchant'], ['sms', 'push', 'in_app']],
        ];

        $labels = [
            'forgot_password' => 'Forgot Password',
            'pin_change' => 'PIN Change',
            'withdraw_request' => 'Withdraw Request',
            'refund_request' => 'Refund Request',
            'deposit_alert' => 'Deposit Alert',
        ];

        foreach ($events as $key => [$labelAr, $recipients, $channels]) {
            foreach ($recipients as $recipient) {
                NotificationChannel::firstOrCreate(
                    ['event_key' => $key, 'recipient' => $recipient],
                    [
                        'event_label' => $labels[$key],
                        'event_label_ar' => $labelAr,
                        'via_email' => in_array('email', $channels),
                        'via_sms' => in_array('sms', $channels),
                        'via_push' => in_array('push', $channels),
                        'via_in_app' => in_array('in_app', $channels),
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    // ── Section 3: message templates with %variables% ──
    private function seedTemplates(): void
    {
        $templates = [
            [
                'code' => 'recharge_confirm', 'event_key' => 'deposit_alert', 'recipient' => 'customer',
                'name' => 'تأكيد الشحن', 'channel' => 'push',
                'subject' => 'Wallet recharged', 'subject_ar' => 'تم شحن محفظتك',
                'body' => 'Hi %user_name%, %amount% was added to your wallet. Ref: %transaction_id%.',
                'body_ar' => 'مرحباً %user_name%، تم إيداع %amount% في محفظتك بنجاح. رقم العملية: %transaction_id%.',
                'variables' => ['user_name', 'amount', 'transaction_id'],
            ],
            [
                'code' => 'transfer_success', 'event_key' => 'transfer', 'recipient' => 'customer',
                'name' => 'نجاح تحويل الأموال', 'channel' => 'push',
                'subject' => 'Transfer successful', 'subject_ar' => 'تم التحويل بنجاح',
                'body' => 'Dear %user_name%, %amount% was transferred successfully. Ref: %transaction_id%.',
                'body_ar' => 'عزيزي %user_name%، تم تحويل %amount% بنجاح. رقم العملية: %transaction_id%.',
                'variables' => ['user_name', 'amount', 'transaction_id'],
            ],
            [
                'code' => 'operation_failed', 'event_key' => 'operation_failed', 'recipient' => 'customer',
                'name' => 'فشل العملية', 'channel' => 'push',
                'subject' => 'Operation failed', 'subject_ar' => 'تعذّر إتمام العملية',
                'body' => 'Sorry %user_name%, operation %transaction_id% for %amount% failed. Please try again.',
                'body_ar' => 'عذراً %user_name%، فشلت العملية رقم %transaction_id% بقيمة %amount%. يرجى المحاولة لاحقاً.',
                'variables' => ['user_name', 'amount', 'transaction_id'],
            ],
            [
                'code' => 'daily_limit_exceeded', 'event_key' => 'daily_limit_exceeded', 'recipient' => 'customer',
                'name' => 'تجاوز الحد المالي اليومي', 'channel' => 'push',
                'subject' => 'Daily limit exceeded', 'subject_ar' => 'تنبيه: تجاوز الحد اليومي',
                'body' => 'Hi %user_name%, an operation of %amount% exceeds your daily limit.',
                'body_ar' => 'مرحباً %user_name%، حاولت تنفيذ عملية بقيمة %amount% تتجاوز حدك اليومي المسموح.',
                'variables' => ['user_name', 'amount'],
            ],
        ];

        foreach ($templates as $t) {
            NotificationTemplate::updateOrCreate(['code' => $t['code']], array_merge($t, ['is_active' => true]));
        }
    }
}
