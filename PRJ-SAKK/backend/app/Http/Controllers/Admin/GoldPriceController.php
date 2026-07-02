<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GoldPrice;
use App\Models\GoldTransaction;
use App\Models\GoldWallet;
use App\Models\SystemSetting;
use App\Services\GoldPriceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class GoldPriceController extends Controller
{
    public function index(): View
    {
        $prices = GoldPrice::orderByRaw("CAST(karat AS UNSIGNED) DESC")->get();

        // Sync is handled by the hourly `gold:update-prices` cron (see
        // GoldPriceService::refresh + routes/console.php). A synchronous
        // 15s HTTP fetch on every page load hung the admin panel — removed.
        // The manual "refresh now" button (refresh() below) is unaffected.

        $stats = [
            'active_prices' => GoldPrice::where('is_active', true)->count(),
            'total_prices' => $prices->count(),
            'total_transactions' => GoldTransaction::count(),
            'total_grams_traded' => GoldTransaction::where('status', 'completed')->sum('grams'),
            'total_volume_usd' => GoldTransaction::where('status', 'completed')->sum('total_usd'),
            'active_users' => GoldWallet::where('balance_grams', '>', 0)->count(),
            'avg_spread' => round((float) GoldPrice::where('is_active', true)->avg('spread'), 2),
            'last_updated' => GoldPrice::max('updated_at'),
            // Automatic global-price sync
            'auto_enabled' => (bool) SystemSetting::get('gold_auto_update', false),
            'auto_margin' => (float) SystemSetting::get('gold_auto_margin', 0.75),
            'auto_provider' => (string) config('services.gold.provider', 'gold-api'),
            'last_auto_update' => SystemSetting::get('gold_last_auto_update'),
            'last_spot_24k' => (float) SystemSetting::get('gold_last_spot_24k', 0),
        ];

        // Recent transactions for the merged page
        $recentTransactions = GoldTransaction::with('user')
            ->orderBy('created_at', 'desc')
            ->take(15)
            ->get();

        $txStats = [
            'total' => GoldTransaction::count(),
            'buy' => GoldTransaction::where('type', 'buy')->count(),
            'sell' => GoldTransaction::where('type', 'sell')->count(),
            'volume' => GoldTransaction::where('status', 'completed')->sum('total_usd'),
            'fees' => GoldTransaction::where('status', 'completed')->sum('fee_usd'),
        ];

        return view('admin.gold.index', compact('prices', 'stats', 'recentTransactions', 'txStats'));
    }

    /**
     * Add a brand-new karat row. Mirrors update()'s validation (buy>=sell)
     * and audit style; source is always 'manual' on creation.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'karat' => 'required|numeric|min:1|max:24|unique:gold_prices,karat',
            'buy_price' => 'required|numeric|min:0.01|gte:sell_price',
            'sell_price' => 'required|numeric|min:0.01',
        ], [
            'karat.unique' => 'هذا العيار موجود مسبقاً',
            'buy_price.gte' => 'سعر الشراء يجب أن يكون ≥ سعر البيع',
        ]);

        $spread = $validated['sell_price'] > 0
            ? round(($validated['buy_price'] - $validated['sell_price']) / $validated['sell_price'] * 100, 2)
            : 0;

        $goldPrice = GoldPrice::create([
            'karat' => $validated['karat'],
            'buy_price' => $validated['buy_price'],
            'sell_price' => $validated['sell_price'],
            'spread' => $spread,
            'source' => 'manual',
            'is_active' => true,
        ]);

        Log::info('Gold price karat added', [
            'actor_id' => $request->user()?->id,
            'karat' => $goldPrice->karat,
            'buy_price' => $goldPrice->buy_price,
            'sell_price' => $goldPrice->sell_price,
        ]);

        return redirect()->route('admin.gold.index')
            ->with('success', 'تمت إضافة ' . $goldPrice->karat_label . ' بنجاح');
    }

    public function update(Request $request, GoldPrice $goldPrice): RedirectResponse
    {
        $validated = $request->validate([
            'buy_price' => 'required|numeric|min:0.01|gte:sell_price',
            'sell_price' => 'required|numeric|min:0.01',
            'is_active' => 'boolean',
        ], [
            'buy_price.gte' => 'سعر الشراء يجب أن يكون ≥ سعر البيع',
        ]);

        $validated['spread'] = $validated['sell_price'] > 0
            ? round(($validated['buy_price'] - $validated['sell_price']) / $validated['sell_price'] * 100, 2)
            : 0;
        // A hand edit pins this karat as manual; the next auto-sync will overwrite it
        // only while auto-update is enabled.
        $validated['source'] = 'manual';

        // Audit trail for manual price edits — lightweight Log entry, no new table.
        Log::info('Gold price manually edited', [
            'actor_id' => $request->user()?->id,
            'karat' => $goldPrice->karat,
            'old_buy_price' => (float) $goldPrice->buy_price,
            'old_sell_price' => (float) $goldPrice->sell_price,
            'new_buy_price' => $validated['buy_price'],
            'new_sell_price' => $validated['sell_price'],
        ]);

        $goldPrice->update($validated);

        return redirect()->route('admin.gold.index')
            ->with('success', 'تم تحديث سعر ' . $goldPrice->karat_label . ' بنجاح');
    }

    /**
     * Flip only the active/inactive flag for a karat — does NOT touch price
     * or source. Separated from update() so the index-page toggle can no
     * longer silently pin source='manual' and detach the karat from auto-sync.
     */
    public function toggleActive(GoldPrice $goldPrice): RedirectResponse
    {
        $old = (bool) $goldPrice->is_active;
        $new = !$old;

        $goldPrice->update(['is_active' => $new]);

        Log::info('Gold price active flag toggled', [
            'actor_id' => request()->user()?->id,
            'karat' => $goldPrice->karat,
            'is_active_old' => $old,
            'is_active_new' => $new,
        ]);

        return redirect()->route('admin.gold.index')
            ->with('success', 'تم ' . ($new ? 'تفعيل' : 'تعطيل') . ' ' . $goldPrice->karat_label);
    }

    /**
     * Save the automatic-sync settings (on/off toggle + platform margin).
     */
    public function autoSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'auto_update' => 'boolean',
            'margin' => 'required|numeric|min:0|max:10',
        ]);

        $oldAutoUpdate = (bool) SystemSetting::get('gold_auto_update', false);
        $oldMargin = (float) SystemSetting::get('gold_auto_margin', 0.75);
        $newAutoUpdate = $request->boolean('auto_update');

        SystemSetting::set('gold_auto_update', $newAutoUpdate ? '1' : '0', 'boolean');
        SystemSetting::set('gold_auto_margin', (string) $validated['margin'], 'decimal');

        Log::info('Gold auto-sync settings changed', [
            'actor_id' => $request->user()?->id,
            'auto_update_old' => $oldAutoUpdate,
            'auto_update_new' => $newAutoUpdate,
            'margin_old' => $oldMargin,
            'margin_new' => $validated['margin'],
        ]);

        return redirect()->route('admin.gold.index')
            ->with('success', 'تم حفظ إعدادات التحديث التلقائي للذهب');
    }

    /**
     * Fetch the global spot price now and rewrite every karat (manual trigger).
     */
    public function refresh(GoldPriceService $service): RedirectResponse
    {
        $result = $service->refresh();

        if (!$result['success']) {
            Log::info('Gold manual refresh failed', [
                'actor_id' => request()->user()?->id,
                'message' => $result['message'] ?? null,
            ]);

            return redirect()->route('admin.gold.index')
                ->with('error', $result['message'] ?? 'تعذّر تحديث الأسعار من السوق العالمي');
        }

        Log::info('Gold manual refresh applied', [
            'actor_id' => request()->user()?->id,
            'updated' => $result['updated'],
            'spot' => $result['spot'],
            'margin' => $result['margin'],
        ]);

        return redirect()->route('admin.gold.index')
            ->with('success', sprintf(
                'تم تحديث %d عيارات من السوق العالمي (سعر الأونصة/غرام 24: $%s ، هامش %s%%)',
                $result['updated'],
                number_format($result['spot'], 2),
                number_format($result['margin'], 2),
            ));
    }

    public function transactions(Request $request): View
    {
        $query = GoldTransaction::with('user')
            ->orderBy('created_at', 'desc');

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($u) => $u->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $transactions = $query->paginate(20)->withQueryString();

        $completed = GoldTransaction::where('status', 'completed');
        $stats = [
            'total' => GoldTransaction::count(),
            'buy' => GoldTransaction::where('type', 'buy')->count(),
            'sell' => GoldTransaction::where('type', 'sell')->count(),
            'volume' => (clone $completed)->sum('total_usd'),
            'buy_grams' => (clone $completed)->where('type', 'buy')->sum('grams'),
            'sell_grams' => (clone $completed)->where('type', 'sell')->sum('grams'),
            'fees' => (clone $completed)->sum('fee_usd'),
            'pending' => GoldTransaction::where('status', 'pending')->count(),
            'avg_ticket' => round((float) (clone $completed)->avg('total_usd'), 2),
        ];

        return view('admin.gold.transactions', compact('transactions', 'stats'));
    }
}
