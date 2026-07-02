<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class PropertyTypeSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $types = [
            ['name_ar' => 'شقق',    'name_en' => 'Apartments', 'slug' => 'apartments', 'icon' => 'building-apartment', 'sort' => 1],
            ['name_ar' => 'فلل',    'name_en' => 'Villas',     'slug' => 'villas',      'icon' => 'building-villa',     'sort' => 2],
            ['name_ar' => 'بيوت',   'name_en' => 'Houses',     'slug' => 'houses',      'icon' => 'house',              'sort' => 3],
            ['name_ar' => 'تجاري',  'name_en' => 'Commercial', 'slug' => 'commercial',  'icon' => 'store',              'sort' => 4],
            ['name_ar' => 'أراضي',  'name_en' => 'Land',       'slug' => 'land',        'icon' => 'land-plot',          'sort' => 5],
        ];

        foreach ($types as &$row) {
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
        }

        DB::table('property_types')->insert($types);
    }
}
