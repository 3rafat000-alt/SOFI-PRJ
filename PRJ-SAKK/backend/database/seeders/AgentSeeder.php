<?php

namespace Database\Seeders;

use App\Models\Agent;
use Illuminate\Database\Seeder;

class AgentSeeder extends Seeder
{
    public function run(): void
    {
        $agents = [
            // ───────── Damascus ─────────
            [
                'name' => 'صرافة الشام المركزية', 'owner_name' => 'أبو خالد', 'agent_code' => 'AG-1001',
                'phone' => '+963 11 221 3344', 'address' => 'شارع الحمراء، وسط المدينة', 'city' => 'دمشق',
                'governorate' => 'دمشق', 'latitude' => 33.5138, 'longitude' => 36.2765,
                'services' => ['cash_in', 'cash_out'], 'working_hours' => '9:00 ص - 9:00 م',
                'commission_rate' => 1.0, 'min_amount' => 10, 'max_amount' => 5000,
                'rating' => 4.9, 'reviews_count' => 312, 'is_featured' => true,
            ],
            [
                'name' => 'مكتب الميدان للحوالات', 'owner_name' => 'سمير حلاق', 'agent_code' => 'AG-1002',
                'phone' => '+963 11 555 7788', 'address' => 'حي الميدان، قرب الجامع', 'city' => 'دمشق',
                'governorate' => 'دمشق', 'latitude' => 33.4905, 'longitude' => 36.2980,
                'services' => ['cash_out'], 'working_hours' => '10:00 ص - 8:00 م',
                'commission_rate' => 1.5, 'min_amount' => 10, 'max_amount' => 3000,
                'rating' => 4.6, 'reviews_count' => 128, 'is_featured' => false,
            ],
            [
                'name' => 'صرافة المزة', 'owner_name' => 'وسيم العلي', 'agent_code' => 'AG-1003',
                'phone' => '+963 11 611 2299', 'address' => 'المزة، أوتوستراد المزة', 'city' => 'دمشق',
                'governorate' => 'دمشق', 'latitude' => 33.5022, 'longitude' => 36.2380,
                'services' => ['cash_in', 'cash_out'], 'working_hours' => '24 ساعة',
                'commission_rate' => 1.2, 'min_amount' => 20, 'max_amount' => 8000,
                'rating' => 4.8, 'reviews_count' => 204, 'is_featured' => true,
            ],

            // ───────── Aleppo ─────────
            [
                'name' => 'صرافة حلب الدولية', 'owner_name' => 'أحمد قباني', 'agent_code' => 'AG-2001',
                'phone' => '+963 21 212 3344', 'address' => 'العزيزية، شارع بارون', 'city' => 'حلب',
                'governorate' => 'حلب', 'latitude' => 36.2021, 'longitude' => 37.1343,
                'services' => ['cash_in', 'cash_out'], 'working_hours' => '9:00 ص - 10:00 م',
                'commission_rate' => 1.0, 'min_amount' => 10, 'max_amount' => 6000,
                'rating' => 4.7, 'reviews_count' => 176, 'is_featured' => true,
            ],
            [
                'name' => 'مكتب السبيل للصرافة', 'owner_name' => 'محمود حداد', 'agent_code' => 'AG-2002',
                'phone' => '+963 21 333 5566', 'address' => 'حي السبيل، الجادة الرئيسية', 'city' => 'حلب',
                'governorate' => 'حلب', 'latitude' => 36.1890, 'longitude' => 37.1620,
                'services' => ['cash_out'], 'working_hours' => '10:00 ص - 8:00 م',
                'commission_rate' => 1.4, 'min_amount' => 10, 'max_amount' => 2500,
                'rating' => 4.4, 'reviews_count' => 89, 'is_featured' => false,
            ],

            // ───────── Homs ─────────
            [
                'name' => 'صرافة الوليد', 'owner_name' => 'خالد ديب', 'agent_code' => 'AG-3001',
                'phone' => '+963 31 247 8899', 'address' => 'شارع الحضارة، وسط حمص', 'city' => 'حمص',
                'governorate' => 'حمص', 'latitude' => 34.7324, 'longitude' => 36.7137,
                'services' => ['cash_in', 'cash_out'], 'working_hours' => '9:00 ص - 9:00 م',
                'commission_rate' => 1.1, 'min_amount' => 10, 'max_amount' => 4000,
                'rating' => 4.7, 'reviews_count' => 142, 'is_featured' => true,
            ],
            [
                'name' => 'مكتب الإنشاءات المالية', 'owner_name' => 'رامي سعد', 'agent_code' => 'AG-3002',
                'phone' => '+963 31 466 1122', 'address' => 'حي الإنشاءات', 'city' => 'حمص',
                'governorate' => 'حمص', 'latitude' => 34.7440, 'longitude' => 36.7050,
                'services' => ['cash_in'], 'working_hours' => '10:00 ص - 7:00 م',
                'commission_rate' => 1.0, 'min_amount' => 10, 'max_amount' => 3000,
                'rating' => 4.3, 'reviews_count' => 64, 'is_featured' => false,
            ],

            // ───────── Latakia ─────────
            [
                'name' => 'صرافة الكورنيش', 'owner_name' => 'ماهر سليمان', 'agent_code' => 'AG-4001',
                'phone' => '+963 41 478 2233', 'address' => 'الكورنيش الجنوبي', 'city' => 'اللاذقية',
                'governorate' => 'اللاذقية', 'latitude' => 35.5196, 'longitude' => 35.7915,
                'services' => ['cash_in', 'cash_out'], 'working_hours' => '9:00 ص - 11:00 م',
                'commission_rate' => 1.0, 'min_amount' => 10, 'max_amount' => 5000,
                'rating' => 4.8, 'reviews_count' => 198, 'is_featured' => true,
            ],
            [
                'name' => 'مكتب الأمان للحوالات', 'owner_name' => 'نادر عيسى', 'agent_code' => 'AG-4002',
                'phone' => '+963 41 233 4455', 'address' => 'شارع 8 آذار', 'city' => 'اللاذقية',
                'governorate' => 'اللاذقية', 'latitude' => 35.5280, 'longitude' => 35.7990,
                'services' => ['cash_out'], 'working_hours' => '10:00 ص - 8:00 م',
                'commission_rate' => 1.3, 'min_amount' => 10, 'max_amount' => 2000,
                'rating' => 4.5, 'reviews_count' => 73, 'is_featured' => false,
            ],

            // ───────── Hama ─────────
            [
                'name' => 'صرافة العاصي', 'owner_name' => 'فادي خوري', 'agent_code' => 'AG-5001',
                'phone' => '+963 33 221 6677', 'address' => 'ساحة العاصي', 'city' => 'حماة',
                'governorate' => 'حماة', 'latitude' => 35.1318, 'longitude' => 36.7578,
                'services' => ['cash_in', 'cash_out'], 'working_hours' => '9:00 ص - 8:00 م',
                'commission_rate' => 1.2, 'min_amount' => 10, 'max_amount' => 3500,
                'rating' => 4.6, 'reviews_count' => 101, 'is_featured' => false,
            ],

            // ───────── Tartus ─────────
            [
                'name' => 'صرافة الفنار', 'owner_name' => 'علي ونوس', 'agent_code' => 'AG-6001',
                'phone' => '+963 43 318 9900', 'address' => 'شارع الثورة', 'city' => 'طرطوس',
                'governorate' => 'طرطوس', 'latitude' => 34.8886, 'longitude' => 35.8866,
                'services' => ['cash_in', 'cash_out'], 'working_hours' => '9:00 ص - 9:00 م',
                'commission_rate' => 1.1, 'min_amount' => 10, 'max_amount' => 4000,
                'rating' => 4.7, 'reviews_count' => 115, 'is_featured' => true,
            ],

            // ───────── Daraa ─────────
            [
                'name' => 'مكتب حوران المالي', 'owner_name' => 'بشار الحريري', 'agent_code' => 'AG-7001',
                'phone' => '+963 15 247 3311', 'address' => 'وسط مدينة درعا', 'city' => 'درعا',
                'governorate' => 'درعا', 'latitude' => 32.6189, 'longitude' => 36.1021,
                'services' => ['cash_out'], 'working_hours' => '10:00 ص - 7:00 م',
                'commission_rate' => 1.5, 'min_amount' => 10, 'max_amount' => 2000,
                'rating' => 4.2, 'reviews_count' => 47, 'is_featured' => false,
            ],
        ];

        foreach ($agents as $data) {
            Agent::updateOrCreate(['agent_code' => $data['agent_code']], $data);
        }
    }
}
