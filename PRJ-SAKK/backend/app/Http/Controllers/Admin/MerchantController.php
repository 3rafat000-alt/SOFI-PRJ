<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\TransactionStatus;
use App\Models\ExchangeRate;
use App\Models\Merchant;
use App\Services\PartnerApprovalNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MerchantController extends Controller
{
    public const TYPES = [
        'physical' => 'متجر فعلي',
        'ecommerce' => 'متجر إلكتروني',
        'both' => 'فعلي + إلكتروني',
    ];

    /**
     * Merchants index — full page or table-only fragment for AJAX debounced search.
     *
     * Fragment trigger: `$request->ajax()` OR `X-Requested-With: XMLHttpRequest`
     * header OR explicit `?fragment=1` query parameter.
     *
     * Fragment response: renders `admin.merchants.partials._table` with `$merchants`.
     * Full response: renders `admin.merchants.index` (which includes the same partial).
     * Paginator always carries query string so AJAX pagination keeps filters.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $type = $request->get('type');
        $status = $request->get('status');
        $sortField = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');

        $allowedSorts = ['store_name', 'merchant_code', 'type', 'is_active', 'balance', 'created_at'];
        if (!in_array($sortField, $allowedSorts)) {
            $sortField = 'created_at';
        }

        $merchants = Merchant::query()
            ->with('bankAccount')
            ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                $q->where('store_name', 'like', "%{$search}%")
                  ->orWhere('merchant_code', 'like', "%{$search}%")
                  ->orWhere('owner_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->when($type, fn($q) => $q->where('type', $type))
            ->when($status === 'active', fn($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn($q) => $q->where('is_active', false))
            ->when($status === 'verified', fn($q) => $q->where('is_verified', true))
            ->when($status === 'unverified', fn($q) => $q->where('is_verified', false))
            ->orderBy($sortField, $sortDir)
            ->paginate(20)
            ->withQueryString();

        $isFragment = $request->ajax() || $request->boolean('fragment');
        if ($isFragment) {
            return view('admin.merchants.partials._table', compact('merchants', 'sortField', 'sortDir'))->render();
        }

        $stats = [
            'total' => Merchant::count(),
            'active' => Merchant::where('is_active', true)->count(),
            'inactive' => Merchant::where('is_active', false)->count(),
            'verified' => Merchant::where('is_verified', true)->count(),
            'pending_kyc' => Merchant::whereIn('kyc_status', ['pending', 'documents_required'])->count(),
            'physical' => Merchant::where('type', 'physical')->count(),
            'ecommerce' => Merchant::where('type', 'ecommerce')->count(),
            'both' => Merchant::where('type', 'both')->count(),
            'total_balance' => (float) Merchant::sum('balance'),
            'total_earned' => (float) Merchant::sum('total_earned'),
        ];

        return view('admin.merchants.index', compact(
            'merchants', 'stats', 'search', 'type', 'status', 'sortField', 'sortDir'
        ));
    }

    public function kpis(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'total'        => Merchant::count(),
            'active'       => Merchant::where('is_active', true)->count(),
            'inactive'     => Merchant::where('is_active', false)->count(),
            'verified'     => Merchant::where('is_verified', true)->count(),
            'pending_kyc'  => Merchant::whereIn('kyc_status', ['pending', 'documents_required'])->count(),
            'total_balance' => (float) Merchant::sum('balance'),
        ]);
    }

    public function create(): View
    {
        return view('admin.merchants.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_name' => 'required|string|max:255',
            'type' => 'required|in:physical,ecommerce,both',
            'owner_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'governorate' => 'nullable|string|max:100',
            'website_url' => 'nullable|url|max:500',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'environment' => 'nullable|in:sandbox,production',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] ??= true;
        $validated['environment'] ??= 'sandbox';

        // commission_rate is guarded (SEC-003); forceFill so this trusted admin write persists it.
        (new Merchant())->forceFill($validated)->save();

        return redirect()->route('admin.merchants.index')
            ->with('success', 'تم إضافة التاجر بنجاح');
    }

    public function show(Merchant $merchant): View
    {
        return view('admin.merchants.show', compact('merchant'));
    }

    public function dashboard(Merchant $merchant): View
    {
        // Real data only — derived from the merchant's linked user account.
        // (No fabricated rand() figures in a financial admin panel.)
        $user = $merchant->user;
        $monthCompleted = fn () => $user
            ? $user->transactions()
                ->where('status', TransactionStatus::COMPLETED->value)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
            : null;

        $stats = [
            'total_transactions' => $user ? $user->transactions()->count() : 0,
            'transactions_this_month' => $user
                ? $user->transactions()
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)->count()
                : 0,
            'earned_this_month' => $monthCompleted()
                ? (float) $monthCompleted()->sum(DB::raw('ABS(amount)'))
                : 0.0,
            'syp_rate' => (float) (ExchangeRate::where('from_currency', 'USD')
                ->where('to_currency', 'SYP')->value('rate') ?? 13000),
        ];

        $recentActivities = ($user ? $user->transactions()->latest()->take(5)->get() : collect())
            ->map(fn ($t) => [
                'type' => ((float) $t->amount) < 0 ? 'refund' : 'payment',
                'description' => $t->title ?: ('عملية #' . $t->reference),
                'amount' => abs((float) $t->amount),
                'date' => $t->created_at->format('Y/m/d H:i'),
            ]);

        $chartData = $this->sevenDayVolume($user);

        return view('admin.merchants.dashboard', compact('merchant', 'stats', 'recentActivities', 'chartData'));
    }

    /** Real completed-transaction volume for the last 7 days (Sun-keyed Arabic labels). */
    private function sevenDayVolume(?\App\Models\User $user): array
    {
        $ar = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
        $labels = [];
        $values = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $labels[] = $ar[$day->dayOfWeek];
            $values[] = $user
                ? (float) $user->transactions()
                    ->where('status', TransactionStatus::COMPLETED->value)
                    ->whereDate('created_at', $day->toDateString())
                    ->sum(DB::raw('ABS(amount)'))
                : 0.0;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    public function edit(Merchant $merchant): View
    {
        return view('admin.merchants.edit', compact('merchant'));
    }

    public function update(Request $request, Merchant $merchant): RedirectResponse
    {
        $validated = $request->validate([
            'store_name' => 'required|string|max:255',
            'type' => 'required|in:physical,ecommerce,both',
            'owner_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'governorate' => 'nullable|string|max:100',
            'website_url' => 'nullable|url|max:500',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'environment' => 'nullable|in:sandbox,production',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $merchant->forceFill($validated)->save();

        if ($request->boolean('is_verified') && !$merchant->verified_at) {
            $merchant->forceFill(['verified_at' => now()])->save();
        }

        // Notify the merchant operator on first approval via the update form.
        if ($request->boolean('is_verified')) {
            (new PartnerApprovalNotifier())->notifyMerchant($merchant->fresh());
        }

        return redirect()->route('admin.merchants.show', $merchant)
            ->with('success', 'تم تحديث التاجر بنجاح');
    }

    public function destroy(Merchant $merchant): RedirectResponse
    {
        $merchant->delete();

        return redirect()->route('admin.merchants.index')
            ->with('success', 'تم حذف التاجر بنجاح');
    }

    public function regenerateKeys(Merchant $merchant): RedirectResponse
    {
        $merchant->regenerateApiKey();

        return redirect()->route('admin.merchants.show', $merchant)
            ->with('success', 'تم تجديد مفاتيح API بنجاح');
    }

    public function toggleStatus(Request $request, Merchant $merchant): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
            'reason'    => ['nullable', 'string', 'max:500'],
        ]);

        $merchant->forceFill(['is_active' => $validated['is_active']])->save();

        return response()->json([
            'success' => true,
            'message' => $validated['is_active'] ? 'تم تفعيل التاجر' : 'تم إيقاف التاجر',
            'is_active' => $validated['is_active'],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = Merchant::query()->with('bankAccount');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('store_name', 'like', "%{$search}%")
                  ->orWhere('merchant_code', 'like', "%{$search}%")
                  ->orWhere('owner_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }
        if ($status = $request->get('status')) {
            match ($status) {
                'active' => $query->where('is_active', true),
                'inactive' => $query->where('is_active', false),
                'verified' => $query->where('is_verified', true),
                'unverified' => $query->where('is_verified', false),
                default => null,
            };
        }

        $sortField = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        $allowedSorts = ['store_name', 'merchant_code', 'type', 'is_active', 'balance', 'created_at'];
        if (!in_array($sortField, $allowedSorts)) $sortField = 'created_at';
        $sortDir = $sortDir === 'asc' ? 'asc' : 'desc';

        \App\Models\ActivityLog::log('merchants.export', null, null, null, null, 'Admin exported merchants CSV');

        $filename = 'merchants_' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'merchant_code', 'store_name', 'owner_name', 'type', 'email', 'phone',
                'is_active', 'is_verified', 'kyc_status',
                'balance', 'total_earned',
                'bank_name', 'bank_account_last4', 'created_at',
            ]);
            $query->chunk(500, function ($merchants) use ($handle): void {
                foreach ($merchants as $m) {
                    fputcsv($handle, [
                        $m->merchant_code,
                        $m->store_name,
                        $m->owner_name,
                        $m->type,
                        $m->email,
                        $m->phone,
                        $m->is_active ? '1' : '0',
                        $m->is_verified ? '1' : '0',
                        $m->kyc_status instanceof \App\Enums\KycStatus ? $m->kyc_status->value : $m->kyc_status,
                        number_format((float) $m->balance, 2, '.', ''),
                        number_format((float) $m->total_earned, 2, '.', ''),
                        $m->bankAccount?->bank_name,
                        $m->bankAccount?->account_number_last4,
                        $m->created_at?->toDateTimeString(),
                    ]);
                }
            });
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
