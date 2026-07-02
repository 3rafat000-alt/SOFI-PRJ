<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\SiteStat;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        // Clear seed data in reverse FK dependency order
        DB::table('property_amenity')->truncate();
        DB::table('property_images')->truncate();
        DB::table('properties')->truncate();
        DB::table('agents')->truncate();
        DB::table('agencies')->truncate();
        DB::table('amenities')->truncate();
        DB::table('property_types')->truncate();
        DB::table('areas')->truncate();
        DB::table('governorates')->truncate();
        SiteStat::truncate();
        Setting::truncate();
        SubscriptionPlan::truncate();

        Schema::enableForeignKeyConstraints();

        $this->call([
            // Static data (order matters for FK references)
            GovernorateSeeder::class,
            AreaSeeder::class,
            PropertyTypeSeeder::class,
            AmenitySeeder::class,
            AgencySeeder::class,
            AgentSeeder::class,
            PropertySeeder::class,
            SiteStatSeeder::class,

            // System data
            RoleSeeder::class,
            SubscriptionPlanSeeder::class,
            SettingSeeder::class,
        ]);

        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@syriahomes.sy'],
            [
                'name'     => 'مدير النظام',
                'password' => Hash::make('admin123'),
                'phone'    => '+963 933 000 000',
                'locale'   => 'ar',
                'status'   => 'active',
            ]
        );
        $admin->assignRole('admin');

        // Create demo agency owner
        $owner = User::firstOrCreate(
            ['email' => 'owner@byout-al-sham.com'],
            [
                'name'     => 'أحمد المدير',
                'password' => Hash::make('owner123'),
                'phone'    => '+963 933 111 111',
                'locale'   => 'ar',
                'status'   => 'active',
            ]
        );
        $owner->assignRole('agency');
    }
}
