<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExchangeRate;
use App\Models\ExchangeRateHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ExchangeRateController extends Controller
{
    public function index(): View
    {
        $rate = ExchangeRate::where('from_currency', 'USD')
            ->where('to_currency', 'SYP')
            ->first();

        $rateHistory = ExchangeRateHistory::where('from_currency', 'USD')
            ->where('to_currency', 'SYP')
            ->latest('recorded_at')
            ->take(10)
            ->get();

        return view('admin.exchange-rates.index', compact('rate', 'rateHistory'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'rate' => 'required|numeric|min:1',
            'spread' => 'required|numeric|min:0|max:20',
        ], [
            'rate.required' => 'سعر الصرف مطلوب.',
            'rate.numeric' => 'سعر الصرف يجب أن يكون رقماً.',
            'rate.min' => 'سعر الصرف يجب أن يكون 1 على الأقل.',
            'spread.required' => 'نسبة السبريد مطلوبة.',
            'spread.numeric' => 'نسبة السبريد يجب أن تكون رقماً.',
            'spread.min' => 'نسبة السبريد يجب أن تكون 0 على الأقل.',
            'spread.max' => 'نسبة السبريد يجب أن لا تتجاوز 20%.',
        ]);

        $rate = $validated['rate'];
        $spread = $validated['spread'];
        $halfSpread = $spread / 200;
        $buyRate = $rate * (1 - $halfSpread);
        $sellRate = $rate * (1 + $halfSpread);

        ExchangeRate::updateOrCreate(
            ['from_currency' => 'USD', 'to_currency' => 'SYP'],
            [
                'rate' => $rate,
                'buy_rate' => $buyRate,
                'sell_rate' => $sellRate,
                'spread' => $spread,
                'source' => 'admin',
                'is_active' => true,
                'fetched_at' => now(),
            ]
        );

        ExchangeRateHistory::create([
            'from_currency' => 'USD',
            'to_currency' => 'SYP',
            'rate' => $rate,
            'buy_rate' => $buyRate,
            'sell_rate' => $sellRate,
            'source' => 'admin',
            'recorded_at' => now(),
        ]);

        Cache::forget('exchange_rate_usd_syp');

        return redirect()->route('admin.exchange-rates.index')
            ->with('success', 'تم تحديث سعر الصرف بنجاح');
    }
}
