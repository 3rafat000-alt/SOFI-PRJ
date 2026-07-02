<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class GovernorateSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $governorates = [
            [
                'name_ar' => 'دمشق',
                'name_en' => 'Damascus',
                'slug'    => 'damascus',
                'lat'     => 33.5138073,
                'lng'     => 36.2765279,
            ],
            [
                'name_ar' => 'ريف دمشق',
                'name_en' => 'Rural Damascus',
                'slug'    => 'rural-damascus',
                'lat'     => 33.5012000,
                'lng'     => 36.5000000,
            ],
            [
                'name_ar' => 'حلب',
                'name_en' => 'Aleppo',
                'slug'    => 'aleppo',
                'lat'     => 36.2021047,
                'lng'     => 37.1342603,
            ],
            [
                'name_ar' => 'حمص',
                'name_en' => 'Homs',
                'slug'    => 'homs',
                'lat'     => 34.7324000,
                'lng'     => 36.7136000,
            ],
            [
                'name_ar' => 'حماة',
                'name_en' => 'Hama',
                'slug'    => 'hama',
                'lat'     => 35.1318000,
                'lng'     => 36.7580000,
            ],
            [
                'name_ar' => 'اللاذقية',
                'name_en' => 'Latakia',
                'slug'    => 'latakia',
                'lat'     => 35.5317000,
                'lng'     => 35.7913000,
            ],
            [
                'name_ar' => 'طرطوس',
                'name_en' => 'Tartus',
                'slug'    => 'tartus',
                'lat'     => 34.8887000,
                'lng'     => 35.8865000,
            ],
            [
                'name_ar' => 'السويداء',
                'name_en' => 'As-Suwayda',
                'slug'    => 'as-suwayda',
                'lat'     => 32.7082000,
                'lng'     => 36.5669000,
            ],
            [
                'name_ar' => 'درعا',
                'name_en' => 'Daraa',
                'slug'    => 'daraa',
                'lat'     => 32.6189000,
                'lng'     => 36.1021000,
            ],
            [
                'name_ar' => 'القنيطرة',
                'name_en' => 'Quneitra',
                'slug'    => 'quneitra',
                'lat'     => 33.1262000,
                'lng'     => 35.8243000,
            ],
            [
                'name_ar' => 'دير الزور',
                'name_en' => 'Deir ez-Zor',
                'slug'    => 'deir-ez-zor',
                'lat'     => 35.3359000,
                'lng'     => 40.1408000,
            ],
            [
                'name_ar' => 'الحسكة',
                'name_en' => 'Al-Hasakah',
                'slug'    => 'al-hasakah',
                'lat'     => 36.5024000,
                'lng'     => 40.7450000,
            ],
            [
                'name_ar' => 'الرقة',
                'name_en' => 'Raqqa',
                'slug'    => 'raqqa',
                'lat'     => 35.9500000,
                'lng'     => 38.9981000,
            ],
            [
                'name_ar' => 'إدلب',
                'name_en' => 'Idlib',
                'slug'    => 'idlib',
                'lat'     => 35.9314000,
                'lng'     => 36.6333000,
            ],
        ];

        foreach ($governorates as &$row) {
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
        }

        DB::table('governorates')->upsert($governorates, 'slug', ['updated_at']);
    }
}
