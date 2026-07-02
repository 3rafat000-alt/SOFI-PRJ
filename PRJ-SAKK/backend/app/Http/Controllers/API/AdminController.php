<?php

namespace App\Http\Controllers\API;

use App\Enums\KycStatus;
use App\Enums\TransactionStatus;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExportCsvRequest;
use App\Http\Requests\Admin\KycReviewRequest;
use App\Http\Requests\Admin\TransactionsIndexRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Admin\UsersIndexRequest;
use App\Models\ActivityLog;
use App\Models\AdminNotification;
use App\Models\CardInventory;
use App\Models\CardPricing;
use App\Models\ExchangeRate;
use App\Models\Fee;
use App\Models\KycLevel;
use App\Models\KycVerification;
use App\Models\SystemSetting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\VirtualCard;
use App\Models\Wallet;
use App\Services\CardService;
use App\Services\KycService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    public function __construct() {}

    /**
     * Backward-compat — delegate user methods to AdminUserController.
     */
    protected function userController(): AdminUserController
    {
        return app(AdminUserController::class);
    }

    public function users(UsersIndexRequest $request): JsonResponse
    {
        return $this->userController()->index($request);
    }

    public function userDetail(int $id): JsonResponse
    {
        return $this->userController()->show($id);
    }

    public function updateUser(UpdateUserRequest $request, int $id): JsonResponse
    {
        return $this->userController()->update($request, $id);
    }

    public function deleteUser(int $id): JsonResponse
    {
        return $this->userController()->destroy($id);
    }

    public function kycDocuments(Request $request): JsonResponse
    {
        return $this->userController()->kycDocuments($request);
    }

    public function approveKyc(int $userId): JsonResponse
    {
        return $this->userController()->approveKyc($userId);
    }

    public function rejectKyc(Request $request, int $userId): JsonResponse
    {
        return $this->userController()->rejectKyc($request, $userId);
    }

    // ============================================
    // Dashboard Stats
    // ============================================

    /**
     * Get admin dashboard statistics.
     */
    public function dashboard(): JsonResponse
    {
        $now = now();
        $todayStart = $now->copy()->startOfDay();
        $monthStart = $now->copy()->startOfMonth();

        $totalUsers = User::count();
        $totalWallets = Wallet::count();
        $totalTransactions = Transaction::count();
        $totalCards = VirtualCard::count();
        $pendingKyc = User::where('kyc_status', KycStatus::PENDING)->count();
        $suspendedUsers = User::where('status', UserStatus::SUSPENDED)->count();

        $todayVolume = Transaction::where('created_at', '>=', $todayStart)
            ->where('status', TransactionStatus::COMPLETED)
            ->where('amount', '>', 0)
            ->sum('amount');

        $monthVolume = Transaction::where('created_at', '>=', $monthStart)
            ->where('status', TransactionStatus::COMPLETED)
            ->where('amount', '>', 0)
            ->sum('amount');

        $totalVolume = Transaction::where('status', TransactionStatus::COMPLETED)
            ->where('amount', '>', 0)
            ->sum('amount');

        $totalFeesCollected = Transaction::sum('fee');

        $recentUsers = User::latest()->take(10)->get()->map(fn ($u) => [
            'id' => $u->id,
            'full_name' => $u->full_name,
            'email' => $u->email,
            'is_admin' => $u->is_admin,
            'status' => $u->status,
            'kyc_status' => $u->kyc_status,
            'created_at' => $u->created_at,
        ]);

        $recentTransactions = Transaction::with('user')
            ->latest()
            ->take(10)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'reference' => $t->reference,
                'user_name' => $t->user?->full_name,
                'type' => $t->type,
                'amount' => $t->amount,
                'currency' => $t->currency,
                'status' => $t->status,
                'created_at' => $t->created_at,
            ]);

        $transactionsByDay = Transaction::where('created_at', '>=', $now->copy()->subDays(30))
            ->where('status', TransactionStatus::COMPLETED)
            ->selectRaw("DATE(created_at) as date")
            ->selectRaw("SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as volume")
            ->selectRaw("COUNT(*) as count")
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $usersByDay = User::where('created_at', '>=', $now->copy()->subDays(30))
            ->selectRaw("DATE(created_at) as date")
            ->selectRaw("COUNT(*) as count")
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topCurrencies = Wallet::select('currency')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(balance) as total_balance')
            ->groupBy('currency')
            ->orderByDesc('total_balance')
            ->get();

        $kycBreakdown = [
            'verified' => User::where('kyc_status', KycStatus::VERIFIED)->count(),
            'pending' => $pendingKyc,
            'submitted' => User::where('kyc_status', KycStatus::SUBMITTED)->count(),
            'rejected' => User::where('kyc_status', KycStatus::REJECTED)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'total_users' => $totalUsers,
                    'total_wallets' => $totalWallets,
                    'total_transactions' => $totalTransactions,
                    'total_cards' => $totalCards,
                    'pending_kyc' => $pendingKyc,
                    'suspended_users' => $suspendedUsers,
                ],
                'volume' => [
                    'today' => round((float) $todayVolume, 2),
                    'this_month' => round((float) $monthVolume, 2),
                    'all_time' => round((float) $totalVolume, 2),
                    'fees_collected' => round((float) $totalFeesCollected, 2),
                ],
                'charts' => [
                    'transactions_by_day' => $transactionsByDay,
                    'users_by_day' => $usersByDay,
                ],
                'top_currencies' => $topCurrencies,
                'kyc_breakdown' => $kycBreakdown,
                'recent_users' => $recentUsers,
                'recent_transactions' => $recentTransactions,
            ],
        ]);
    }

    // ============================================
    // Transactions Management
    // ============================================

    /**
     * List transactions with filtering.
     */
    public function transactions(TransactionsIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $query = Transaction::with('user');

        $this->applyTransactionFilters($query, $validated);

        $sortField = $validated['sort_by'] ?? 'created_at';
        $sortDir = $validated['sort_dir'] ?? 'desc';
        $query->orderBy($sortField, $sortDir === 'asc' ? 'asc' : 'desc');

        $perPage = min((int) ($validated['per_page'] ?? 20), 100);

        return response()->json([
            'success' => true,
            'data' => $query->paginate($perPage)->through(fn ($t) => [
                'id' => $t->id,
                'uuid' => $t->uuid,
                'reference' => $t->reference,
                'user_id' => $t->user_id,
                'user_name' => $t->user?->full_name,
                'type' => $t->type,
                'category' => $t->category,
                'currency' => $t->currency,
                'amount' => $t->amount,
                'fee' => $t->fee,
                'net_amount' => $t->net_amount,
                'balance_before' => $t->balance_before,
                'balance_after' => $t->balance_after,
                'status' => $t->status,
                'title' => $t->title,
                'description' => $t->description,
                'wallet_id' => $t->wallet_id,
                'card_id' => $t->card_id,
                'recipient_id' => $t->recipient_id,
                'metadata' => $t->metadata,
                'failure_reason' => $t->failure_reason,
                'completed_at' => $t->completed_at,
                'created_at' => $t->created_at,
            ]),
        ]);
    }

    /**
     * Apply filters to transaction query.
     */
    protected function applyTransactionFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['currency'])) {
            $query->where('currency', $filters['currency']);
        }

        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function (Builder $q) use ($term) {
                $q->where('reference', 'like', $term)
                    ->orWhere('title', 'like', $term);
            });
        }

        if (!empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }
    }

    /**
     * Get transaction details.
     */
    public function transactionDetail(int $id): JsonResponse
    {
        $transaction = Transaction::with(['user', 'wallet', 'card', 'recipient'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $transaction,
        ]);
    }

    /**
     * Reverse a completed transaction.
     */
    public function reverseTransaction(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $transaction = Transaction::findOrFail($id);

        if ($transaction->status === TransactionStatus::REVERSED) {
            return response()->json([
                'success' => false,
                'message' => 'المعاملة معكوسة مسبقاً',
            ], 422);
        }

        if ($transaction->status !== TransactionStatus::COMPLETED) {
            return response()->json([
                'success' => false,
                'message' => 'يمكن عكس المعاملات المكتملة فقط',
            ], 422);
        }

        $admin = auth('sanctum')->user();
        if ($admin && ! $admin->can('reverse', $transaction)) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بعكس المعاملات',
            ], 403);
        }

        try {
            $reversal = DB::transaction(function () use ($transaction, $validated) {
                $reversal = $transaction->reverse();

                if (! $reversal) {
                    throw new \RuntimeException('Transaction reversal failed');
                }

                // Update the reversal with the reason
                $reversal->update([
                    'metadata' => array_merge(
                        $reversal->metadata ?? [],
                        ['reversal_reason' => $validated['reason'], 'reversed_by' => auth('sanctum')->id()]
                    ),
                ]);

                return $reversal;
            });

            return response()->json([
                'success' => true,
                'message' => 'تم عكس المعاملة بنجاح',
                'data' => $reversal,
            ]);
        } catch (\Throwable $e) {
            Log::error('Transaction reversal failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل عكس المعاملة: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // Wallets Management
    // ============================================

    /**
     * List wallets with filtering.
     */
    public function wallets(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'currency' => ['nullable', 'string', 'max:3'],
            'is_frozen' => ['nullable', 'boolean'],
            'sort_by' => ['nullable', 'string', 'in:balance,created_at'],
            'sort_dir' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Wallet::with('user');

        if (!empty($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }

        if (!empty($validated['currency'])) {
            $query->where('currency', strtoupper($validated['currency']));
        }

        if (isset($validated['is_frozen']) && $validated['is_frozen'] !== null) {
            $query->where('is_frozen', filter_var($validated['is_frozen'], FILTER_VALIDATE_BOOLEAN));
        }

        $sortField = $validated['sort_by'] ?? 'balance';
        $sortDir = $validated['sort_dir'] ?? 'desc';
        $query->orderBy($sortField, $sortDir === 'asc' ? 'asc' : 'desc');

        $perPage = min((int) ($validated['per_page'] ?? 20), 100);

        return response()->json([
            'success' => true,
            'data' => $query->paginate($perPage)->through(fn ($w) => [
                'id' => $w->id,
                'uuid' => $w->uuid,
                'user_id' => $w->user_id,
                'user_name' => $w->user?->full_name,
                'currency' => $w->currency,
                'balance' => $w->balance,
                'available_balance' => $w->available_balance,
                'pending_balance' => $w->pending_balance,
                'is_active' => $w->is_active,
                'is_default' => $w->is_default,
                'is_frozen' => $w->is_frozen,
                'frozen_reason' => $w->frozen_reason,
                'daily_limit' => $w->daily_limit,
                'monthly_limit' => $w->monthly_limit,
                'total_deposits' => $w->total_deposits,
                'total_withdrawals' => $w->total_withdrawals,
                'transaction_count' => $w->transaction_count,
                'created_at' => $w->created_at,
            ]),
        ]);
    }

    /**
     * Freeze a wallet.
     */
    public function freezeWallet(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $wallet = Wallet::findOrFail($id);
        $wallet->update(['is_frozen' => true, 'frozen_reason' => $validated['reason']]);

        return response()->json([
            'success' => true,
            'message' => 'تم تجميد المحفظة',
        ]);
    }

    /**
     * Unfreeze a wallet.
     */
    public function unfreezeWallet(int $id): JsonResponse
    {
        $wallet = Wallet::findOrFail($id);
        $wallet->update(['is_frozen' => false, 'frozen_reason' => null]);

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء تجميد المحفظة',
        ]);
    }

    // ============================================
    // Cards Management
    // ============================================

    /**
     * List cards with filtering.
     */
    public function cards(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', 'string'],
            'brand' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = VirtualCard::with('user', 'wallet');

        if (!empty($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['brand'])) {
            $query->where('brand', $validated['brand']);
        }

        $perPage = min((int) ($validated['per_page'] ?? 20), 100);

        return response()->json([
            'success' => true,
            'data' => $query->latest()->paginate($perPage)->through(fn ($c) => [
                'id' => $c->id,
                'uuid' => $c->uuid,
                'user_id' => $c->user_id,
                'user_name' => $c->user?->full_name,
                'wallet_id' => $c->wallet_id,
                'card_number_masked' => $c->card_number_masked,
                'cardholder_name' => $c->cardholder_name,
                'brand' => $c->brand,
                'balance' => $c->balance,
                'status' => $c->status,
                'is_active' => $c->is_active,
                'is_expired' => $c->is_expired,
                'frozen_reason' => $c->frozen_reason,
                'expires_at' => $c->expires_at,
                'created_at' => $c->created_at,
            ]),
        ]);
    }

    /**
     * Freeze a card.
     */
    public function freezeCard(int $id): JsonResponse
    {
        $card = VirtualCard::findOrFail($id);
        $card->freeze('Admin action');

        return response()->json([
            'success' => true,
            'message' => 'تم تجميد البطاقة',
        ]);
    }

    /**
     * Unfreeze a card.
     */
    public function unfreezeCard(int $id): JsonResponse
    {
        $card = VirtualCard::findOrFail($id);
        $card->unfreeze();

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء تجميد البطاقة',
        ]);
    }

    /**
     * Cancel a card.
     */
    public function cancelCard(int $id): JsonResponse
    {
        $card = VirtualCard::findOrFail($id);
        $card->cancel();

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء البطاقة',
        ]);
    }

    /**
     * Update card limits.
     */
    public function updateCardLimits(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'daily_limit' => ['sometimes', 'numeric', 'min:0'],
            'monthly_limit' => ['sometimes', 'numeric', 'min:0'],
            'per_transaction_limit' => ['sometimes', 'numeric', 'min:0'],
            'spending_limit' => ['sometimes', 'numeric', 'min:0'],
        ]);

        $card = VirtualCard::findOrFail($id);
        $card->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حدود البطاقة',
            'data' => $card->fresh(),
        ]);
    }

    // ============================================
    // System Settings
    // ============================================

    /**
     * Get basic system settings.
     */
    public function systemSettings(): JsonResponse
    {
        $settings = [
            'maintenance_mode' => app()->isDownForMaintenance(),
            'registration_open' => config('app.allow_registration', true),
            'min_deposit' => config('app.min_deposit', 1),
            'max_deposit' => config('app.max_deposit', 100000),
            'min_withdrawal' => config('app.min_withdrawal', 1),
            'max_withdrawal' => config('app.max_withdrawal', 50000),
            'withdrawal_fee_percent' => config('app.withdrawal_fee_percent', 1),
            'default_currency' => config('app.currency', 'USD'),
            'supported_currencies' => ['USD', 'SYP'],
        ];

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Get all settings grouped.
     */
    public function getAllSettings(): JsonResponse
    {
        $settings = SystemSetting::all()->groupBy('group');

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Get settings by group.
     */
    public function getSettingsByGroup(string $group): JsonResponse
    {
        $settings = SystemSetting::where('group', $group)->get();

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Update a setting.
     */
    public function updateSetting(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string'],
            'value' => ['required'],
            'type' => ['sometimes', 'string', 'in:string,integer,decimal,boolean,json'],
            'group' => ['sometimes', 'string'],
            'label' => ['sometimes', 'string'],
            'description' => ['sometimes', 'string'],
        ]);

        $setting = SystemSetting::updateOrCreate(
            ['key' => $validated['key']],
            $validated
        );

        ActivityLog::log('setting.updated', null, $setting, null, $validated, "Updated setting: {$validated['key']}");
        Cache::forget("setting:{$validated['key']}");

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الإعداد',
            'data' => $setting,
        ]);
    }

    /**
     * Delete a setting.
     */
    public function deleteSetting(string $key): JsonResponse
    {
        $setting = SystemSetting::where('key', $key)->first();

        if ($setting) {
            ActivityLog::log('setting.deleted', null, $setting, $setting->toArray(), null, "Deleted setting: {$key}");
            $setting->delete();
            Cache::forget("setting:{$key}");
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الإعداد',
        ]);
    }

    // ============================================
    // Fees & Limits
    // ============================================

    /**
     * Get platform fees.
     */
    public function getFees(): JsonResponse
    {
        $fees = SystemSetting::getByGroup('fees');

        $defaults = [
            'fee_withdrawal_percent' => 1.0,
            'fee_withdrawal_min' => 1.00,
            'fee_card_creation' => 5.00,
            'fee_card_monthly' => 2.00,
        ];

        foreach ($defaults as $key => $value) {
            if (!isset($fees[$key])) {
                $fees[$key] = $value;
            }
        }

        return response()->json([
            'success' => true,
            'data' => $fees,
        ]);
    }

    /**
     * Update platform fees.
     */
    public function updateFees(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fee_withdrawal_percent' => ['sometimes', 'numeric', 'min:0', 'max:10'],
            'fee_withdrawal_min' => ['sometimes', 'numeric', 'min:0'],
            'fee_card_creation' => ['sometimes', 'numeric', 'min:0'],
            'fee_card_monthly' => ['sometimes', 'numeric', 'min:0'],
        ]);

        foreach ($validated as $key => $value) {
            SystemSetting::set($key, $value, 'decimal');
            SystemSetting::where('key', $key)->update(['group' => 'fees']);
        }

        ActivityLog::log('fees.updated', null, null, null, $validated, 'Updated platform fees');

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الرسوم',
            'data' => $validated,
        ]);
    }

    /**
     * Get platform limits.
     */
    public function getLimits(): JsonResponse
    {
        $limits = SystemSetting::getByGroup('limits');

        $defaults = [
            'limit_daily_withdrawal' => 5000,
            'limit_monthly_withdrawal' => 50000,
            'limit_card_daily' => 5000,
            'limit_card_monthly' => 20000,
        ];

        foreach ($defaults as $key => $value) {
            if (!isset($limits[$key])) {
                $limits[$key] = $value;
            }
        }

        return response()->json([
            'success' => true,
            'data' => $limits,
        ]);
    }

    /**
     * Update platform limits.
     */
    public function updateLimits(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit_daily_withdrawal' => ['sometimes', 'numeric', 'min:0'],
            'limit_monthly_withdrawal' => ['sometimes', 'numeric', 'min:0'],
            'limit_card_daily' => ['sometimes', 'numeric', 'min:0'],
            'limit_card_monthly' => ['sometimes', 'numeric', 'min:0'],
        ]);

        foreach ($validated as $key => $value) {
            SystemSetting::set($key, $value, 'decimal');
            SystemSetting::where('key', $key)->update(['group' => 'limits']);
        }

        ActivityLog::log('limits.updated', null, null, null, $validated, 'Updated platform limits');

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الحدود',
            'data' => $validated,
        ]);
    }

    // ============================================
    // Activity Logs
    // ============================================

    /**
     * List activity logs.
     */
    public function activityLogs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['nullable', 'string', 'max:255'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'admin_id' => ['nullable', 'integer', 'exists:users,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = ActivityLog::with(['user', 'admin']);

        if (!empty($validated['action'])) {
            $query->where('action', 'like', '%' . $validated['action'] . '%');
        }

        if (!empty($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }

        if (!empty($validated['admin_id'])) {
            $query->where('admin_id', $validated['admin_id']);
        }

        if (!empty($validated['from'])) {
            $query->whereDate('created_at', '>=', $validated['from']);
        }

        if (!empty($validated['to'])) {
            $query->whereDate('created_at', '<=', $validated['to']);
        }

        $perPage = min((int) ($validated['per_page'] ?? 20), 100);
        $logs = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    // ============================================
    // Push Notifications
    // ============================================

    /**
     * List admin notifications.
     */
    public function getNotifications(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = min((int) ($validated['per_page'] ?? 15), 100);

        $notifications = AdminNotification::with('admin')
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    /**
     * Send a push notification.
     */
    public function sendNotification(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:1000'],
            'type' => ['required', 'string', 'in:all,kyc_verified,active,inactive,specific'],
            'user_ids' => ['required_if:type,specific', 'nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'scheduled_at' => ['sometimes', 'date', 'after:now'],
        ]);

        $notification = AdminNotification::create([
            'admin_id' => auth()->id(),
            'title' => $validated['title'],
            'body' => $validated['body'],
            'type' => $validated['type'],
            'user_ids' => $validated['user_ids'] ?? null,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'status' => isset($validated['scheduled_at']) ? 'scheduled' : 'pending',
        ]);

        if (!isset($validated['scheduled_at'])) {
            $this->dispatchNotification($notification);
        }

        ActivityLog::log('notification.sent', null, $notification, null, $validated, "Push notification sent to {$validated['type']} users");

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال الإشعار',
            'data' => $notification,
        ]);
    }

    /**
     * Dispatch notification to target users.
     */
    protected function dispatchNotification(AdminNotification $notification): void
    {
        app(\App\Services\AdminBroadcastService::class)->dispatch($notification);
    }

    // ============================================
    // Maintenance Mode
    // ============================================

    /**
     * Enable maintenance mode.
     */
    public function enableMaintenance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'secret' => ['sometimes', 'string', 'min:6'],
            'message' => ['sometimes', 'string', 'max:500'],
        ]);

        $command = 'down';
        $options = [];

        if (isset($validated['secret'])) {
            $options['--secret'] = $validated['secret'];
        }

        if (isset($validated['message'])) {
            $options['--message'] = $validated['message'];
        }

        Artisan::call($command, $options);

        ActivityLog::log('maintenance.enabled', null, null, null, $validated, 'Maintenance mode enabled');

        return response()->json([
            'success' => true,
            'message' => 'تم تفعيل وضع الصيانة',
            'data' => [
                'secret' => $validated['secret'] ?? null,
                'bypass_url' => isset($validated['secret']) ? url("/{$validated['secret']}") : null,
            ],
        ]);
    }

    /**
     * Disable maintenance mode.
     */
    public function disableMaintenance(): JsonResponse
    {
        Artisan::call('up');

        ActivityLog::log('maintenance.disabled', null, null, null, null, 'Maintenance mode disabled');

        return response()->json([
            'success' => true,
            'message' => 'تم تعطيل وضع الصيانة',
        ]);
    }

    // ============================================
    // Currencies
    // ============================================

    /**
     * Get supported currencies.
     */
    public function getCurrencies(): JsonResponse
    {
        $currencies = SystemSetting::get('supported_currencies', ['USD', 'SYP']);

        return response()->json([
            'success' => true,
            'data' => $currencies,
        ]);
    }

    /**
     * Update supported currencies.
     */
    public function updateCurrencies(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'currencies' => ['required', 'array', 'min:1'],
            'currencies.*' => ['string', 'size:3'],
        ]);

        SystemSetting::set('supported_currencies', $validated['currencies'], 'json');
        SystemSetting::where('key', 'supported_currencies')->update(['group' => 'general']);

        ActivityLog::log('currencies.updated', null, null, null, $validated, 'Updated supported currencies');

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث العملات',
            'data' => $validated['currencies'],
        ]);
    }

    // ============================================
    // Referrals
    // ============================================

    /**
     * List referrals.
     */
    public function getReferrals(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = User::withCount('referrals')
            ->having('referrals_count', '>', 0);

        if (!empty($validated['search'])) {
            $term = '%' . $validated['search'] . '%';
            $query->where(function (Builder $q) use ($term) {
                $q->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('referral_code', 'like', $term);
            });
        }

        $perPage = min((int) ($validated['per_page'] ?? 15), 100);
        $referrers = $query->orderByDesc('referrals_count')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $referrers,
        ]);
    }

    /**
     * Get referral statistics.
     */
    public function getReferralStats(): JsonResponse
    {
        $totalReferrals = User::whereNotNull('referred_by')->count();
        $topReferrers = User::withCount('referrals')
            ->having('referrals_count', '>', 0)
            ->orderByDesc('referrals_count')
            ->take(10)
            ->get();

        $referralConfig = [
            'bonus_referrer' => SystemSetting::get('referral_bonus_referrer', 10),
            'bonus_referred' => SystemSetting::get('referral_bonus_referred', 5),
            'enabled' => SystemSetting::get('referral_enabled', true),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'total_referrals' => $totalReferrals,
                'top_referrers' => $topReferrers,
                'config' => $referralConfig,
            ],
        ]);
    }

    /**
     * Update referral configuration.
     */
    public function updateReferralConfig(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bonus_referrer' => ['sometimes', 'numeric', 'min:0'],
            'bonus_referred' => ['sometimes', 'numeric', 'min:0'],
            'enabled' => ['sometimes', 'boolean'],
        ]);

        if (isset($validated['bonus_referrer'])) {
            SystemSetting::set('referral_bonus_referrer', $validated['bonus_referrer'], 'decimal');
        }
        if (isset($validated['bonus_referred'])) {
            SystemSetting::set('referral_bonus_referred', $validated['bonus_referred'], 'decimal');
        }
        if (isset($validated['enabled'])) {
            SystemSetting::set('referral_enabled', $validated['enabled'], 'boolean');
        }

        ActivityLog::log('referral.config_updated', null, null, null, $validated, 'Updated referral configuration');

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث إعدادات الإحالة',
        ]);
    }

    // ============================================
    // Exchange Rates (Simplified - Single Row USD/SYP)
    // ============================================

    /**
     * Get current exchange rate (single row USD/SYP).
     */
    public function getExchangeRates(): JsonResponse
    {
        $rate = ExchangeRate::where('from_currency', 'USD')
            ->where('to_currency', 'SYP')
            ->first();

        if (!$rate) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'سعر الصرف غير مُعيَّن بعد',
            ]);
        }

        // Calculate preview of buy/sell rates using model accessors
        $buyRate = $rate->getBuyRate();
        $sellRate = $rate->getSellRate();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $rate->id,
                'rate' => (float) $rate->rate,
                'spread' => (float) $rate->spread,
                'buy_rate' => round($buyRate, 2),
                'sell_rate' => round($sellRate, 2),
                'is_active' => $rate->is_active,
                'updated_at' => $rate->updated_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Update exchange rate (single row USD/SYP).
     * 
     * Admin sets:
     * - rate: Base rate (1 USD = X SYP)
     * - spread: Spread percentage (difference between buy/sell)
     * 
     * System calculates:
     * - buy_rate: rate - (spread/2)%
     * - sell_rate: rate + (spread/2)%
     */
    public function updateExchangeRate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rate' => ['required', 'numeric', 'min:1'],
            'spread' => ['required', 'numeric', 'min:0', 'max:20'],
        ]);

        $rate = $validated['rate'];
        $spread = $validated['spread'];

        // Calculate buy/sell rates
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
                'source' => 'admin',
                'is_active' => true,
                'fetched_at' => now(),
            ]
        );

        // Record in history
        \App\Models\ExchangeRateHistory::create([
            'from_currency' => 'USD',
            'to_currency' => 'SYP',
            'rate' => $rate,
            'buy_rate' => $buyRate,
            'sell_rate' => $sellRate,
            'source' => 'admin',
            'recorded_at' => now(),
        ]);

        // Clear cache
        Cache::forget('exchange_rate_usd_syp');

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث سعر الصرف بنجاح',
            'data' => [
                'rate' => $rate,
                'spread' => $spread,
                'buy_rate' => round($buyRate, 2),
                'sell_rate' => round($sellRate, 2),
            ],
        ]);
    }

    // ============================================
    // Advanced Fees
    // ============================================

    /**
     * Get all fees.
     */
    public function getAllFees(): JsonResponse
    {
        $fees = Fee::all();

        return response()->json([
            'success' => true,
            'data' => $fees,
        ]);
    }

    /**
     * Update a fee.
     */
    public function updateFee(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'type' => ['required', 'string', 'in:fixed,percentage,tiered,mixed'],
            'fixed_amount' => ['nullable', 'numeric', 'min:0'],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'min_fee' => ['nullable', 'numeric', 'min:0'],
            'max_fee' => ['nullable', 'numeric', 'min:0'],
            'kyc_level_min' => ['nullable', 'integer', 'min:0', 'max:3'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $fee = Fee::updateOrCreate(
            ['code' => $validated['code']],
            $validated
        );

        Cache::forget("fee_config_{$validated['code']}");

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الرسم',
            'data' => $fee,
        ]);
    }

    // ============================================
    // KYC Levels
    // ============================================

    /**
     * Get KYC levels.
     */
    public function getKycLevels(): JsonResponse
    {
        $levels = KycLevel::orderBy('level')->get();

        return response()->json([
            'success' => true,
            'data' => $levels,
        ]);
    }

    /**
     * Update KYC level.
     */
    public function updateKycLevel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'level' => ['required', 'integer', 'min:0', 'max:3'],
            'name' => ['required', 'string', 'max:100'],
            'name_ar' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:500'],
            'requirements' => ['required', 'array'],
            'daily_limit' => ['required', 'numeric', 'min:0'],
            'monthly_limit' => ['required', 'numeric', 'min:0'],
            'single_transaction_limit' => ['required', 'numeric', 'min:0'],
            'withdrawal_limit' => ['required', 'numeric', 'min:0'],
            'can_withdraw' => ['required', 'boolean'],
            'can_create_card' => ['required', 'boolean'],
        ]);

        $level = KycLevel::updateOrCreate(
            ['level' => $validated['level']],
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث مستوى KYC',
            'data' => $level,
        ]);
    }

    /**
     * Get pending KYC verifications.
     */
    public function getPendingKycVerifications(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = min((int) ($validated['per_page'] ?? 20), 100);
        $verifications = KycVerification::with('user')
            ->whereIn('status', ['submitted', 'pending'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $verifications,
        ]);
    }

    /**
     * Review KYC verification.
     */
    public function reviewKycVerification(KycReviewRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();
        $verification = KycVerification::findOrFail($id);
        $kycService = app(KycService::class);

        $result = $kycService->reviewVerification(
            $verification,
            $request->user(),
            $validated['decision'],
            $validated['reason'] ?? null
        );

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result['new_kyc_level'] ?? null,
        ]);
    }

    // ============================================
    // Card Inventory
    // ============================================

    /**
     * Get card inventory.
     */
    public function getCardInventory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'assigned' => ['nullable', 'boolean'],
            'brand' => ['nullable', 'string', 'in:visa,mastercard'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = CardInventory::query();

        if (isset($validated['assigned']) && $validated['assigned'] !== null) {
            $query->where('is_assigned', filter_var($validated['assigned'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($validated['brand'])) {
            $query->where('brand', $validated['brand']);
        }

        $perPage = min((int) ($validated['per_page'] ?? 50), 100);
        $inventory = $query->latest()->paginate($perPage);

        $stats = [
            'total' => CardInventory::count(),
            'available' => CardInventory::where('is_assigned', false)->count(),
            'assigned' => CardInventory::where('is_assigned', true)->count(),
            'by_brand' => CardInventory::select('brand')
                ->selectRaw('COUNT(*) as count')
                ->where('is_assigned', false)
                ->groupBy('brand')
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $inventory,
            'stats' => $stats,
        ]);
    }

    /**
     * Import cards from file.
     */
    public function importCards(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $path = $request->file('file')->store('card-imports', 'local');
        $cardService = app(CardService::class);

        $result = $cardService->importCardsFromFile(storage_path('app/' . $path));

        return response()->json([
            'success' => true,
            'message' => "Imported {$result['imported']} cards",
            'data' => $result,
        ]);
    }

    /**
     * Get card pricing.
     */
    public function getCardPricing(): JsonResponse
    {
        $pricing = CardPricing::all();

        return response()->json([
            'success' => true,
            'data' => $pricing,
        ]);
    }

    /**
     * Update card pricing.
     */
    public function updateCardPricing(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'brand' => ['required', 'string', 'in:visa,mastercard,all'],
            'type' => ['required', 'string', 'in:virtual,physical,all'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'monthly_fee' => ['nullable', 'numeric', 'min:0'],
            'min_load' => ['required', 'numeric', 'min:0'],
            'max_load' => ['required', 'numeric', 'min:0'],
            'load_fee_percentage' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'kyc_level_required' => ['required', 'integer', 'min:0', 'max:3'],
        ]);

        $pricing = CardPricing::updateOrCreate(
            ['brand' => $validated['brand'], 'type' => $validated['type']],
            array_merge($validated, ['is_active' => true])
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث أسعار البطاقات',
            'data' => $pricing,
        ]);
    }

    // ============================================
    // Reports
    // ============================================

    /**
     * Get platform reports.
     */
    public function getReports(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => ['nullable', 'string', 'in:day,week,month,year'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $period = $validated['period'] ?? 'month';
        $from = $validated['from'] ?? now()->startOf($period)->toDateString();
        $to = $validated['to'] ?? now()->toDateString();

        $users = $this->getUsersReport($from, $to);
        $transactions = $this->getTransactionsReport($from, $to);
        $wallets = $this->getWalletsReport($from, $to);
        $cards = $this->getCardsReport($from, $to);

        return response()->json([
            'success' => true,
            'data' => [
                'period' => ['from' => $from, 'to' => $to],
                'users' => $users,
                'transactions' => $transactions,
                'wallets' => $wallets,
                'cards' => $cards,
            ],
        ]);
    }

    /**
     * Get users report.
     */
    protected function getUsersReport(string $from, string $to): array
    {
        return [
            'new' => User::whereBetween('created_at', [$from, $to])->count(),
            'active' => User::whereBetween('last_login_at', [$from, $to])->count(),
            'kyc_verified' => User::where('kyc_status', 'verified')
                ->whereBetween('kyc_verified_at', [$from, $to])->count(),
        ];
    }

    /**
     * Get transactions report.
     */
    protected function getTransactionsReport(string $from, string $to): array
    {
        $baseQuery = Transaction::whereBetween('created_at', [$from, $to]);

        return [
            'count' => $baseQuery->count(),
            'volume' => (float) (clone $baseQuery)->where('status', 'completed')->sum('amount'),
            'fees_collected' => (float) (clone $baseQuery)->where('status', 'completed')->sum('fee'),
            'by_type' => Transaction::whereBetween('created_at', [$from, $to])
                ->select('type')
                ->selectRaw('COUNT(*) as count')
                ->selectRaw('SUM(amount) as total')
                ->groupBy('type')
                ->get(),
        ];
    }

    /**
     * Get wallets report.
     */
    protected function getWalletsReport(string $from, string $to): array
    {
        return [
            'total_balance' => (float) Wallet::sum('balance'),
            'new' => Wallet::whereBetween('created_at', [$from, $to])->count(),
        ];
    }

    /**
     * Get cards report.
     */
    protected function getCardsReport(string $from, string $to): array
    {
        return [
            'active' => VirtualCard::where('status', 'active')->count(),
            'new' => VirtualCard::whereBetween('created_at', [$from, $to])->count(),
            'volume' => (float) VirtualCard::sum('total_spent'),
        ];
    }

    // ============================================
    // Export
    // ============================================

    /**
     * Export data as CSV.
     */
    public function exportCsv(ExportCsvRequest $request, string $type): StreamedResponse|JsonResponse
    {
        $delimiter = ',';

        return match ($type) {
            'users' => $this->exportUsersCsv($delimiter),
            'transactions' => $this->exportTransactionsCsv($delimiter),
            default => response()->json([
                'success' => false,
                'message' => 'نوع التصدير غير صالح',
            ], 422),
        };
    }

    /**
     * Export users as CSV.
     */
    protected function exportUsersCsv(string $delimiter): StreamedResponse
    {
        $filename = 'users-' . now()->format('Y-m-d') . '.csv';
        $headers = ['ID', 'Name', 'Email', 'Phone', 'Status', 'KYC', 'Admin', 'Wallet Count', 'Created'];

        $callback = function () use ($headers, $delimiter) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers, $delimiter);

            User::chunk(500, function ($users) use ($handle, $delimiter) {
                foreach ($users as $user) {
                    fputcsv($handle, [
                        $user->id,
                        $user->full_name,
                        $user->email,
                        $user->phone,
                        $user->status->value,
                        $user->kyc_status->value,
                        $user->is_admin ? 'Yes' : 'No',
                        $user->wallets()->count(),
                        $user->created_at,
                    ], $delimiter);
                }
            });

            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }

    /**
     * Export transactions as CSV.
     */
    protected function exportTransactionsCsv(string $delimiter): StreamedResponse
    {
        $filename = 'transactions-' . now()->format('Y-m-d') . '.csv';
        $headers = ['ID', 'Reference', 'User', 'Type', 'Currency', 'Amount', 'Fee', 'Status', 'Date'];

        $callback = function () use ($headers, $delimiter) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers, $delimiter);

            Transaction::with('user')->latest()->chunk(500, function ($transactions) use ($handle, $delimiter) {
                foreach ($transactions as $t) {
                    fputcsv($handle, [
                        $t->id,
                        $t->reference,
                        $t->user?->full_name,
                        $t->type->value,
                        $t->currency,
                        $t->amount,
                        $t->fee,
                        $t->status->value,
                        $t->created_at,
                    ], $delimiter);
                }
            });

            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }
}
