<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('testimonials')->insert([
            [
                'name'        => 'محمد الخالد',
                'role_ar'     => 'مشتري عقار',
                'role_en'     => 'Property Buyer',
                'avatar_path' => 'https://i.pravatar.cc/100?img=11',
                'rating'      => 5,
                'quote_ar'    => 'صراحة من أفضل المواقع العقارية في سورية. ساعدوني أجد بيت أحلامي بسرعة وبأقل جهد.',
                'quote_en'    => 'Honestly one of the best real estate sites in Syria. Helped me find my dream home quickly.',
                'is_featured' => true,
                'sort'        => 1,
            ],
            [
                'name'        => 'سارة المصري',
                'role_ar'     => 'مستثمرة عقارية',
                'role_en'     => 'Real Estate Investor',
                'avatar_path' => 'https://i.pravatar.cc/100?img=16',
                'rating'      => 5,
                'quote_ar'    => 'منصة ممتازة للمستثمرين. معلومات دقيقة، صور واضحة، وتواصل مباشر مع المكاتب العقارية.',
                'quote_en'    => 'Excellent platform for investors. Accurate info, clear photos, direct contact with agencies.',
                'is_featured' => true,
                'sort'        => 2,
            ],
            [
                'name'        => 'أحمد شريف',
                'role_ar'     => 'وكيل عقارات',
                'role_en'     => 'Real Estate Agent',
                'avatar_path' => 'https://i.pravatar.cc/100?img=12',
                'rating'      => 4,
                'quote_ar'    => 'ساعدني الموقع في عرض عقاراتي بشكل احترافي ووصلت لعدد أكبر من الزبائن.',
                'quote_en'    => 'The site helped me showcase properties professionally and reach more clients.',
                'is_featured' => true,
                'sort'        => 3,
            ],
        ]);
    }
}
