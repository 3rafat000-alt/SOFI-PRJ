<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Cashback — rewards the user has earned. Backed by reward-type transactions
 * (cashback is credited as a 'reward'). Read-only summary + history.
 */
class CashbackController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $base = Transaction::where('user_id', $user->id)
            ->where('type', 'reward');

        $total = (float) (clone $base)->where('currency', 'USD')->sum('amount');
        $count = (clone $base)->count();
        $transactions = (clone $base)->latest()->limit(50)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'currency' => 'USD',
                'count' => $count,
                'transactions' => TransactionResource::collection($transactions),
            ],
        ]);
    }
}
