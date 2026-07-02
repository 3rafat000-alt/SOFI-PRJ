<?php

namespace App\Services;

use App\Models\GoldPrice;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Gold Price Service
 *
 * Pulls the global gold spot price (XAU/USD) and derives per-karat buy/sell
 * prices automatically, so the wallet's gold rates track the world market
 * instead of being typed in by hand.
 *
 * Pricing model:
 *   spot24  = USD per gram of pure 24k gold (from the feed)
 *   mid(k)  = spot24 * (k / 24)            // purity-scaled mid for each karat
 *   buy(k)  = mid * (1 + margin/100)       // user buys gold → pays a touch more
 *   sell(k) = mid * (1 - margin/100)       // user sells gold → gets a touch less
 *   spread  = (buy - sell) / sell * 100    // same formula the admin form uses
 *
 * Auto-update is gated by the `gold_auto_update` system setting so the admin
 * can flip it on/off; `gold_auto_margin` tunes the platform margin.
 */
class GoldPriceService
{
    /** Karats we maintain prices for. */
    public const KARATS = ['24', '22', '21', '18'];

    /** Grams in one troy ounce — feeds quote XAU per troy ounce. */
    private const GRAMS_PER_OZT = 31.1034768;

    /** Default platform margin (% applied each side of mid) when unset. */
    private const DEFAULT_MARGIN = 0.75;

    public function isAutoEnabled(): bool
    {
        return (bool) SystemSetting::get('gold_auto_update', false);
    }

    public function margin(): float
    {
        $m = (float) SystemSetting::get('gold_auto_margin', self::DEFAULT_MARGIN);

        // Clamp to a sane range — a runaway margin would publish absurd prices.
        return max(0.0, min(10.0, $m));
    }

    /**
     * Fetch the current spot price as USD per gram of pure 24k gold.
     * Returns null on any failure (caller must keep the last good prices).
     */
    public function fetchSpotPerGram24k(): ?float
    {
        $provider = (string) config('services.gold.provider', 'gold-api');
        $timeout = (int) config('services.gold.timeout', 15);

        try {
            if ($provider === 'goldapi') {
                $key = config('services.gold.goldapi_key');
                if (!$key) {
                    Log::warning('Gold: provider=goldapi but GOLDAPI_KEY is missing.');
                    return null;
                }

                $res = Http::timeout($timeout)
                    ->withHeaders(['x-access-token' => $key])
                    ->acceptJson()
                    ->get('https://www.goldapi.io/api/XAU/USD');

                if (!$res->ok()) {
                    Log::warning('Gold: goldapi.io request failed', ['status' => $res->status()]);
                    return null;
                }

                // goldapi.io returns the 24k per-gram price directly.
                $gram = (float) $res->json('price_gram_24k');
                return $gram > 0 ? round($gram, 4) : null;
            }

            // Default free provider: gold-api.com — no key, returns USD per troy ounce.
            $res = Http::timeout($timeout)
                ->acceptJson()
                ->get('https://api.gold-api.com/price/XAU');

            if (!$res->ok()) {
                Log::warning('Gold: gold-api.com request failed', ['status' => $res->status()]);
                return null;
            }

            $ounce = (float) $res->json('price');
            return $ounce > 0 ? round($ounce / self::GRAMS_PER_OZT, 4) : null;
        } catch (\Throwable $e) {
            Log::error('Gold: spot fetch threw', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Fetch the global spot and rewrite every karat's buy/sell price.
     *
     * @return array{success:bool, updated?:int, spot?:float, margin?:float, message?:string}
     */
    public function refresh(): array
    {
        $spot = $this->fetchSpotPerGram24k();
        if ($spot === null) {
            return ['success' => false, 'message' => 'تعذّر جلب السعر العالمي للذهب — تم الإبقاء على آخر أسعار.'];
        }

        $margin = $this->margin();
        $updated = 0;

        foreach (self::KARATS as $karat) {
            $mid = round($spot * ((int) $karat / 24), 2);
            $buy = round($mid * (1 + $margin / 100), 2);
            $sell = round($mid * (1 - $margin / 100), 2);
            $spread = $sell > 0 ? round(($buy - $sell) / $sell * 100, 2) : 0;

            $row = GoldPrice::firstOrNew(['karat' => $karat]);
            $row->buy_price = $buy;
            $row->sell_price = $sell;
            $row->spread = $spread;
            $row->source = 'auto';
            // New karats default to active; never silently flip an admin-disabled karat back on.
            if (!$row->exists) {
                $row->is_active = true;
            }
            $row->save();
            $updated++;
        }

        SystemSetting::set('gold_last_auto_update', now()->toIso8601String(), 'string');
        SystemSetting::set('gold_last_spot_24k', (string) $spot, 'decimal');

        Log::info('Gold prices auto-refreshed', [
            'spot_per_gram_24k' => $spot,
            'margin' => $margin,
            'updated' => $updated,
        ]);

        return ['success' => true, 'updated' => $updated, 'spot' => $spot, 'margin' => $margin];
    }
}
