<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        SubscriptionPlan::create([
            'name_ar'        => 'مجاني',
            'name_en'        => 'Free',
            'slug'           => 'free',
            'description_ar' => 'باقة مجانية للبدء — 3 عقارات ووكيل واحد',
            'description_en' => 'Free starter plan — 3 properties & 1 agent',
            'price'          => 0,
            'currency'       => 'USD',
            'duration_days'  => 30,
            'max_properties' => 3,
            'max_agents'     => 1,
            'is_featured'    => false,
            'features'       => ['basic_listing', 'email_support'],
            'sort'           => 1,
            'is_active'      => true,
        ]);

        SubscriptionPlan::create([
            'name_ar'        => 'احترافي شهري',
            'name_en'        => 'Pro Monthly',
            'slug'           => 'pro-monthly',
            'description_ar' => 'باقة احترافية شهرية — 25 عقار و5 وكلاء + مميز',
            'description_en' => 'Pro Monthly — 25 properties, 5 agents + featured',
            'price'          => 29.00,
            'currency'       => 'USD',
            'duration_days'  => 30,
            'max_properties' => 25,
            'max_agents'     => 5,
            'is_featured'    => false,
            'features'       => ['basic_listing', 'featured_listing', 'priority_support', 'analytics'],
            'sort'           => 2,
            'is_active'      => true,
        ]);

        SubscriptionPlan::create([
            'name_ar'        => 'احترافي سنوي',
            'name_en'        => 'Pro Annual',
            'slug'           => 'pro-annual',
            'description_ar' => 'باقة احترافية سنوية (وفر شهرين) — 25 عقار و5 وكلاء + مميز',
            'description_en' => 'Pro Annual (save 2 months) — 25 properties, 5 agents + featured',
            'price'          => 290.00,
            'currency'       => 'USD',
            'duration_days'  => 365,
            'max_properties' => 25,
            'max_agents'     => 5,
            'is_featured'    => true,
            'features'       => ['basic_listing', 'featured_listing', 'priority_support', 'analytics'],
            'sort'           => 3,
            'is_active'      => true,
        ]);

        SubscriptionPlan::create([
            'name_ar'        => 'غير محدود شهري',
            'name_en'        => 'Unlimited Monthly',
            'slug'           => 'unlimited-monthly',
            'description_ar' => 'باقة غير محدودة شهرية — عقارات ووكلاء غير محدودين',
            'description_en' => 'Unlimited Monthly — no limits on properties or agents',
            'price'          => 79.00,
            'currency'       => 'USD',
            'duration_days'  => 30,
            'max_properties' => 0, // unlimited
            'max_agents'     => 0,  // unlimited
            'is_featured'    => false,
            'features'       => ['basic_listing', 'featured_listing', 'hot_deals', 'priority_support', 'analytics', 'api_access'],
            'sort'           => 4,
            'is_active'      => true,
        ]);

        SubscriptionPlan::create([
            'name_ar'        => 'غير محدود سنوي',
            'name_en'        => 'Unlimited Annual',
            'slug'           => 'unlimited-annual',
            'description_ar' => 'باقة غير محدودة سنوية (وفر شهرين) — عقارات ووكلاء غير محدودين + براندينج مخصص',
            'description_en' => 'Unlimited Annual (save 2 months) — everything + custom branding',
            'price'          => 790.00,
            'currency'       => 'USD',
            'duration_days'  => 365,
            'max_properties' => 0,
            'max_agents'     => 0,
            'is_featured'    => true,
            'features'       => ['basic_listing', 'featured_listing', 'hot_deals', 'priority_support', 'analytics', 'api_access', 'custom_branding'],
            'sort'           => 5,
            'is_active'      => true,
        ]);
    }
}
