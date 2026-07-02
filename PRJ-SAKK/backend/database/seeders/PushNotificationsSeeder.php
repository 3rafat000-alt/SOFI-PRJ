<?php

namespace Database\Seeders;

use App\Models\Integration;
use Illuminate\Database\Seeder;

/**
 * Seeds the Push Notifications integration, docs, and templates.
 */
class PushNotificationsSeeder extends Seeder
{
    use SeedsIntegrationContent;

    public function run(): void
    {
        $integration = Integration::firstOrCreate(['key' => 'notifications'], [
            'key' => 'notifications',
            'name' => 'Push Notifications',
            'name_ar' => 'الإشعارات',
            'description' => 'Push notification services for mobile and web.',
            'description_ar' => 'خدمات الإشعارات للجوال والويب.',
            'icon' => 'notifications',
            'category' => 'notifications',
            'is_active' => true,
            'is_visible' => true,
            'config' => [
                'push_enabled' => true,
                'in_app_enabled' => true,
                'sound_enabled' => true,
                'badge_enabled' => true,
                'group_similar' => true,
                'max_per_hour' => 10,
            ],
            'credentials' => [
                // FCM HTTP v1 — paste the full service-account JSON here (SECRET).
                'fcm_service_account' => '',
                'fcm_project_id' => 'skk-wallet',
                'onesignal_app_id' => '',
                'onesignal_rest_key' => '',
                'pusher_app_id' => '',
                'pusher_key' => '',
                'pusher_secret' => '',
                'pusher_cluster' => '',
            ],
            'settings' => [
                'provider' => 'fcm',
                'environment' => 'production',
                'timeout' => 30,
            ],
        ]);

    }
}
