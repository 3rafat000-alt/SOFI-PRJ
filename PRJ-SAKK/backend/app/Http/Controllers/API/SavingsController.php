<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Concerns\VerifiesTransactionAuth;
use App\Http\Controllers\Controller;
use App\Models\SavingsGoal;
use App\Models\SavingsTransaction;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Enums\TransactionType;
use App\Enums\TransactionCategory;
use App\Enums\TransactionStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SavingsController extends Controller
{
    use VerifiesTransactionAuth;

    /// Overview across all of the user's savings goals.
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();

        $goals = SavingsGoal::where('user_id', $user->id)
            ->where('status', '!=', 'closed')
            ->get();

        $usdWallet = Wallet::where('user_id', $user->id)
            ->where('currency', 'USD')->first();

        return response()->json([
            'data' => [
                'total_saved' => round($goals->sum('saved_amount'), 2),
                'goals_count' => $goals->count(),
                'completed_count' => $goals->where('status', 'completed')->count(),
                'usd_balance' => $usdWallet ? (float) $usdWallet->available_balance : 0,
            ],
        ]);
    }

    /// List the user's savings goals (newest first).
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $goals = SavingsGoal::where('user_id', $user->id)
            ->where('status', '!=', 'closed')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($g) => $this->transform($g));

        return response()->json(['data' => $goals]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'target_amount' => 'nullable|numeric|min:1',
            'target_date' => 'nullable|date|after:today',
            'icon' => 'nullable|string|max:40',
            'color' => 'nullable|string|max:20',
            'initial_amount' => 'nullable|numeric|min:0',
        ]);

        $user = $request->user();

        return DB::transaction(function () use ($user, $validated, $request) {
            $goal = SavingsGoal::create([
                'user_id' => $user->id,
                'name' => $validated['name'],
                'target_amount' => $validated['target_amount'] ?? null,
                'saved_amount' => 0,
                'currency' => 'USD',
                'status' => 'active',
                'icon' => $validated['icon'] ?? null,
                'color' => $validated['color'] ?? null,
                'target_date' => $validated['target_date'] ?? null,
            ]);

            // Optional opening deposit.
            $initial = (float) ($validated['initial_amount'] ?? 0);
            if ($initial > 0) {
                $usdWallet = Wallet::where('user_id', $user->id)
                    ->where('currency', 'USD')
                    ->lockForUpdate()
                    ->first();
                if (!$usdWallet || $usdWallet->available_balance < $initial) {
                    return response()->json(['message' => 'رصيد غير كافٍ في محفظة الدولار للإيداع الأولي'], 422);
                }
                $this->moveToSavings($user, $goal, $initial);
            }

            return response()->json([
                'message' => 'تم إنشاء هدف الادخار',
                'data' => $this->transform($goal->fresh()),
            ], 201);
        });
    }

    public function show(Request $request, SavingsGoal $savings): JsonResponse
    {
        $this->authorizeGoal($request, $savings);

        $transactions = $savings->transactions()
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn ($t) => [
                'reference' => $t->reference,
                'type' => $t->type,
                'type_label' => $t->type_label,
                'amount' => (float) $t->amount,
                'status' => $t->status,
                'notes' => $t->notes,
                'created_at' => $t->created_at->toIso8601String(),
            ]);

        return response()->json([
            'data' => array_merge($this->transform($savings), [
                'transactions' => $transactions,
            ]),
        ]);
    }

    public function deposit(Request $request, SavingsGoal $savings): JsonResponse
    {
        $this->authorizeGoal($request, $savings);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'pin' => 'required_without:biometric_token|string',
        ]);

        $user = $request->user();

        // Second factor (fail-closed): a valid PIN or a cryptographically verified
        // biometric signature must be presented. Mere presence of `biometric_token`
        // no longer bypasses the check (SEC C2).
        if (!$this->verifyTransactionFactor($request, $user)) {
            return response()->json(['message' => 'فشل التحقق الأمني — رمز PIN أو البصمة غير صحيح.'], 422);
        }

        return DB::transaction(function () use ($user, $savings, $validated) {
            // Lock wallet row to prevent race conditions
            $usdWallet = Wallet::where('user_id', $user->id)
                ->where('currency', 'USD')
                ->lockForUpdate()
                ->first();

            if (!$usdWallet || $usdWallet->available_balance < (float) $validated['amount']) {
                return response()->json(['message' => 'رصيد غير كافٍ في محفظة الدولار'], 422);
            }

            $this->moveToSavings($user, $savings, (float) $validated['amount']);

            return response()->json([
                'message' => 'تم الإيداع في الادخار',
                'data' => $this->transform($savings->fresh()),
            ]);
        });
    }

    public function withdraw(Request $request, SavingsGoal $savings): JsonResponse
    {
        $this->authorizeGoal($request, $savings);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'pin' => 'required_without:biometric_token|string',
        ]);

        $user = $request->user();

        // KYC gate: a savings withdrawal credits the spendable USD wallet, so it
        // is held to the same can_withdraw policy as wallet/crypto withdrawals
        // (fail-closed — unknown/misconfigured level grants no permission).
        if (! (app(\App\Services\KycService::class)->permissionsForUser($user)['can_withdraw'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'السحب يتطلب توثيق الهوية (KYC).',
                'code' => 'kyc_required',
            ], 403);
        }

        // Second factor (fail-closed) — see SEC C2.
        if (!$this->verifyTransactionFactor($request, $user)) {
            return response()->json(['message' => 'فشل التحقق الأمني — رمز PIN أو البصمة غير صحيح.'], 422);
        }

        if ($savings->saved_amount < $validated['amount']) {
            return response()->json(['message' => 'المبلغ المطلوب أكبر من رصيد الادخار'], 422);
        }

        return DB::transaction(function () use ($user, $savings, $validated) {
            $this->moveFromSavings($user, $savings, (float) $validated['amount']);

            return response()->json([
                'message' => 'تم سحب المبلغ إلى محفظتك',
                'data' => $this->transform($savings->fresh()),
            ]);
        });
    }

    /// Close a goal: return any remaining balance to the USD wallet, then archive.
    public function close(Request $request, SavingsGoal $savings): JsonResponse
    {
        $this->authorizeGoal($request, $savings);

        $user = $request->user();

        return DB::transaction(function () use ($user, $savings) {
            if ($savings->saved_amount > 0) {
                $this->moveFromSavings($user, $savings, (float) $savings->saved_amount);
            }
            $savings->update(['status' => 'closed']);

            return response()->json(['message' => 'تم إغلاق هدف الادخار وإرجاع الرصيد']);
        });
    }

    // ==================== Helpers ====================

    private function moveToSavings($user, SavingsGoal $goal, float $amount): void
    {
        $usdWallet = Wallet::where('user_id', $user->id)->where('currency', 'USD')->first();
        $balanceBefore = $usdWallet ? (float) $usdWallet->balance : 0.0;
        if (!$usdWallet || !$usdWallet->debit($amount, 'ادخار: ' . $goal->name)) {
            throw new \RuntimeException('رصيد غير كافٍ في محفظة الدولار');
        }

        Transaction::create([
            'user_id' => $user->id,
            'wallet_id' => $usdWallet->id,
            'type' => TransactionType::WITHDRAWAL,
            'category' => TransactionCategory::SAVINGS,
            'currency' => 'USD',
            'amount' => -$amount,
            'fee' => 0,
            'net_amount' => -$amount,
            'balance_before' => $balanceBefore,
            'balance_after' => (float) $usdWallet->balance,
            'status' => TransactionStatus::COMPLETED,
            'title' => 'إيداع في الادخار',
            'description' => "إيداع في هدف الادخار: {$goal->name}",
        ]);

        $goal->deposit($amount);

        SavingsTransaction::create([
            'savings_goal_id' => $goal->id,
            'user_id' => $user->id,
            'type' => 'deposit',
            'amount' => $amount,
            'currency' => 'USD',
            'status' => 'completed',
        ]);
    }

    private function moveFromSavings($user, SavingsGoal $goal, float $amount): void
    {
        $goal->withdraw($amount);

        $usdWallet = Wallet::where('user_id', $user->id)->where('currency', 'USD')->first();
        $balanceBefore = $usdWallet ? (float) $usdWallet->balance : 0.0;
        $usdWallet->credit($amount, 'سحب من الادخار: ' . $goal->name);

        Transaction::create([
            'user_id' => $user->id,
            'wallet_id' => $usdWallet->id,
            'type' => TransactionType::DEPOSIT,
            'category' => TransactionCategory::SAVINGS,
            'currency' => 'USD',
            'amount' => $amount,
            'fee' => 0,
            'net_amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => (float) $usdWallet->balance,
            'status' => TransactionStatus::COMPLETED,
            'title' => 'سحب من الادخار',
            'description' => "سحب من هدف الادخار: {$goal->name}",
        ]);

        SavingsTransaction::create([
            'savings_goal_id' => $goal->id,
            'user_id' => $user->id,
            'type' => 'withdraw',
            'amount' => $amount,
            'currency' => 'USD',
            'status' => 'completed',
        ]);
    }

    private function authorizeGoal(Request $request, SavingsGoal $savings): void
    {
        abort_unless($savings->user_id === $request->user()->id, 403, 'غير مصرح');
    }

    private function transform(SavingsGoal $goal): array
    {
        return [
            'id' => $goal->id,
            'uuid' => $goal->uuid,
            'name' => $goal->name,
            'target_amount' => $goal->target_amount ? (float) $goal->target_amount : null,
            'saved_amount' => (float) $goal->saved_amount,
            'progress_percent' => $goal->progress_percent,
            'currency' => $goal->currency,
            'status' => $goal->status,
            'status_label' => $goal->status_label,
            'icon' => $goal->icon,
            'color' => $goal->color,
            'target_date' => $goal->target_date?->toDateString(),
            'created_at' => $goal->created_at->toIso8601String(),
        ];
    }
}
