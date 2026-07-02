<?php

namespace Database\Seeders;

use App\Models\Integration;
use Illuminate\Database\Seeder;

/**
 * Seeds the Messaging (SMS/Email) integration, docs, and templates.
 */
class MessagingSeeder extends Seeder
{
    use SeedsIntegrationContent;

    public function run(): void
    {
        $integration = Integration::firstOrCreate(['key' => 'messaging'], [
            'key' => 'messaging',
            'name' => 'Messaging',
            'name_ar' => 'الرسائل',
            'description' => 'SMS and email messaging services integration.',
            'description_ar' => 'تكامل خدمات الرسائل النصية والبريد الإلكتروني.',
            'icon' => 'message',
            'category' => 'messaging',
            'is_active' => true,
            'is_visible' => true,
            'config' => [
                'default_sms_provider' => 'twilio',
                'default_email_provider' => 'smtp',
                'sms_enabled' => true,
                'email_enabled' => true,
                'otp_length' => 6,
                'otp_expiry' => 300,
            ],
            'credentials' => [
                'twilio_sid' => '',
                'twilio_token' => '',
                'twilio_from' => '',
                'vonage_key' => '',
                'vonage_secret' => '',
                'vonage_from' => '',
                'mail_host' => '',
                'mail_port' => '587',
                'mail_username' => '',
                'mail_password' => '',
                'mail_from_address' => '',
                'mail_from_name' => 'SAKK',
            ],
            'settings' => [
                'timeout' => 30,
                'retry_attempts' => 3,
            ],
        ]);

    }
}
