<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\TransactionStatus;
use App\Models\Agent;
use App\Services\PartnerApprovalNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AgentController extends Controller
{
    public function index(Request $request)
    {
        $query = Agent::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('owner_name', 'like', "%{$search}%")
                  ->orWhere('agent_code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('governorate', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            match ($status) {
                'active' => $query->where('is_active', true),
                'inactive' => $query->where('is_active', false),
                'featured' => $query->where('is_featured', true),
                default => null,
            };
        }

        if ($service = $request->get('service')) {
            $query->whereJsonContains('services', $service);
        }

        if ($city = $request->get('city')) {
            $query->where('city', $city);
        }

        $sortField = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        $allowedSorts = ['name', 'city', 'rating', 'is_active', 'created_at'];
        if (!in_array($sortField, $allowedSorts)) $sortField = 'created_at';
        $sortDir = $sortDir === 'asc' ? 'asc' : 'desc';

        $agents = $query->with('bankAccount')->orderBy($sortField, $sortDir)->paginate(20)->withQueryString();

        $isFragment = $request->ajax() || $request->boolean('fragment');
        if ($isFragment) {
            return view('admin.agents.partials._table', compact('agents', 'sortField', 'sortDir'))->render();
        }

        $cities = Agent::select('city')->distinct()->whereNotNull('city')->pluck('city')->sort()->values();

        $stats = [
            'total'       => Agent::count(),
            'active'      => Agent::where('is_active', true)->count(),
            'inactive'    => Agent::where('is_active', false)->count(),
            'featured'    => Agent::where('is_featured', true)->count(),
            'verified'    => Agent::where('is_verified', true)->count(),
            'pending_kyc' => Agent::where('kyc_status', 'pending')->count(),
        ];

        return view('admin.agents.index', compact('agents', 'cities', 'stats', 'search', 'status', 'service', 'city', 'sortField', 'sortDir'));
    }

    public function kpis(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'total'       => Agent::count(),
            'active'      => Agent::where('is_active', true)->count(),
            'inactive'    => Agent::where('is_active', false)->count(),
            'featured'    => Agent::where('is_featured', true)->count(),
            'verified'    => Agent::where('is_verified', true)->count(),
            'pending_kyc' => Agent::where('kyc_status', 'pending')->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.agents.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'owner_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'governorate' => 'nullable|string|max:100',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'services' => 'nullable|array',
            'services.*' => 'string|in:cash_in,cash_out',
            'working_hours' => 'nullable|string|max:255',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_verified' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        if (empty($request->agent_code)) {
            do {
                $code = 'AG-' . str_pad((string) random_int(1000, 999999), 4, '0', STR_PAD_LEFT);
            } while (Agent::where('agent_code', $code)->exists());
            $validated['agent_code'] = $code;
        }

        $validated['is_active'] ??= true;
        $validated['is_featured'] ??= false;
        $validated['is_verified'] ??= true;
        $validated['rating'] ??= 5.0;

        // commission_rate/min_amount/max_amount/rating/reviews_count/is_featured/is_verified
        // are guarded (SEC-003); forceFill so this trusted admin write persists them.
        (new Agent())->forceFill($validated)->save();

        return redirect()->route('admin.agents.index')->with('success', 'تم إضافة الوكيل بنجاح');
    }

    public function show(Agent $agent): View
    {
        return view('admin.agents.show', compact('agent'));
    }

    public function dashboard(Agent $agent): View
    {
        // Real data only — derived from the agent's linked user account.
        $user = $agent->user;
        $completed = fn () => $user
            ? $user->transactions()->where('status', TransactionStatus::COMPLETED->value)
            : null;

        $cashFlow = $completed() ? (float) $completed()->sum(DB::raw('ABS(amount)')) : 0.0;

        $stats = [
            'total_transactions' => $user ? $user->transactions()->count() : 0,
            'total_cash_flow' => $cashFlow,
            // Commission derived from real volume × the agent's real rate (not tracked
            // as its own ledger entry yet, so this is a rate-based estimate).
            'total_commission' => round($cashFlow * ((float) $agent->commission_rate) / 100, 2),
        ];

        $recentActivities = ($user ? $user->transactions()->latest()->take(5)->get() : collect())
            ->map(fn ($t) => [
                'type' => ((float) $t->amount) < 0 ? 'cash_out' : 'cash_in',
                'user_name' => $agent->owner_name ?: ($user->name ?? 'مستخدم'),
                'amount' => abs((float) $t->amount),
                'date' => $t->created_at->format('Y/m/d H:i'),
            ]);

        $chartData = $this->sevenDayVolume($user);

        return view('admin.agents.dashboard', compact('agent', 'stats', 'recentActivities', 'chartData'));
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

    public function edit(Agent $agent): View
    {
        return view('admin.agents.edit', compact('agent'));
    }

    public function update(Request $request, Agent $agent): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'owner_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'governorate' => 'nullable|string|max:100',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'services' => 'nullable|array',
            'services.*' => 'string|in:cash_in,cash_out',
            'working_hours' => 'nullable|string|max:255',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
            'rating' => 'nullable|numeric|min:0|max:5',
            'reviews_count' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_verified' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['is_active'] ??= false;
        $validated['is_featured'] ??= false;
        $validated['is_verified'] ??= false;

        $agent->forceFill($validated)->save();

        // Notify the agent operator on first approval via the update form.
        if ($request->boolean('is_verified')) {
            (new PartnerApprovalNotifier())->notifyAgent($agent->fresh());
        }

        return redirect()->route('admin.agents.index')->with('success', 'تم تحديث الوكيل بنجاح');
    }

    public function destroy(Agent $agent): RedirectResponse
    {
        $agent->delete();
        return redirect()->route('admin.agents.index')->with('success', 'تم حذف الوكيل بنجاح');
    }

    public function toggleStatus(Request $request, Agent $agent): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
            'reason'    => ['nullable', 'string', 'max:500'],
        ]);

        $agent->forceFill(['is_active' => $validated['is_active']])->save();

        return response()->json([
            'success' => true,
            'message' => $validated['is_active'] ? 'تم تفعيل الوكيل' : 'تم إيقاف الوكيل',
            'is_active' => $validated['is_active'],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = Agent::query()->with('bankAccount');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('owner_name', 'like', "%{$search}%")
                  ->orWhere('agent_code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('governorate', 'like', "%{$search}%");
            });
        }
        if ($status = $request->get('status')) {
            match ($status) {
                'active' => $query->where('is_active', true),
                'inactive' => $query->where('is_active', false),
                'featured' => $query->where('is_featured', true),
                default => null,
            };
        }
        if ($service = $request->get('service')) {
            $query->whereJsonContains('services', $service);
        }
        if ($city = $request->get('city')) {
            $query->where('city', $city);
        }

        $sortField = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        $allowedSorts = ['name', 'city', 'rating', 'is_active', 'created_at'];
        if (!in_array($sortField, $allowedSorts)) $sortField = 'created_at';
        $sortDir = $sortDir === 'asc' ? 'asc' : 'desc';

        \App\Models\ActivityLog::log('agents.export', null, null, null, null, 'Admin exported agents CSV');

        $filename = 'agents_' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'agent_code', 'name', 'owner_name', 'phone', 'city', 'governorate',
                'services', 'rating', 'is_active', 'is_featured', 'kyc_status',
                'bank_name', 'bank_account_last4', 'created_at',
            ]);
            $query->chunk(500, function ($agents) use ($handle): void {
                foreach ($agents as $a) {
                    $services = is_array($a->services) ? implode(', ', $a->services) : $a->services;
                    fputcsv($handle, [
                        $a->agent_code,
                        $a->name,
                        $a->owner_name,
                        $a->phone,
                        $a->city,
                        $a->governorate,
                        $services,
                        $a->rating,
                        $a->is_active ? '1' : '0',
                        $a->is_featured ? '1' : '0',
                        $a->kyc_status instanceof \App\Enums\KycStatus ? $a->kyc_status->value : $a->kyc_status,
                        $a->bankAccount?->bank_name,
                        $a->bankAccount?->account_number_last4,
                        $a->created_at?->toDateTimeString(),
                    ]);
                }
            });
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
