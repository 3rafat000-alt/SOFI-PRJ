<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'site_name_ar',     'value' => 'سورية هومز',           'group' => 'general', 'is_public' => true,  'description' => 'اسم الموقع بالعربية'],
            ['key' => 'site_name_en',     'value' => 'Syria Homes',          'group' => 'general', 'is_public' => true,  'description' => 'Site name in English'],
            ['key' => 'site_description',  'value' => 'أول سوق عقاري سوري شامل', 'group' => 'general', 'is_public' => true,  'description' => 'Meta description'],
            ['key' => 'contact_email',    'value' => 'info@syriahomes.sy',   'group' => 'contact', 'is_public' => true,  'description' => 'Public contact email'],
            ['key' => 'contact_phone',    'value' => '+963 11 234 5678',     'group' => 'contact', 'is_public' => true,  'description' => 'Public contact phone'],
            ['key' => 'social_facebook',  'value' => 'https://facebook.com/syriahomes', 'group' => 'social', 'is_public' => true, 'description' => 'Facebook URL'],
            ['key' => 'social_instagram', 'value' => 'https://instagram.com/syriahomes', 'group' => 'social', 'is_public' => true, 'description' => 'Instagram URL'],
            ['key' => 'maintenance_mode', 'value' => 'false',                'group' => 'system', 'is_public' => false, 'description' => 'Maintenance mode flag'],
            ['key' => 'currency_default', 'value' => 'USD',                  'group' => 'system', 'is_public' => true,  'description' => 'Default currency'],
            ['key' => 'commission_pct',   'value' => '2.5',                  'group' => 'system', 'is_public' => false, 'description' => 'Platform commission percentage'],
        ];

        foreach ($settings as $s) {
            Setting::create($s);
        }
    }
}
