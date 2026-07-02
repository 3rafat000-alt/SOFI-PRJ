<?php

namespace App\Http\Controllers\API;

use App\Enums\KycStatus;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UsersIndexRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\ActivityLog;
use App\Models\KycDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUserController extends Controller
{
    public function __construct() {}

    public function index(UsersIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $query = User::query();

        $this->applySearch($query, $validated['search'] ?? null);
        $this->applyFilters($query, $validated);

        $sortField = $validated['sort_by'] ?? 'created_at';
        $sortDir = $validated['sort_dir'] ?? 'desc';
        $allowedSorts = ['created_at', 'first_name', 'last_name', 'email', 'status', 'kyc_status'];
        if (in_array($sortField, $allowedSorts, true)) {
            $query->orderBy($sortField, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $perPage = min((int) ($validated['per_page'] ?? 20), 100);
        $users = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $user = User::with(['wallets', 'cards', 'kycDocuments'])
            ->withCount('transactions')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'full_name' => $user->full_name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $user->avatar,
                'date_of_birth' => $user->date_of_birth,
                'gender' => $user->gender,
                'country_code' => $user->country_code,
                'language' => $user->language,
                'timezone' => $user->timezone,
                'status' => $user->status,
                'is_active' => $user->is_active,
                'is_admin' => $user->is_admin,
                'kyc_status' => $user->kyc_status,
                'kyc_data' => $user->kyc_data,
                'is_kyc_verified' => $user->is_kyc_verified,
                'kyc_verified_at' => $user->kyc_verified_at,
                'has_pin' => !is_null($user->pin_code),
                'two_factor_enabled' => $user->two_factor_enabled,
                'email_verified' => !is_null($user->email_verified_at),
                'phone_verified' => !is_null($user->phone_verified_at),
                'referral_code' => $user->referral_code,
                'referred_by' => $user->referred_by,
                'wallet_count' => $user->wallets->count(),
                'card_count' => $user->cards->count(),
                'transaction_count' => $user->transactions_count,
                'total_deposits' => $user->wallets->sum('total_deposits'),
                'total_withdrawals' => $user->wallets->sum('total_withdrawals'),
                'total_balance' => $user->wallets->sum('balance'),
                'last_login_at' => $user->last_login_at,
                'last_login_ip' => $user->last_login_ip,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'wallets' => $user->wallets,
                'cards' => $user->cards,
                'kyc_documents' => $user->kycDocuments,
            ],
        ]);
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $validated = $request->validated();
        $oldValues = $user->only(array_merge(array_keys($validated), ['is_admin']));

        if ($request->has('is_admin')) {
            $user->forceFill(['is_admin' => (bool) $request->input('is_admin')])->save();
        }

        $user->update($validated);

        ActivityLog::log(
            'admin.users.updated',
            $user,
            $user,
            $oldValues,
            $user->fresh()->only(array_merge(array_keys($validated), ['is_admin'])),
            'Admin updated user via API'
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المستخدم بنجاح',
            'data' => $user->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف المستخدمين المشرفين',
            ], 422);
        }

        $snapshot = $user->only(['id', 'uuid', 'email', 'phone', 'status']);

        DB::transaction(function () use ($user) {
            $user->wallets()->delete();
            $user->cards()->delete();
            $user->transactions()->delete();
            $user->delete();
        });

        ActivityLog::log(
            'admin.users.deleted',
            null,
            $user,
            $snapshot,
            null,
            'Admin deleted user via API'
        );

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المستخدم بنجاح',
        ]);
    }

    public function kycDocuments(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:pending,approved,rejected'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = KycDocument::with('user');

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }

        $perPage = min((int) ($validated['per_page'] ?? 20), 100);

        return response()->json([
            'success' => true,
            'data' => $query->latest()->paginate($perPage),
        ]);
    }

    public function approveKyc(int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $oldStatus = $user->kyc_status instanceof KycStatus ? $user->kyc_status->value : $user->kyc_status;

        $user->forceFill([
            'kyc_status' => KycStatus::VERIFIED,
            'kyc_verified_at' => now(),
        ])->save();

        $user->kycDocuments()->where('status', 'pending')->update([
            'status' => 'approved',
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        ActivityLog::log(
            'admin.users.kyc_approved',
            $user,
            $user,
            ['kyc_status' => $oldStatus],
            ['kyc_status' => KycStatus::VERIFIED->value],
            'Admin approved KYC via API'
        );

        return response()->json([
            'success' => true,
            'message' => 'تمت الموافقة على KYC بنجاح',
        ]);
    }

    public function rejectKyc(Request $request, int $userId): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $user = User::findOrFail($userId);
        $oldStatus = $user->kyc_status instanceof KycStatus ? $user->kyc_status->value : $user->kyc_status;

        $user->forceFill(['kyc_status' => KycStatus::REJECTED])->save();

        $user->kycDocuments()->where('status', 'pending')->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['reason'],
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        ActivityLog::log(
            'admin.users.kyc_rejected',
            $user,
            $user,
            ['kyc_status' => $oldStatus],
            ['kyc_status' => KycStatus::REJECTED->value, 'rejection_reason' => $validated['reason']],
            $validated['reason']
        );

        return response()->json([
            'success' => true,
            'message' => 'تم رفض KYC',
        ]);
    }

    protected function applySearch(Builder $query, ?string $search): void
    {
        if (blank($search)) {
            return;
        }

        $term = '%' . $search . '%';
        $query->where(function (Builder $q) use ($term) {
            $q->where('first_name', 'like', $term)
                ->orWhere('last_name', 'like', $term)
                ->orWhere('email', 'like', $term)
                ->orWhere('phone', 'like', $term);
        });
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['kyc_status'])) {
            $query->where('kyc_status', $filters['kyc_status']);
        }

        if (isset($filters['is_admin']) && $filters['is_admin'] !== null) {
            $query->where('is_admin', filter_var($filters['is_admin'], FILTER_VALIDATE_BOOLEAN));
        }
    }
}
