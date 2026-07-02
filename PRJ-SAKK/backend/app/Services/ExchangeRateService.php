<?php

namespace App\Services;

use App\Models\ExchangeRate;
use App\Models\ExchangeRateHistory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Simplified Exchange Rate Service
 * 
 * Single row system: Only USD/SYP with spread for buy/sell rates
 * - rate: The base exchange rate (1 USD = X SYP)
 * - spread: Percentage difference between buy and sell
 * - buy_rate: Rate when user buys USD (sells SYP) - calculated automatically
 * - sell_rate: Rate when user sells USD (buys SYP) - calculated automatically
 */
class ExchangeRateService
{
    protected const CACHE_KEY = 'exchange_rate_usd_syp';
    protected const CACHE_TTL = 300; // 5 minutes

    /**
     * Get the current exchange rate
     * Returns single row USD/SYP with calculated buy/sell rates
     */
    public function getRate(string $from = 'USD', string $to = 'SYP'): array
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        // Same currency = 1:1
        if ($from === $to) {
            return [
                'success' => true,
                'from' => $from,
                'to' => $to,
                'rate' => 1.0,
                'buy_rate' => 1.0,
                'sell_rate' => 1.0,
                'spread' => 0,
                'source' => 'direct',
            ];
        }

        // Try cache first (skip if stale serialized class)
        $cached = Cache::get(self::CACHE_KEY);
        if ($cached instanceof ExchangeRate) {
            return $this->formatRateResponse($cached, $from, $to);
        }
        Cache::forget(self::CACHE_KEY);

        // Get from database (single row)
        $rate = ExchangeRate::where('from_currency', 'USD')
            ->where('to_currency', 'SYP')
            ->where('is_active', true)
            ->first();

        if (!$rate) {
            return [
                'success' => false,
                'error' => 'سعر الصرف غير متوفر. يرجى تعيينه من لوحة الإدارة.',
                'from' => $from,
                'to' => $to,
            ];
        }

        // Cache the rate
        Cache::put(self::CACHE_KEY, $rate, self::CACHE_TTL);

