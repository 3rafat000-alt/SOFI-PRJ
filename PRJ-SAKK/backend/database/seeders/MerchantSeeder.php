<?php

namespace Database\Seeders;

use App\Models\Merchant;
use Illuminate\Database\Seeder;

class MerchantSeeder extends Seeder
{
    public function run(): void
    {
        $merchants = [
            [
                'store_name' => 'متجر الموضة الشامي', 'owner_name' => 'محمود الخطيب',
                'type' => 'physical', 'email' => 'info@shami-fashion.com',
                'phone' => '+963 11 223 4455', 'description' => 'أزياء ومستلزمات نسائية ورجالية فاخرة',
                'city' => 'دمشق', 'governorate' => 'دمشق', 'address' => 'شارع الحمراء، بناء رقم 5',
                'latitude' => 33.5120, 'longitude' => 36.2780,
                'commission_rate' => 2.5, 'has_api_access' => false,
                'balance' => 15420.50, 'total_earned' => 45200.00,
                'is_verified' => true, 'verified_at' => now()->subDays(30),
            ],
            [
                'store_name' => 'سوق كوم السوري', 'owner_name' => 'أحمد زين',
                'type' => 'ecommerce', 'email' => 'admin@sook-sy.com',
                'phone' => '+963 93 444 7788', 'description' => 'منصة تسوق إلكتروني متكاملة',
                'city' => 'دمشق', 'governorate' => 'دمشق', 'address' => 'المزة، شارع النخيل',
                'latitude' => 33.5050, 'longitude' => 36.2400,
                'commission_rate' => 1.8, 'has_api_access' => true,
                'environment' => 'production', 'webhook_url' => 'https://sook-sy.com/webhook/sakk',
                'balance' => 88250.00, 'total_earned' => 320500.00,
                'is_verified' => true, 'verified_at' => now()->subDays(60),
            ],
            [
                'store_name' => 'مخبز وحلويات كل شي', 'owner_name' => 'خالد دبس',
                'type' => 'physical', 'email' => 'khalid@kullshei.com',
                'phone' => '+963 21 555 6633', 'description' => 'مخبز وحلويات شرقية وغربية',
                'city' => 'حلب', 'governorate' => 'حلب', 'address' => 'العزيزية، قرب ساحة بارون',
                'latitude' => 36.2040, 'longitude' => 37.1360,
                'commission_rate' => 3.0, 'has_api_access' => false,
                'balance' => 3750.00, 'total_earned' => 18200.00,
                'is_verified' => true, 'verified_at' => now()->subDays(15),
            ],
            [
                'store_name' => 'متجر إليكترونيكس', 'owner_name' => 'نور عيسى',
                'type' => 'both', 'email' => 'nour@electronix.sy',
                'phone' => '+963 31 888 9900', 'description' => 'أجهزة إلكترونية وكهربائية — متجر فعلي + توصيل للمنازل',
                'city' => 'حمص', 'governorate' => 'حمص', 'address' => 'شارع الحضارة',
                'latitude' => 34.7310, 'longitude' => 36.7150,
                'commission_rate' => 2.0, 'has_api_access' => true,
                'environment' => 'sandbox',
                'balance' => 12300.00, 'total_earned' => 67500.00,
                'is_verified' => true, 'verified_at' => now()->subDays(45),
            ],
            [
                'store_name' => 'صيدلية البركة', 'owner_name' => 'د. مها السعد',
                'type' => 'physical', 'email' => 'info@albaraka-pharma.com',
                'phone' => '+963 41 335 7788', 'description' => 'صيدلية — أدوية ومستحضرات طبية وتجميلية',
                'city' => 'اللاذقية', 'governorate' => 'اللاذقية', 'address' => 'الكورنيش الجنوبي',
                'latitude' => 35.5210, 'longitude' => 35.7930,
                'commission_rate' => 2.2, 'has_api_access' => false,
                'balance' => 28900.00, 'total_earned' => 95400.00,
                'is_verified' => true, 'verified_at' => now()->subDays(20),
            ],
            [
                'store_name' => 'بساط الريح — توصيل', 'owner_name' => 'رامي شعبان',
                'type' => 'ecommerce', 'email' => 'ramy@bisatrrih.com',
                'phone' => '+963 93 777 1122', 'description' => 'تطبيق توصيل طلبات — مطاعم، بقالة، صيدليات',
                'city' => 'دمشق', 'governorate' => 'دمشق', 'address' => 'الميدان، شارع 30',
                'latitude' => 33.4920, 'longitude' => 36.2960,
                'commission_rate' => 3.5, 'has_api_access' => true,
                'environment' => 'production', 'webhook_url' => 'https://bisatrrih.com/hooks/sakk',
                'balance' => 124500.00, 'total_earned' => 487000.00,
                'is_verified' => true, 'verified_at' => now()->subDays(90),
            ],
            [
                'store_name' => 'معرض السجاد الحموي', 'owner_name' => 'عبد الرحمن خضور',
                'type' => 'physical', 'email' => 'info@hammou-carpets.com',
                'phone' => '+963 33 223 4455', 'description' => 'سجاد شرقي وأوروبي — صناعة يدوية وتجارية',
                'city' => 'حماة', 'governorate' => 'حماة', 'address' => 'ساحة العاصي',
                'latitude' => 35.1330, 'longitude' => 36.7550,
                'commission_rate' => 2.8, 'has_api_access' => false,
                'balance' => 6800.00, 'total_earned' => 31500.00,
                'is_active' => false, 'is_verified' => true, 'verified_at' => now()->subDays(40),
                'notes' => 'مغلق مؤقتاً للتجديد',
            ],
            [
                'store_name' => 'متجر الأصيل للمواد الغذائية', 'owner_name' => 'سمير صالح',
                'type' => 'both', 'email' => 'samir@alaseel.sy',
                'phone' => '+963 43 114 7788', 'description' => 'مواد غذائية بالجملة والتجزئة — متجر + طلبات أونلاين',
                'city' => 'طرطوس', 'governorate' => 'طرطوس', 'address' => 'شارع الثورة',
                'latitude' => 34.8860, 'longitude' => 35.8880,
                'commission_rate' => 1.5, 'has_api_access' => true,
                'environment' => 'sandbox',
                'balance' => 4450.00, 'total_earned' => 18900.00,
                'is_verified' => false,
            ],
            [
                'store_name' => 'سوبر ماركت الفيحاء', 'owner_name' => 'غسان مراد',
                'type' => 'physical', 'email' => 'ghassan@fayhaa.sy',
                'phone' => '+963 15 224 6677', 'description' => 'سوبر ماركت متكامل — مواد تموينية وخضار وفواكه',
                'city' => 'درعا', 'governorate' => 'درعا', 'address' => 'وسط مدينة درعا — قرب السوق',
                'latitude' => 32.6200, 'longitude' => 36.1000,
                'commission_rate' => 2.0, 'has_api_access' => false,
                'balance' => 12250.00, 'total_earned' => 44300.00,
                'is_verified' => true, 'verified_at' => now()->subDays(10),
            ],
            [
                'store_name' => 'متجر التقنية الحديثة', 'owner_name' => 'ليث عقاد',
                'type' => 'ecommerce', 'email' => 'layth@tech-sy.com',
                'phone' => '+963 93 555 2244', 'description' => 'متجر إلكتروني متخصص في الإلكترونيات والهواتف والملحقات',
                'city' => 'دمشق', 'governorate' => 'دمشق', 'address' => 'العرموش، شارع 8',
                'latitude' => 33.4780, 'longitude' => 36.3050,
                'commission_rate' => 2.2, 'has_api_access' => true,
                'environment' => 'production', 'webhook_url' => 'https://tech-sy.com/api/sakk-webhook',
                'balance' => 56700.00, 'total_earned' => 215800.00,
                'is_verified' => true, 'verified_at' => now()->subDays(75),
            ],
        ];

        foreach ($merchants as $data) {
            Merchant::create($data);
        }
    }
}
