<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ExchangeRateController extends Controller
{
    public function __construct(protected ExchangeRateService $exchangeRateService) {}

    /**
     * Get current exchange rate (USD/SYP)
     * 
     * Returns the single exchange rate with calculated buy/sell rates
     */
    public function getRate(Request $request): JsonResponse
    {
        $from = $request->get('from', 'USD');
        $to = $request->get('to', 'SYP');

        $result = $this->exchangeRateService->getRate($from, $to);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'سعر الصرف غير متوفر',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Get all rates (simplified - just USD/SYP)
     */
    public function getAllRates(Request $request): JsonResponse
    {
        $result = $this->exchangeRateService->getAllRates();

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'سعر الصرف غير متوفر',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Convert amount between currencies
     */
    public function convert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'from' => 'required|string|in:USD,SYP',
            'to' => 'required|string|in:USD,SYP',
            'direction' => 'nullable|string|in:buy,sell',
        ]);

        $result = $this->exchangeRateService->convert(
            amount: $validated['amount'],
            from: $validated['from'],
            to: $validated['to'],
            direction: $validated['direction'] ?? 'sell'
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'فشل التحويل',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Get rate history for charts
     */
    public function getHistory(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);

        $result = $this->exchangeRateService->getRateHistory($days);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Check if exchange rate is configured
     */
    public function isConfigured(): JsonResponse
    {
        $configured = $this->exchangeRateService->isConfigured();

        return response()->json([
            'success' => true,
            'data' => [
                'configured' => $configured,
            ],
        ]);
    }
}