        return $this->formatRateResponse($rate, $from, $to);
    }

    /**
     * Format rate response based on direction (USD→SYP or SYP→USD)
     */
    protected function formatRateResponse(ExchangeRate $rate, string $from, string $to): array
    {
        $baseRate = (float) $rate->rate;
        $spread = (float) $rate->spread;
        
        // Calculate buy/sell rates from spread using model accessors
        $buyRate = $rate->getBuyRate();
        $sellRate = $rate->getSellRate();

        // If requesting SYP→USD, invert the rates
        if ($from === 'SYP' && $to === 'USD') {
            return [
                'success' => true,
                'from' => 'SYP',
                'to' => 'USD',
                'rate' => 1 / $baseRate,
                'buy_rate' => 1 / $sellRate,  // Inverted - higher SYP cost when buying USD
                'sell_rate' => 1 / $buyRate,  // Inverted - lower SYP received when selling USD
                'spread' => $spread,
                'source' => $rate->source,
                'updated_at' => $rate->updated_at->toIso8601String(),
            ];
        }

        // USD→SYP (default)
        return [
            'success' => true,
            'from' => 'USD',
            'to' => 'SYP',
            'rate' => $baseRate,
            'buy_rate' => $buyRate,
            'sell_rate' => $sellRate,
            'spread' => $spread,
            'source' => $rate->source,
            'updated_at' => $rate->updated_at->toIso8601String(),
        ];
    }

    /**
     * Get the current exchange rate for a money-committing operation.
     *
     * Bypasses the read cache entirely and locks the authoritative row
     * (`lockForUpdate`) so the caller is guaranteed the committed DB value at
     * execution time — not a value that may be up to CACHE_TTL seconds stale.
     * MUST be called from inside an open DB transaction (the lock is only
     * meaningful/held there); callers that need a display-only rate should
     * keep using {@see getRate()}.
     */
    public function getAuthoritativeRate(string $from = 'USD', string $to = 'SYP'): array
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            return [
                'success' => true,
                'from' => $from,
                'to' => $to,
                'rate' => 1.0,
                'buy_rate' => 1.0,
                'sell_rate' => 1.0,
                'spread' => 0,
                'source' => 'direct',
            ];
        }

        $rate = ExchangeRate::where('from_currency', 'USD')
            ->where('to_currency', 'SYP')
            ->where('is_active', true)
            ->lockForUpdate()
            ->first();

        if (!$rate) {
            return [
                'success' => false,
                'error' => 'سعر الصرف غير متوفر. يرجى تعيينه من لوحة الإدارة.',
                'from' => $from,
                'to' => $to,
            ];
        }

        return $this->formatRateResponse($rate, $from, $to);
    }

    /**
     * Convert amount between currencies
     * 
     * @param float $amount Amount to convert
     * @param string $from Source currency (USD or SYP)
     * @param string $to Target currency (USD or SYP)
     * @param string $direction 'buy' or 'sell' from user perspective
     *                          'buy' = user is buying $to currency
     *                          'sell' = user is selling $from currency
     */
    public function convert(float $amount, string $from, string $to, string $direction = 'sell'): array
    {
        $rateData = $this->getRate($from, $to);

        if (!$rateData['success']) {
            return $rateData;
        }

        // Use appropriate rate based on direction
        // 'sell' = user selling $from to get $to (worse rate for user)
        // 'buy' = user buying $to with $from (better rate for user)
        $rate = $direction === 'buy' ? $rateData['buy_rate'] : $rateData['sell_rate'];
        $convertedAmount = $amount * $rate;

        return [
            'success' => true,
            'original_amount' => $amount,
            'original_currency' => $from,
            'converted_amount' => round($convertedAmount, 2),
            'target_currency' => $to,
            'rate_used' => $rate,
            'mid_rate' => $rateData['rate'],
            'spread' => $rateData['spread'],
            'direction' => $direction,
        ];
    }

    /**
     * Get all rates (simplified - just USD/SYP)
     */
    public function getAllRates(string $baseCurrency = 'USD'): array
    {
        $rateData = $this->getRate('USD', 'SYP');

        if (!$rateData['success']) {
            return $rateData;
        }

        return [
            'success' => true,
            'base' => 'USD',
            'rates' => [
                'SYP' => [
                    'rate' => $rateData['rate'],
                    'buy_rate' => $rateData['buy_rate'],
                    'sell_rate' => $rateData['sell_rate'],
                    'spread' => $rateData['spread'],
                ],
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Update exchange rate (admin function)
     * Only updates the single USD/SYP row
     * 
     * @param float $rate Base exchange rate (1 USD = X SYP)
     * @param float $spread Spread percentage (e.g., 2.0 for 2%)
     */
    public function updateRate(float $rate, float $spread = 2.0): ExchangeRate
    {
        if ($rate <= 0) {
            throw new \InvalidArgumentException('Rate must be greater than 0.');
        }

        // Calculate buy/sell rates from spread
        $halfSpread = $spread / 200;
        $buyRate = $rate * (1 - $halfSpread);
        $sellRate = $rate * (1 + $halfSpread);

        $exchangeRate = ExchangeRate::updateOrCreate(
            ['from_currency' => 'USD', 'to_currency' => 'SYP'],
            [
                'rate' => $rate,
                'buy_rate' => $buyRate,
                'sell_rate' => $sellRate,
                'spread' => $spread,
                'source' => 'manual',
                'is_active' => true,
                'fetched_at' => now(),
            ]
        );

        // Record history
        ExchangeRateHistory::create([
            'from_currency' => 'USD',
            'to_currency' => 'SYP',
            'rate' => $rate,
            'buy_rate' => $buyRate,
            'sell_rate' => $sellRate,
            'source' => 'manual',
            'recorded_at' => now(),
        ]);

        // Clear cache
        Cache::forget(self::CACHE_KEY);

        Log::info('Exchange rate updated', [
            'rate' => $rate,
            'spread' => $spread,
            'buy_rate' => $buyRate,
            'sell_rate' => $sellRate,
        ]);

        return $exchangeRate;
    }

    /**
     * Get current rate model (for admin)
     */
    public function getCurrentRate(): ?ExchangeRate
    {
        return ExchangeRate::where('from_currency', 'USD')
            ->where('to_currency', 'SYP')
            ->first();
    }

    /**
     * Get rate history for charts
     */
    public function getRateHistory(int $days = 30): array
    {
        $history = ExchangeRateHistory::where('from_currency', 'USD')
            ->where('to_currency', 'SYP')
            ->where('recorded_at', '>=', now()->subDays($days))
            ->orderBy('recorded_at')
            ->get(['rate', 'buy_rate', 'sell_rate', 'recorded_at']);

        return [
            'success' => true,
            'from' => 'USD',
            'to' => 'SYP',
            'period_days' => $days,
            'data' => $history->map(fn($h) => [
                'rate' => (float) $h->rate,
                'buy_rate' => (float) $h->buy_rate,
                'sell_rate' => (float) $h->sell_rate,
                'date' => $h->recorded_at->toDateString(),
                'timestamp' => $h->recorded_at->toIso8601String(),
            ])->all(),
        ];
    }

    /**
     * Check if exchange rate is configured
     */
    public function isConfigured(): bool
    {
        return ExchangeRate::where('from_currency', 'USD')
            ->where('to_currency', 'SYP')
            ->where('is_active', true)
            ->exists();
    }
}
