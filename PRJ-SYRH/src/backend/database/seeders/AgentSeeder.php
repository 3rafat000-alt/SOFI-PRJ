<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class AgentSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $agencyIds = DB::table('agencies')->pluck('id', 'slug');

        DB::table('agents')->insert([
            [
                'user_id'      => null,
                'agency_id'    => $agencyIds['byout-al-sham'],
                'display_name' => 'أحمد الخطيب',
                'phone'        => '+963-912-001-001',
                'whatsapp'     => '+963-912-001-001',
                'photo_path'   => '/storage/agents/ahmed-khatib.jpg',
                'license_no'   => 'SY-RE-10042',
                'rating'       => 4.8,
                'reviews_count'=> 124,
                'bio_ar'       => 'خبرة أكثر من ١٥ عاماً في السوق العقاري الدمشقي، متخصص في الشقق الفاخرة وعقارات المزة والمالكي.',
                'bio_en'       => 'Over 15 years in the Damascus real estate market, specialising in luxury apartments in Mazzeh and Malki.',
                'verified_at'  => $now,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'user_id'      => null,
                'agency_id'    => $agencyIds['byout-al-sham'],
                'display_name' => 'ريم العلي',
                'phone'        => '+963-933-002-002',
                'whatsapp'     => '+963-933-002-002',
                'photo_path'   => '/storage/agents/reem-ali.jpg',
                'license_no'   => 'SY-RE-10087',
                'rating'       => 4.9,
                'reviews_count'=> 98,
                'bio_ar'       => 'متخصصة في عقارات السكن الفاخر وريف دمشق، وتقديم تجربة استثمارية مميزة للعملاء.',
                'bio_en'       => 'Specialist in luxury residential and Rural Damascus properties with a focus on investor experience.',
                'verified_at'  => $now,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'user_id'      => null,
                'agency_id'    => $agencyIds['dar-al-yasmin'],
                'display_name' => 'ماهر قاسم',
                'phone'        => '+963-944-003-003',
                'whatsapp'     => '+963-944-003-003',
                'photo_path'   => '/storage/agents/maher-qasim.jpg',
                'license_no'   => 'SY-RE-20031',
                'rating'       => 4.6,
                'reviews_count'=> 77,
                'bio_ar'       => 'خبير العقارات التجارية والسكنية في حلب، مع شبكة واسعة من المطورين المحليين.',
                'bio_en'       => 'Commercial and residential expert in Aleppo with a wide network of local developers.',
                'verified_at'  => $now,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'user_id'      => null,
                'agency_id'    => $agencyIds['dar-al-yasmin'],
                'display_name' => 'سارة منصور',
                'phone'        => '+963-955-004-004',
                'whatsapp'     => '+963-955-004-004',
                'photo_path'   => '/storage/agents/sara-mansour.jpg',
                'license_no'   => 'SY-RE-20056',
                'rating'       => 4.7,
                'reviews_count'=> 61,
                'bio_ar'       => 'مستشارة عقارية متخصصة في الساحل السوري — اللاذقية وطرطوس — للمغتربين والمستثمرين.',
                'bio_en'       => 'Property consultant specialised in the Syrian coast — Latakia and Tartus — for diaspora and investors.',
                'verified_at'  => $now,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'user_id'      => null,
                'agency_id'    => $agencyIds['nukhba-real-estate'],
                'display_name' => 'خالد درويش',
                'phone'        => '+963-966-005-005',
                'whatsapp'     => '+963-966-005-005',
                'photo_path'   => '/storage/agents/khaled-darwish.jpg',
                'license_no'   => 'SY-RE-30012',
                'rating'       => 4.5,
                'reviews_count'=> 53,
                'bio_ar'       => 'متخصص في الأراضي والمشاريع التطويرية في حمص والمنطقة الوسطى.',
                'bio_en'       => 'Land and development projects specialist in Homs and the central region.',
                'verified_at'  => $now,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
        ]);
    }
}
