<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Enums\TransactionType;
use App\Enums\TransactionCategory;
use App\Enums\TransactionStatus;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    /**
     * Create a fee transaction
     */
    public function chargeFee(Wallet $wallet, float $amount, string $reason): Transaction
    {
        return DB::transaction(function () use ($wallet, $amount, $reason) {
            $balanceBefore = $wallet->balance;

            $wallet->debit($amount);

            return Transaction::create([
                'user_id' => $wallet->user_id,
                'wallet_id' => $wallet->id,
                'type' => TransactionType::FEE,
                'category' => TransactionCategory::FEE,
                'currency' => $wallet->currency,
                'amount' => -$amount,
                'fee' => 0,
                'net_amount' => -$amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $wallet->balance,
                'status' => TransactionStatus::COMPLETED,
                'title' => $reason,
                'completed_at' => now(),
            ]);
        });
    }

    /**
     * Create a reward transaction
     */
    public function addReward(Wallet $wallet, float $amount, string $reason, array $metadata = []): Transaction
    {
        return DB::transaction(function () use ($wallet, $amount, $reason, $metadata) {
            $balanceBefore = $wallet->balance;

            $wallet->credit($amount);

            return Transaction::create([
                'user_id' => $wallet->user_id,
                'wallet_id' => $wallet->id,
                'type' => TransactionType::REWARD,
                'category' => TransactionCategory::REWARD,
                'currency' => $wallet->currency,
                'amount' => $amount,
                'fee' => 0,
                'net_amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $wallet->balance,
                'status' => TransactionStatus::COMPLETED,
                'title' => $reason,
                'metadata' => $metadata,
                'completed_at' => now(),
            ]);
        });
    }

    /**
     * Get user's recent transactions
     */
    public function getRecent(User $user, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return $user->transactions()
            ->with(['wallet', 'card', 'recipient'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Search transactions
     */
    public function search(User $user, array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $user->transactions()->with(['wallet', 'card', 'recipient']);

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate($filters['per_page'] ?? 20);
    }
}
