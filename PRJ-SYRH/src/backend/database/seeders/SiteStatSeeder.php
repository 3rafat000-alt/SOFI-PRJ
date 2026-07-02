<?php

namespace Database\Seeders;

use App\Models\SiteStat;
use Illuminate\Database\Seeder;

class SiteStatSeeder extends Seeder
{
    public function run(): void
    {
        SiteStat::create([
            'happy_clients'     => 1500,
            'properties_listed' => 24,
            'agents_count'      => 5,
            'satisfaction_pct'  => 98,
        ]);
    }
}
