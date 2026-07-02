<?php

namespace Database\Seeders;

use App\Models\GoldPrice;
use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class GoldPriceSeeder extends Seeder
{
    public function run(): void
    {
        // Fallback prices — overwritten by the first global auto-sync. These exist
        // only so the wallet has numbers before the scheduler/manual fetch runs.
        $prices = [
            ['karat' => '24', 'buy_price' => '89.50', 'sell_price' => '88.20', 'spread' => '1.47'],
            ['karat' => '22', 'buy_price' => '82.05', 'sell_price' => '80.85', 'spread' => '1.48'],
            ['karat' => '21', 'buy_price' => '78.31', 'sell_price' => '77.18', 'spread' => '1.46'],
            ['karat' => '18', 'buy_price' => '67.13', 'sell_price' => '66.15', 'spread' => '1.48'],
        ];

        foreach ($prices as $data) {
            GoldPrice::updateOrCreate(['karat' => $data['karat']], $data);
        }

        // Default: automatic global-price sync ON with a ~1.5% spread (±0.75%).
        // firstOrCreate so an admin's later override is never clobbered by re-seeds.
        SystemSetting::firstOrCreate(
            ['key' => 'gold_auto_update'],
            ['value' => '1', 'type' => 'boolean', 'group' => 'gold', 'label' => 'تحديث أسعار الذهب تلقائياً']
        );
        SystemSetting::firstOrCreate(
            ['key' => 'gold_auto_margin'],
            ['value' => '0.75', 'type' => 'decimal', 'group' => 'gold', 'label' => 'هامش المنصّة للذهب (%)']
        );
    }
}
