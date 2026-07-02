<?php

namespace Database\Seeders;

use App\Models\Integration;
use Illuminate\Database\Seeder;

/**
 * Seeds the Google Maps integration, docs, and templates.
 */
class GoogleMapsSeeder extends Seeder
{
    use SeedsIntegrationContent;

    public function run(): void
    {
        $integration = Integration::firstOrCreate(['key' => 'google_maps'], [
            'key' => 'google_maps',
            'name' => 'Google Maps',
            'name_ar' => 'خرائط قوقل',
            'description' => 'Google Maps integration for location services and agent finding.',
            'description_ar' => 'تكامل خرائط قوقل لخدمات الموقع وإيجاد الوكلاء.',
            'icon' => 'map',
            'category' => 'location',
            'is_active' => false,
            'is_visible' => true,
            'config' => [
                'agent_finder_enabled' => true,
                'geocoding_enabled' => true,
                'places_enabled' => true,
                'default_zoom' => 12,
                'max_results' => 50,
            ],
            'credentials' => [
                'api_key' => '',
                'places_key' => '',
                'geocoding_key' => '',
            ],
            'settings' => [
                'environment' => 'production',
                'timeout' => 30,
            ],
        ]);

    }
}
