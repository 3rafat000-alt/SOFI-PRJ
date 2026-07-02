<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class AmenitySeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $amenities = [
            ['name_ar' => 'مسبح',          'name_en' => 'Pool',     'icon' => 'pool',     'group' => 'recreation'],
            ['name_ar' => 'مصعد',           'name_en' => 'Elevator', 'icon' => 'elevator', 'group' => 'building'],
            ['name_ar' => 'حديقة',          'name_en' => 'Garden',   'icon' => 'garden',   'group' => 'recreation'],
            ['name_ar' => 'حراسة',          'name_en' => 'Security', 'icon' => 'security', 'group' => 'safety'],
            ['name_ar' => 'تكييف',          'name_en' => 'AC',       'icon' => 'ac-unit',  'group' => 'comfort'],
            ['name_ar' => 'طاقة شمسية',    'name_en' => 'Solar',    'icon' => 'solar',    'group' => 'utilities'],
            ['name_ar' => 'موقف سيارات',   'name_en' => 'Parking',  'icon' => 'parking',  'group' => 'building'],
            ['name_ar' => 'مولد كهرباء',   'name_en' => 'Generator','icon' => 'generator','group' => 'utilities'],
        ];

        foreach ($amenities as &$row) {
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
        }

        DB::table('amenities')->insert($amenities);
    }
}
