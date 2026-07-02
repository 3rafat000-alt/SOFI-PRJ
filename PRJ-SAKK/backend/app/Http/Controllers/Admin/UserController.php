<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\KycStatus;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\KycDocument;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    /** Columns the frontend may sort on — prevents arbitrary ORDER BY injection. */
    private const SORT_ALLOWLIST = ['last_name', 'email', 'kyc_level', 'status', 'last_login_at'];

    // ==================== Read actions ====================

    /**
     * Users index — full page or table-only fragment for AJAX debounced search.
     *
     * Fragment trigger: `$request->ajax()` OR `X-Requested-With: XMLHttpRequest`
     * header OR explicit `?fragment=1` query parameter.
     *
     * Fragment response: renders `admin.users.partials._table` with `$users`.
     * Full response: renders `admin.users.index` (which includes the same partial).
     * Paginator always carries query string so AJAX pagination keeps filters.
     */
    public function index(Request $request)
    {
        $query = User::with('wallets')->withCount('transactions')->where('is_admin', false);

        // --- Search ---
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search): void {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // --- Filters ---
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('kyc_level')) {
            $query->where('kyc_level', $request->kyc_level);
        }

        if ($request->filled('kyc_status')) {
            $query->where('kyc_status', $request->kyc_status);
        }

        if ($request->filled('two_fa')) {
            $query->where('two_factor_enabled', (bool) $request->two_fa);
        }

        if ($request->filled('aml_flagged') && $request->aml_flagged === '1') {
            $query->whereExists(function ($sub): void {
                $sub->selectRaw('1')
                    ->from('aml_flags')
                    ->whereColumn('aml_flags.user_id', 'users.id')
                    ->where('aml_flags.status', 'pending');
            });
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        // --- Sort ---
        $sortCol = in_array($request->get('sort'), self::SORT_ALLOWLIST, true)
            ? $request->get('sort')
            : 'created_at';
        $sortDir = strtolower((string) $request->get('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortCol, $sortDir);

        $users = $query->paginate(20)->withQueryString();

        // AJAX / fragment mode — return only the table partial for debounced search.
        $isFragment = $request->ajax() || $request->boolean('fragment');
        if ($isFragment) {
            return view('admin.users.partials._table', compact('users'))->render();
        }

        return view('admin.users.index', compact('users'));
    }

    /**
     * KPI aggregates JSON — cached 30 s to avoid N+1 on every strip load.
     */
    public function kpis(Request $request): JsonResponse
    {
        $data = Cache::remember('admin.users.kpis', 30, function (): array {
            return [
        'total'             => User::where('is_admin', false)->count(),
            'active'            => User::where('is_admin', false)->where('status', 'active')->count(),
            'pending_kyc'       => User::where('is_admin', false)->where('kyc_status', 'submitted')->count(),
            'suspended'         => User::where('is_admin', false)->where('status', 'suspended')->count(),
                'total_usd_balance' => (float) Wallet::where('currency', 'USD')->sum('balance'),
                'total_syp_balance' => (float) Wallet::where('currency', 'SYP')->sum('balance'),
            ];
        });

        return response()->json($data);
    }

    /**
     * Server-side CSV export — same filter logic as index(), never paginated.
     * Streams in 500-row chunks. Writes one ActivityLog entry.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = User::with('wallets')->where('is_admin', false);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search): void {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('kyc_level')) {
            $query->where('kyc_level', $request->kyc_level);
        }
        if ($request->filled('kyc_status')) {
            $query->where('kyc_status', $request->kyc_status);
        }
        if ($request->filled('two_fa')) {
            $query->where('two_factor_enabled', (bool) $request->two_fa);
        }
        if ($request->filled('aml_flagged') && $request->aml_flagged === '1') {
            $query->whereExists(function ($sub): void {
                $sub->selectRaw('1')
                    ->from('aml_flags')
                    ->whereColumn('aml_flags.user_id', 'users.id')
                    ->where('aml_flags.status', 'pending');
            });
        }
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        ActivityLog::log('users.export', null, null, null, null, 'Admin exported users CSV');

        $filename = 'users_' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'uuid', 'full_name', 'email', 'phone',
                'status', 'kyc_level', 'kyc_status',
                'two_factor_enabled', 'usd_balance', 'created_at',
            ]);

            $query->chunk(500, function ($users) use ($handle): void {
                foreach ($users as $u) {
                    $usdBalance = $u->wallets->where('currency', 'USD')->sum('balance');

                    fputcsv($handle, [
                        $u->uuid,
                        $u->first_name . ' ' . $u->last_name,
                        $u->email,
                        $u->phone,
                        $u->status instanceof UserStatus ? $u->status->value : $u->status,
                        $u->kyc_level,
                        $u->kyc_status instanceof KycStatus ? $u->kyc_status->value : $u->kyc_status,
                        $u->two_factor_enabled ? '1' : '0',
                        number_format((float) $usdBalance, 2, '.', ''),
                        $u->created_at?->toDateTimeString(),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * User detail page — eager loads all tab relations + passes computed tx KPIs.
     */
    public function show(User $user)
    {
        $user->load([
            'wallets',
            'cards',
            'transactions' => fn($q) => $q->latest()->take(10),
            'kycDocuments',
            'devices',
            'activityLogs' => fn($q) => $q->latest()->take(30),
            'amlFlags'     => fn($q) => $q->latest()->take(20),
            'referrer',
            'referrals',
        ])->loadCount('transactions')->loadSum('transactions', 'amount');

        $txCount  = $user->transactions_count;
        $txVolume = (float) $user->transactions_sum_amount;

        return view('admin.users.show', compact('user', 'txCount', 'txVolume'));
    }

    /**
     * Quick-view JSON for the index slide-over panel.
     */
    public function quickView(User $user): JsonResponse
    {
        $user->load(['wallets', 'transactions' => fn($q) => $q->latest()->take(3)]);

        $amlOpenCount = $user->amlFlags()->where('status', 'pending')->count();
        $devicesCount = $user->devices()->count();

        return response()->json([
            'user' => [
                'uuid'               => $user->uuid,
                'full_name'          => $user->full_name,
                'email'              => $user->email,
                'phone'              => $user->phone,
                'avatar'             => $user->avatar,
                'status'             => $user->status instanceof UserStatus ? $user->status->value : $user->status,
                'kyc_level'          => $user->kyc_level,
                'kyc_status'         => $user->kyc_status instanceof KycStatus ? $user->kyc_status->value : $user->kyc_status,
                'two_factor_enabled' => $user->two_factor_enabled,
            ],
            'wallets'        => $user->wallets->map(fn($w) => [
                'currency' => $w->currency,
                'balance'  => (float) $w->balance,
            ]),
            'recent_txs'     => $user->transactions->map(fn($t) => [
                'reference'  => $t->reference,
                'amount'     => (float) $t->amount,
                'currency'   => $t->currency,
                'status'     => $t->status instanceof \App\Enums\TransactionStatus ? $t->status->value : $t->status,
                'created_at' => $t->created_at?->toDateTimeString(),
            ]),
            'aml_open_count' => $amlOpenCount,
            'devices_count'  => $devicesCount,
        ]);
    }

    // ==================== Mutating actions ====================

    /**
     * Audited status change (active / suspended only).
     * Uses forceFill to bypass SEC-003 guard on User.status.
     * Returns JSON — called via Alpine fetch from the status modal.
     */
    public function updateStatus(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:active,suspended'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $oldStatus = $user->status instanceof UserStatus ? $user->status->value : $user->status;

        $user->forceFill(['status' => $validated['status']])->save();

        ActivityLog::log(
            'users.status_changed',
            $user,
            $user,
            ['status' => $oldStatus],
            ['status' => $validated['status']],
            $validated['reason']
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة الحساب بنجاح',
            'status'  => $validated['status'],
        ]);
    }

    /**
     * Approve a KYC document — verifies doc ownership then sets approved.
     * Writes ActivityLog. Returns JSON.
     */
    public function approveKycDoc(Request $request, User $user, KycDocument $doc): JsonResponse
    {
        if ((int) $doc->user_id !== (int) $user->id) {
            abort(403, 'Document does not belong to this user.');
        }

        $oldStatus = $doc->status instanceof VerificationStatus ? $doc->status->value : $doc->status;

        if ($oldStatus === 'rejected') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن قبول وثيقة مرفوضة مسبقاً — يجب على المستخدم إعادة رفع الوثيقة',
            ], 422);
        }

        $doc->update([
            'status'      => 'approved',
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        ActivityLog::log(
            'users.kyc_doc_approved',
            $user,
            $doc,
            ['status' => $oldStatus],
            ['status' => 'approved', 'verified_by' => auth()->id()],
            'Admin approved KYC document'
        );

        return response()->json([
            'success' => true,
            'message' => 'تم قبول الوثيقة بنجاح',
        ]);
    }

    /**
     * Reject a KYC document — reason required.
     * Verifies doc ownership. Writes ActivityLog. Returns JSON.
     */
    public function rejectKycDoc(Request $request, User $user, KycDocument $doc): JsonResponse
    {
        if ((int) $doc->user_id !== (int) $user->id) {
            abort(403, 'Document does not belong to this user.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $oldStatus = $doc->status instanceof VerificationStatus ? $doc->status->value : $doc->status;

        $doc->update([
            'status'           => 'rejected',
            'rejection_reason' => $validated['reason'],
            'verified_by'      => auth()->id(),
            'verified_at'      => now(),
        ]);

        ActivityLog::log(
            'users.kyc_doc_rejected',
            $user,
            $doc,
            ['status' => $oldStatus],
            ['status' => 'rejected', 'rejection_reason' => $validated['reason']],
            $validated['reason']
        );

        return response()->json([
            'success' => true,
            'message' => 'تم رفض الوثيقة',
        ]);
    }

    /**
     * Bulk activate or suspend a list of users (by UUID).
     * Writes one ActivityLog per user. Returns JSON.
     */
    public function bulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action'     => ['required', 'string', 'in:activate,suspend'],
            'user_ids'   => ['required', 'array', 'min:1'],
            'user_ids.*' => ['required', 'string', 'uuid'],
            'reason'     => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $newStatus = $validated['action'] === 'activate' ? 'active' : 'suspended';
        $processed = 0;
        $failed    = [];

        $users = User::whereIn('uuid', $validated['user_ids'])->get();

        foreach ($users as $user) {
            try {
                $oldStatus = $user->status instanceof UserStatus ? $user->status->value : $user->status;
                $user->forceFill(['status' => $newStatus])->save();

                ActivityLog::log(
                    'users.bulk_' . $validated['action'],
                    $user,
                    $user,
                    ['status' => $oldStatus],
                    ['status' => $newStatus],
                    $validated['reason']
                );

                $processed++;
            } catch (\Throwable) {
                $failed[] = $user->uuid;
            }
        }

        return response()->json([
            'success'   => true,
            'processed' => $processed,
            'failed'    => $failed,
        ]);
    }

    /**
     * Quick suspend — legacy route kept for existing Blade links.
     * Writes ActivityLog.
     */
    public function suspend(User $user): RedirectResponse
    {
        $user->forceFill(['status' => 'suspended'])->save();

        ActivityLog::log(
            'users.suspend',
            $user,
            $user,
            ['status' => 'active'],
            ['status' => 'suspended'],
            'Admin suspended user via quick action'
        );

        return back()->with('success', 'تم تعليق حساب المستخدم');
    }

    /**
     * Quick activate — legacy route kept for existing Blade links.
     * Writes ActivityLog.
     */
    public function activate(User $user): RedirectResponse
    {
        $user->forceFill(['status' => 'active'])->save();

        ActivityLog::log(
            'users.activate',
            $user,
            $user,
            ['status' => 'suspended'],
            ['status' => 'active'],
            'Admin activated user via quick action'
        );

        return back()->with('success', 'تم تفعيل حساب المستخدم');
    }
}
