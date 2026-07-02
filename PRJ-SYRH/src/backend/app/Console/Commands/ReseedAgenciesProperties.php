<?php

namespace App\Console\Commands;

use App\Models\Agent;
use App\Models\Agency;
use App\Models\Amenity;
use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReseedAgenciesProperties extends Command
{
    protected $signature = 'db:reseed-agencies {--force : Skip confirmation prompt}';
    protected $description = 'Delete all and reseed 3 agencies + 20 properties with full Arabic content';

    public function handle(): int
    {
        if (!$this->option('force') && !$this->confirm('This will DELETE all existing data and reseed. Continue?')) {
            return Command::FAILURE;
        }

        $this->line('Clearing existing data...');
        DB::statement('SET session_replication_role = replica');

        DB::table('chat_messages')->truncate();
        DB::table('conversations')->truncate();
        DB::table('deals')->truncate();
        DB::table('payments')->truncate();
        DB::table('property_amenity')->truncate();
        DB::table('property_images')->truncate();
        DB::table('property_reviews')->truncate();
        DB::table('property_views')->truncate();
        DB::table('favorites')->truncate();
        DB::table('inquiries')->truncate();
        DB::table('saved_searches')->truncate();
        DB::table('contact_messages')->truncate();
        DB::table('quick_replies')->truncate();
        DB::table('agency_subscriptions')->truncate();
        Property::query()->forceDelete();
        Agent::query()->truncate();
        Agency::query()->truncate();

        DB::statement('SET session_replication_role = origin');

        $this->line('Seeding agencies...');

        $now = now();

        $agency1 = Agency::create([
            'name'              => 'شركة بيطار العقارية',
            'slug'              => Str::slug('شركة بيطار العقارية'),
            'email'             => 'info@bitar.sy',
            'phone'             => '011-555-1000',
            'whatsapp'          => '+963933000100',
            'description_ar'    => 'شركة عقارية رائدة في سورية متخصصة في بيع وشراء وإدارة الأملاك العقارية. نتمتع بخبرة تزيد عن 15 عاماً في السوق العقاري السوري.',
            'description_en'    => '',
            'logo_path'         => '/storage/agencies/sharkat-bitar.svg',
            'cover_path'        => null,
            'address'           => 'دمشق - المزة',
            'license_no'        => 'SYR-BIT-001',
            'commission_rate'   => 2.5,
            'status'            => 'active',
            'verified_at'       => $now,
            'sakk_merchant_id'  => 'MCH-BPBLEZXF',
            'sakk_verified'     => true,
            'sakk_verified_at'  => $now,
            'governorate_id'    => 1,
            'area_id'           => 2,
            'lat'               => 33.513,
            'lng'               => 36.285,
        ]);

        $agency2 = Agency::create([
            'name'              => 'سورية هومز العقارية',
            'slug'              => Str::slug('سورية هومز العقارية'),
            'email'             => 'info@syriahomes.sy',
            'phone'             => '011-555-2000',
            'whatsapp'          => null,
            'description_ar'    => 'منصة عقارية متكاملة تهدف لربط المشترين والمستأجرين بأفضل العقارات في جميع أنحاء سورية. نقدم حلولاً ذكية وشفافة لجميع احتياجاتك العقارية.',
            'description_en'    => '',
            'logo_path'         => null,
            'cover_path'        => null,
            'address'           => 'دمشق - أبو رمانة',
            'license_no'        => 'SYR-SH-001',
            'commission_rate'   => 2.0,
            'status'            => 'active',
            'verified_at'       => $now,
            'governorate_id'    => 1,
            'area_id'           => 3,
            'lat'               => 33.520,
            'lng'               => 36.285,
        ]);

        $agency3 = Agency::create([
            'name'              => 'منارة الشام للعقارات',
            'slug'              => Str::slug('منارة الشام للعقارات'),
            'email'             => 'info@manaratal-sham.sy',
            'phone'             => '011-555-3000',
            'whatsapp'          => null,
            'description_ar'    => 'وكالة عقارية متميزة تقدم أفضل الخدمات العقارية في دمشق وريفها. متخصصون في العقارات الفاخرة والتجارية.',
            'description_en'    => '',
            'logo_path'         => null,
            'cover_path'        => null,
            'address'           => 'دمشق - المهاجرين',
            'license_no'        => 'SYR-MS-001',
            'commission_rate'   => 3.0,
            'status'            => 'active',
            'verified_at'       => $now,
            'governorate_id'    => 1,
            'area_id'           => 5,
            'lat'               => 33.522,
            'lng'               => 36.290,
        ]);

        $this->line('Seeding agents...');

        $agentsData = [
            ['agency_id' => $agency1->id, 'display_name' => 'أحمد صوفي',       'email' => 'ahmad@bitar.sy',             'phone' => '0933-100-101', 'bio_ar' => 'مدير مبيعات بخبرة ١٠ سنوات في السوق العقاري الدمشقي.'],
            ['agency_id' => $agency1->id, 'display_name' => 'ليلى الخطيب',     'email' => 'layla@bitar.sy',             'phone' => '0933-100-102', 'bio_ar' => 'وسيط عقاري محترف متخصص في العقارات السكنية والتجارية.'],
            ['agency_id' => $agency2->id, 'display_name' => 'محمد الحلبي',     'email' => 'mohammad@syriahomes.sy',     'phone' => '0933-200-201', 'bio_ar' => 'مستشار عقاري يقدم استشارات مهنية لشراء وبيع العقارات.'],
            ['agency_id' => $agency2->id, 'display_name' => 'نور السيد',       'email' => 'nour@syriahomes.sy',         'phone' => '0933-200-202', 'bio_ar' => 'مديرة تسويق عقاري بخبرة واسعة في الترويج للعقارات الفاخرة.'],
            ['agency_id' => $agency3->id, 'display_name' => 'خالد الأيوبي',    'email' => 'khaled@manaratal-sham.sy',   'phone' => '0933-300-301', 'bio_ar' => 'وسيط أول متخصص في العقارات الفاخرة والتجارية في دمشق.'],
            ['agency_id' => $agency3->id, 'display_name' => 'سارة المصري',     'email' => 'sara@manaratal-sham.sy',     'phone' => '0933-300-302', 'bio_ar' => 'مسؤولة حجوزات وعقود بخبرة تزيد عن ٨ سنوات في القطاع العقاري.'],
        ];

        $agents = [];
        foreach ($agentsData as $data) {
            $agents[] = Agent::create([
                'agency_id'    => $data['agency_id'],
                'display_name' => $data['display_name'],
                'email'        => $data['email'],
                'phone'        => $data['phone'],
                'status'       => 'active',
                'bio_ar'       => $data['bio_ar'],
                'verified_at'  => $now,
            ]);
        }

        $this->line('Seeding properties...');

        $amenitiesMap = [
            'pool'     => 1,
            'elevator' => 2,
            'garden'   => 3,
            'security' => 4,
            'ac'       => 5,
            'solar'    => 6,
            'parking'  => 7,
            'generator'=> 8,
        ];

        $propertySeed = function (array $p, int $agencyId, int $agentIdx) use ($amenitiesMap, $now) {
            static $refCounter = 0;
            $refCounter++;

            $titleAr = $p['title_ar'];

            $prop = Property::create([
                'ref_code'         => 'SYR-' . str_pad((string) $refCounter, 3, '0', STR_PAD_LEFT),
                'property_type_id' => $p['type_id'],
                'agent_id'         => $p['agent_id'] ?? null,
                'agency_id'        => $agencyId,
                'governorate_id'   => $p['gov_id'],
                'area_id'          => $p['area_id'],
                'purpose'          => $p['purpose'],
                'status'           => 'available',
                'title_ar'         => $titleAr,
                'title_en'         => '',
                'slug'             => Str::slug($titleAr),
                'description_ar'   => $p['desc_ar'],
                'description_en'   => '',
                'price'            => $p['price'],
                'currency'         => 'USD',
                'rent_period'      => $p['purpose'] === 'rent' ? 'month' : null,
                'area_sqm'         => $p['area_sqm'],
                'bedrooms'         => $p['bedrooms'],
                'bathrooms'        => $p['bathrooms'],
                'parking'          => $p['parking'] ?? 0,
                'floor'            => $p['floor'] ?? null,
                'year_built'       => $p['year_built'],
                'furnished'        => $p['furnished'] ?? false,
                'address_ar'       => $p['address_ar'] ?? $titleAr,
                'address_en'       => '',
                'lat'              => $p['lat'],
                'lng'              => $p['lng'],
                'is_featured'      => $p['is_featured'] ?? false,
                'is_hot_deal'      => $p['is_hot_deal'] ?? false,
                'views_count'      => random_int(50, 500),
                'published_at'     => $now,
            ]);

            if (!empty($p['amenities'])) {
                $prop->amenities()->attach($p['amenities']);
            }

            $imageCount = $p['image_count'] ?? 4;
            for ($i = 1; $i <= $imageCount; $i++) {
                $img = new PropertyImage();
                $img->property_id = $prop->id;
                $img->path = "https://picsum.photos/seed/syr-prop-{$prop->id}-{$i}/800/600";
                $img->alt_ar = $titleAr . " - صورة {$i}";
                $img->alt_en = '';
                $img->sort = $i;
                $img->is_cover = $i === 1;
                $img->save();
            }

            return $prop;
        };

        [$a1, $a2, $a3] = [$agency1, $agency2, $agency3];
        [$ag1, $ag2, $ag3, $ag4, $ag5, $ag6] = $agents;

        // Agency 1 — 8 premium
        $propertySeed([
            'title_ar'    => 'فيلا فاخرة في المزة - فيلات غربية بمساحة 600 م²',
            'type_id'     => 2,
            'gov_id'      => 1,
            'area_id'     => 2,
            'purpose'     => 'sale',
            'price'       => 850000,
            'area_sqm'    => 600,
            'bedrooms'    => 5,
            'bathrooms'   => 4,
            'parking'     => 2,
            'floor'       => null,
            'year_built'  => 2020,
            'furnished'   => true,
            'lat'         => 33.5065,
            'lng'         => 36.2780,
            'is_featured' => true,
            'desc_ar'     => 'فيلا فاخرة في أجمل مناطق المزة - فيلات غربية. تتميز بتصميم عصري ومساحات واسعة وحديقة خاصة وحمام سباحة. إطلالة رائعة على دمشق.',
            'amenities'   => [1, 3, 4, 5, 7, 8],
            'agent_id'    => $ag1->id,
            'image_count' => 5,
        ], $a1->id, 0);

        $propertySeed([
            'title_ar'    => 'شقة سكنية في أبو رمانة بمساحة 200 م²',
            'type_id'     => 1,
            'gov_id'      => 1,
            'area_id'     => 3,
            'purpose'     => 'sale',
            'price'       => 180000,
            'area_sqm'    => 200,
            'bedrooms'    => 3,
            'bathrooms'   => 2,
            'parking'     => 1,
            'floor'       => 4,
            'year_built'  => 2018,
            'furnished'   => false,
            'lat'         => 33.5200,
            'lng'         => 36.2850,
            'is_featured' => true,
            'desc_ar'     => 'شقة سكنية فاخرة في حي أبو رمانة الراقي. مساحة واسعة مع إطلالة جميلة, قريبة من جميع الخدمات والمرافق الحيوية.',
            'amenities'   => [2, 5, 7, 8],
            'agent_id'    => $ag2->id,
            'image_count' => 4,
        ], $a1->id, 0);

        $propertySeed([
            'title_ar'    => 'شقة مفروشة في الشعلان بمساحة 150 م²',
            'type_id'     => 1,
            'gov_id'      => 1,
            'area_id'     => 9,
            'purpose'     => 'rent',
            'price'       => 1200,
            'area_sqm'    => 150,
            'bedrooms'    => 2,
            'bathrooms'   => 2,
            'parking'     => 1,
            'floor'       => 3,
            'year_built'  => 2019,
            'furnished'   => true,
            'lat'         => 33.5100,
            'lng'         => 36.2750,
            'is_featured' => false,
            'desc_ar'     => 'شقة مفروشة بالكامل في حي الشعلان الحيوي. تشمل أجهزة كهربائية وأثاث عصري. مناسبة للعائلات الصغيرة أو المغتربين.',
            'amenities'   => [2, 5, 7],
            'agent_id'    => $ag1->id,
            'image_count' => 4,
        ], $a1->id, 0);

        $propertySeed([
            'title_ar'    => 'فيلا بحديقة في كفرسوسة بمساحة 450 م²',
            'type_id'     => 2,
            'gov_id'      => 1,
            'area_id'     => 4,
            'purpose'     => 'sale',
            'price'       => 550000,
            'area_sqm'    => 450,
            'bedrooms'    => 4,
            'bathrooms'   => 3,
            'parking'     => 2,
            'floor'       => null,
            'year_built'  => 2015,
            'furnished'   => true,
            'lat'         => 33.5050,
            'lng'         => 36.2950,
            'is_featured' => true,
            'desc_ar'     => 'فيلا راقية مع حديقة جميلة في منطقة كفرسوسة. تتكون من طابقين مع قبو وموقف سيارات. تشطيب فاخر مع تدفئة مركزية.',
            'amenities'   => [1, 3, 4, 5, 7, 8],
            'agent_id'    => $ag2->id,
            'image_count' => 5,
        ], $a1->id, 0);

        $propertySeed([
            'title_ar'    => 'محل تجاري في سوق الحميدية بمساحة 80 م²',
            'type_id'     => 4,
            'gov_id'      => 1,
            'area_id'     => 20,
            'purpose'     => 'rent',
            'price'       => 3500,
            'area_sqm'    => 80,
            'bedrooms'    => 0,
            'bathrooms'   => 1,
            'parking'     => 0,
            'floor'       => 1,
            'year_built'  => 2005,
            'furnished'   => false,
            'lat'         => 33.5110,
            'lng'         => 36.3050,
            'is_featured' => false,
            'desc_ar'     => 'محل تجاري مميز في قلب سوق الحميدية التاريخي. موقع استراتيجي مع حركة مشاة كثيفة. مناسب للمحلات التجارية الفاخرة.',
            'amenities'   => [4],
            'agent_id'    => $ag1->id,
            'image_count' => 3,
        ], $a1->id, 0);

        $propertySeed([
            'title_ar'    => 'أرض استثمارية في عدرا بمساحة 1200 م²',
            'type_id'     => 5,
            'gov_id'      => 2,
            'area_id'     => 38,
            'purpose'     => 'sale',
            'price'       => 250000,
            'area_sqm'    => 1200,
            'bedrooms'    => 0,
            'bathrooms'   => 0,
            'parking'     => 0,
            'floor'       => null,
            'year_built'  => null,
            'furnished'   => false,
            'lat'         => 33.6100,
            'lng'         => 36.5200,
            'is_featured' => true,
            'desc_ar'     => 'أرض استثمارية كبيرة في منطقة عدرا الصناعية. مناسبة للمشاريع التجارية والصناعية. قريبة من الطريق الدولي.',
            'amenities'   => [],
            'agent_id'    => $ag2->id,
            'image_count' => 3,
        ], $a1->id, 0);

        $propertySeed([
            'title_ar'    => 'منزل في ركن الدين بمساحة 300 م²',
            'type_id'     => 3,
            'gov_id'      => 1,
            'area_id'     => 6,
            'purpose'     => 'sale',
            'price'       => 220000,
            'area_sqm'    => 300,
            'bedrooms'    => 4,
            'bathrooms'   => 2,
            'parking'     => 1,
            'floor'       => null,
            'year_built'  => 2010,
            'furnished'   => false,
            'lat'         => 33.5180,
            'lng'         => 36.3100,
            'is_featured' => false,
            'desc_ar'     => 'منزل عائلي مستقل في منطقة ركن الدين. يضم حديقة صغيرة وموقف سيارة. قريب من المدارس والأسواق.',
            'amenities'   => [3, 5, 7],
            'agent_id'    => $ag1->id,
            'image_count' => 4,
        ], $a1->id, 0);

        $propertySeed([
            'title_ar'    => 'شقة استوديو في البرامكة بمساحة 45 م²',
            'type_id'     => 1,
            'gov_id'      => 1,
            'area_id'     => 10,
            'purpose'     => 'rent',
            'price'       => 500,
            'area_sqm'    => 45,
            'bedrooms'    => 1,
            'bathrooms'   => 1,
            'parking'     => 0,
            'floor'       => 2,
            'year_built'  => 2022,
            'furnished'   => true,
            'lat'         => 33.5000,
            'lng'         => 36.2800,
            'is_featured' => false,
            'desc_ar'     => 'شقة استوديو حديثة في البرامكة. مفروشة بالكامل ومناسبة للطلاب والموظفين. قريبة من الجامعة والمواصلات.',
            'amenities'   => [5, 8],
            'agent_id'    => $ag2->id,
            'image_count' => 3,
        ], $a1->id, 0);

        // Agency 2 — 6 mid-range
        $propertySeed([
            'title_ar'    => 'شقة في المزة بمساحة 175 م² - إطلالة رائعة',
            'type_id'     => 1,
            'gov_id'      => 1,
            'area_id'     => 2,
            'purpose'     => 'sale',
            'price'       => 140000,
            'area_sqm'    => 175,
            'bedrooms'    => 3,
            'bathrooms'   => 2,
            'parking'     => 1,
            'floor'       => 5,
            'year_built'  => 2017,
            'furnished'   => false,
            'lat'         => 33.5080,
            'lng'         => 36.2820,
            'is_featured' => true,
            'desc_ar'     => 'شقة سكنية جميلة في المزة بمساحة 175 متراً مربعاً. تتكون من 3 غرف نوم وصالون ومطبخ. موقع هادئ وقريب من الخدمات.',
            'amenities'   => [2, 5, 7],
            'agent_id'    => $ag3->id,
            'image_count' => 4,
        ], $a2->id, 0);

        $propertySeed([
            'title_ar'    => 'منزل في دمر بمساحة 250 م²',
            'type_id'     => 3,
            'gov_id'      => 1,
            'area_id'     => 15,
            'purpose'     => 'sale',
            'price'       => 175000,
            'area_sqm'    => 250,
            'bedrooms'    => 3,
            'bathrooms'   => 2,
            'parking'     => 1,
            'floor'       => null,
            'year_built'  => 2016,
            'furnished'   => false,
            'lat'         => 33.5020,
            'lng'         => 36.2200,
            'is_featured' => false,
            'desc_ar'     => 'منزل مستقل في دمر مع حديقة. يتميز بالهدوء والخصوصية. مناسب للعائلات الباحثة عن السكن المستقل.',
            'amenities'   => [3, 4, 7],
            'agent_id'    => $ag4->id,
            'image_count' => 4,
        ], $a2->id, 0);

        $propertySeed([
            'title_ar'    => 'أرض في حرستا بمساحة 800 م² - فرصة استثمارية',
            'type_id'     => 5,
            'gov_id'      => 2,
            'area_id'     => 32,
            'purpose'     => 'sale',
            'price'       => 90000,
            'area_sqm'    => 800,
            'bedrooms'    => 0,
            'bathrooms'   => 0,
            'parking'     => 0,
            'floor'       => null,
            'year_built'  => null,
            'furnished'   => false,
            'lat'         => 33.5580,
            'lng'         => 36.3680,
            'is_featured' => true,
            'desc_ar'     => 'أرض في حرستا مناسبة للاستثمار العقاري أو البناء. مساحة كبيرة بسعر منافس. قريبة من الطرق الرئيسية.',
            'amenities'   => [],
            'agent_id'    => $ag3->id,
            'image_count' => 3,
        ], $a2->id, 0);

        $propertySeed([
            'title_ar'    => 'شقة للايجار في حلب - الجميلية بمساحة 130 م²',
            'type_id'     => 1,
            'gov_id'      => 3,
            'area_id'     => 56,
            'purpose'     => 'rent',
            'price'       => 400,
            'area_sqm'    => 130,
            'bedrooms'    => 2,
            'bathrooms'   => 1,
            'parking'     => 0,
            'floor'       => 3,
            'year_built'  => 2021,
            'furnished'   => true,
            'lat'         => 36.1950,
            'lng'         => 37.1600,
            'is_featured' => false,
            'desc_ar'     => 'شقة مفروشة في حلب - منطقة الفرقان. مناسبة للعائلات الصغيرة. قريبة من المرافق العامة والمواصلات.',
            'amenities'   => [5, 8],
            'agent_id'    => $ag4->id,
            'image_count' => 4,
        ], $a2->id, 0);

        $propertySeed([
            'title_ar'    => 'منزل في الزبداني بمساحة 180 م²',
            'type_id'     => 3,
            'gov_id'      => 2,
            'area_id'     => 23,
            'purpose'     => 'sale',
            'price'       => 85000,
            'area_sqm'    => 180,
            'bedrooms'    => 3,
            'bathrooms'   => 2,
            'parking'     => 1,
            'floor'       => null,
            'year_built'  => 2014,
            'furnished'   => false,
            'lat'         => 33.7240,
            'lng'         => 36.1000,
            'is_featured' => true,
            'desc_ar'     => 'منزل ريفي جميل في الزبداني. يتميز بجو معتدل وإطلالة على الطبيعة. مناسب لقضاء العطلات والعيش الهادئ.',
            'amenities'   => [3, 4],
            'agent_id'    => $ag3->id,
            'image_count' => 4,
        ], $a2->id, 0);

        $propertySeed([
            'title_ar'    => 'شقة تجارية في الصالحية بمساحة 100 م²',
            'type_id'     => 1,
            'gov_id'      => 1,
            'area_id'     => 7,
            'purpose'     => 'rent',
            'price'       => 800,
            'area_sqm'    => 100,
            'bedrooms'    => 1,
            'bathrooms'   => 1,
            'parking'     => 0,
            'floor'       => 2,
            'year_built'  => 2019,
            'furnished'   => true,
            'lat'         => 33.5200,
            'lng'         => 36.3100,
            'is_featured' => false,
            'desc_ar'     => 'شقة مفروشة في منطقة الصالحية المركزية. مناسبة للاستخدام السكني أو كمكتب. قريبة من جميع الخدمات.',
            'amenities'   => [2, 5, 7],
            'agent_id'    => $ag4->id,
            'image_count' => 3,
        ], $a2->id, 0);

        // Agency 3 — 6 luxury/premium
        $propertySeed([
            'title_ar'    => 'فيلا فاخرة في المهاجرين بمساحة 550 م²',
            'type_id'     => 2,
            'gov_id'      => 1,
            'area_id'     => 5,
            'purpose'     => 'sale',
            'price'       => 1200000,
            'area_sqm'    => 550,
            'bedrooms'    => 5,
            'bathrooms'   => 4,
            'parking'     => 3,
            'floor'       => null,
            'year_built'  => 2022,
            'furnished'   => true,
            'lat'         => 33.5225,
            'lng'         => 36.2890,
            'is_featured' => true,
            'desc_ar'     => 'فيلا فاخرة في أرقى أحياء دمشق - المهاجرين. تشطيب راقٍ جداً مع مسبح داخلي وحديقة واسعة. إطلالة بانورامية على دمشق.',
            'amenities'   => [1, 3, 4, 5, 7, 8],
            'agent_id'    => $ag5->id,
            'image_count' => 5,
        ], $a3->id, 0);

        $propertySeed([
            'title_ar'    => 'شقة في اللاذقية بمساحة 200 م² - إطلالة على البحر',
            'type_id'     => 1,
            'gov_id'      => 6,
            'area_id'     => 86,
            'purpose'     => 'sale',
            'price'       => 350000,
            'area_sqm'    => 200,
            'bedrooms'    => 3,
            'bathrooms'   => 2,
            'parking'     => 1,
            'floor'       => 7,
            'year_built'  => 2023,
            'furnished'   => false,
            'lat'         => 35.5317,
            'lng'         => 35.7913,
            'is_featured' => true,
            'desc_ar'     => 'شقة فاخرة في مدينة اللاذقية الساحلية مع إطلالة رائعة على البحر المتوسط. تصميم عصري ومساحات واسعة.',
            'amenities'   => [2, 5, 7, 8],
            'agent_id'    => $ag6->id,
            'image_count' => 4,
        ], $a3->id, 0);

        $propertySeed([
            'title_ar'    => 'فيلا في حمص - الوعر بمساحة 400 م²',
            'type_id'     => 2,
            'gov_id'      => 4,
            'area_id'     => 71,
            'purpose'     => 'sale',
            'price'       => 280000,
            'area_sqm'    => 400,
            'bedrooms'    => 4,
            'bathrooms'   => 3,
            'parking'     => 2,
            'floor'       => null,
            'year_built'  => 2018,
            'furnished'   => true,
            'lat'         => 34.7500,
            'lng'         => 36.6900,
            'is_featured' => true,
            'desc_ar'     => 'فيلا متميزة في حي الوعر في حمص. تتميز بمساحات خضراء وحديقة خاصة. مناسبة للعائلات الكبيرة الباحثة عن الراحة.',
            'amenities'   => [1, 3, 4, 5, 7],
            'agent_id'    => $ag5->id,
            'image_count' => 4,
        ], $a3->id, 0);

        $propertySeed([
            'title_ar'    => 'أرض استثمارية في القرداحة بمساحة 1000 م²',
            'type_id'     => 5,
            'gov_id'      => 6,
            'area_id'     => 89,
            'purpose'     => 'sale',
            'price'       => 200000,
            'area_sqm'    => 1000,
            'bedrooms'    => 0,
            'bathrooms'   => 0,
            'parking'     => 0,
            'floor'       => null,
            'year_built'  => null,
            'furnished'   => false,
            'lat'         => 35.4500,
            'lng'         => 36.0600,
            'is_featured' => false,
            'desc_ar'     => 'أرض واسعة في القرداحة بريف اللاذقية. مناسبة للاستثمار السياحي أو بناء فيلا. إطلالة جميلة على الجبال والبحر.',
            'amenities'   => [],
            'agent_id'    => $ag6->id,
            'image_count' => 3,
        ], $a3->id, 0);

        $propertySeed([
            'title_ar'    => 'شقة مفروشة فاخرة في كفرسوسة بمساحة 160 م²',
            'type_id'     => 1,
            'gov_id'      => 1,
            'area_id'     => 4,
            'purpose'     => 'rent',
            'price'       => 1500,
            'area_sqm'    => 160,
            'bedrooms'    => 3,
            'bathrooms'   => 2,
            'parking'     => 1,
            'floor'       => 4,
            'year_built'  => 2021,
            'furnished'   => true,
            'lat'         => 33.5060,
            'lng'         => 36.2940,
            'is_featured' => false,
            'desc_ar'     => 'شقة مفروشة فاخرة في كفرسوسة. تشمل جميع الأجهزة الكهربائية والأثاث العصري. مناسبة للمغتربين ورجال الأعمال.',
            'amenities'   => [2, 4, 5, 7, 8],
            'agent_id'    => $ag5->id,
            'image_count' => 4,
        ], $a3->id, 0);

        $propertySeed([
            'title_ar'    => 'محل تجاري في حمص - وسط المدينة بمساحة 120 م²',
            'type_id'     => 4,
            'gov_id'      => 4,
            'area_id'     => 65,
            'purpose'     => 'rent',
            'price'       => 900,
            'area_sqm'    => 120,
            'bedrooms'    => 1,
            'bathrooms'   => 1,
            'parking'     => 0,
            'floor'       => 1,
            'year_built'  => 2017,
            'furnished'   => false,
            'lat'         => 34.7320,
            'lng'         => 36.7130,
            'is_featured' => false,
            'desc_ar'     => 'محل تجاري مميز في وسط مدينة حمص. واجهة زجاجية عصرية وموقع حيوي. مناسب للمطاعم أو المحلات التجارية.',
            'amenities'   => [4, 5],
            'agent_id'    => $ag6->id,
            'image_count' => 3,
        ], $a3->id, 0);

        $this->line("\n<info>Verification:</info>");
        $agencyCount = Agency::count();
        $agentCount = Agent::count();
        $propertyCount = Property::count();
        $imageCount = PropertyImage::count();

        $this->line("  Agencies:   {$agencyCount}");
        $this->line("  Agents:     {$agentCount}");
        $this->line("  Properties: {$propertyCount}");
        $this->line("  Images:     {$imageCount}");

        $this->newLine();
        $this->info("✅ Done — seeded {$agencyCount} agencies, {$agentCount} agents, {$propertyCount} properties, {$imageCount} images");

        return Command::SUCCESS;
    }
}
