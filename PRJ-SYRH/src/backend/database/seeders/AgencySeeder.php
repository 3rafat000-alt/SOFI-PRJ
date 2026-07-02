<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class AgencySeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        DB::table('agencies')->insert([
            [
                'name'        => 'بيوت الشام العقارية',
                'slug'        => 'byout-al-sham',
                'logo_path'   => '/storage/agencies/byout-al-sham.png',
                'phone'       => '+963-11-111-2001',
                'verified_at' => $now,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'name'        => 'دار الياسمين للعقارات',
                'slug'        => 'dar-al-yasmin',
                'logo_path'   => '/storage/agencies/dar-al-yasmin.png',
                'phone'       => '+963-21-222-3002',
                'verified_at' => $now,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'name'        => 'نخبة العقارات السورية',
                'slug'        => 'nukhba-real-estate',
                'logo_path'   => '/storage/agencies/nukhba.png',
                'phone'       => '+963-41-333-4003',
                'verified_at' => $now,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ]);
    }
}
