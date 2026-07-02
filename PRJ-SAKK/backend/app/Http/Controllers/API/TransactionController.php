<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Enums\TransactionType;
use App\Enums\TransactionCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransactionController extends Controller
{
    /**
     * Get all transactions for authenticated user
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        // Validate all filter inputs with strict boundaries
        $validated = $request->validate([
            'type' => 'nullable|string|in:' . implode(',', array_column(TransactionType::cases(), 'value')),
            'category' => 'nullable|string|in:' . implode(',', array_column(TransactionCategory::cases(), 'value')),
            'status' => 'nullable|string|in:pending,processing,completed,failed,cancelled,reversed',
            'wallet_id' => 'nullable|integer|exists:wallets,id',
            'card_id' => 'nullable|integer|exists:virtual_cards,id',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'period' => 'nullable|string|in:day,week,month,quarter,year',
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = $request->user()->transactions()->visibleToUser()->with(['wallet', 'card', 'recipient']);

        // Apply validated filters
        if ($validated['type'] ?? null) {
            $query->where('type', $validated['type']);
        }

        if ($validated['category'] ?? null) {
            $query->where('category', $validated['category']);
        }

        if ($validated['status'] ?? null) {
            $query->where('status', $validated['status']);
        }

        if ($validated['wallet_id'] ?? null) {
            $query->where('wallet_id', $validated['wallet_id']);
        }

        if ($validated['card_id'] ?? null) {
            $query->where('card_id', $validated['card_id']);
        }

        // Verify wallet + card ownership
        if ($validated['wallet_id'] ?? null) {
            $query->whereHas('wallet', fn ($q) => $q->where('user_id', $request->user()->id));
        }
        if ($validated['card_id'] ?? null) {
            $query->whereHas('card', fn ($q) => $q->where('user_id', $request->user()->id));
        }

        // Date range filters
        if ($validated['from'] ?? null) {
            $query->whereDate('created_at', '>=', $validated['from']);
        }
        if ($validated['to'] ?? null) {
            $query->whereDate('created_at', '<=', $validated['to']);
        }

        // Period filter
        if ($validated['period'] ?? null) {
            $query->forPeriod($validated['period']);
        }

        // Search (still uses LIKE but with validated max length)
        if ($validated['search'] ?? null) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $transactions = $query->latest()->paginate($validated['per_page'] ?? 20);

        return TransactionResource::collection($transactions);
    }

    /**
     * Get transaction details
     */
    public function show(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'المعاملة غير موجودة',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new TransactionResource($transaction->load(['wallet', 'card', 'recipient'])),
        ]);
    }

    /**
     * Get transaction by reference
     */
    public function byReference(Request $request, string $reference): JsonResponse
    {
        $transaction = Transaction::where('reference', $reference)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'المعاملة غير موجودة',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new TransactionResource($transaction->load(['wallet', 'card', 'recipient'])),
        ]);
    }

    /**
     * Get transaction statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $period = $request->period ?? 'month';

        $query = $user->transactions()->completed();

        // Apply period filter
        $query->forPeriod($period);

        $transactions = $query->get();

        // Calculate stats
        $totalIncome = $transactions->where('amount', '>', 0)->sum('amount');
        $totalExpense = $transactions->where('amount', '<', 0)->sum('amount');
        $netChange = $totalIncome + $totalExpense;

        // Group by type
        $byType = $transactions->groupBy('type')->map(fn($group) => [
            'count' => $group->count(),
            'total' => $group->sum('amount'),
        ]);

        // Group by category
        $byCategory = $transactions->groupBy('category')->map(fn($group) => [
            'count' => $group->count(),
            'total' => $group->sum('amount'),
        ]);

        // Daily breakdown (last 7 days)
        $dailyBreakdown = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayTransactions = $user->transactions()
                ->completed()
                ->whereDate('created_at', $date)
                ->get();

            $dailyBreakdown[$date] = [
                'income' => $dayTransactions->where('amount', '>', 0)->sum('amount'),
                'expense' => abs($dayTransactions->where('amount', '<', 0)->sum('amount')),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $period,
                'summary' => [
                    'total_transactions' => $transactions->count(),
                    'total_income' => $totalIncome,
                    'total_expense' => abs($totalExpense),
                    'net_change' => $netChange,
                ],
                'by_type' => $byType,
                'by_category' => $byCategory,
                'daily_breakdown' => $dailyBreakdown,
            ],
        ]);
    }

    /**
     * Export transactions (CSV)
     */
    public function export(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'format' => 'nullable|in:csv,xlsx,pdf',
        ], [
            'from.required' => 'تاريخ البدء مطلوب.',
            'from.date' => 'تاريخ البدء غير صالح.',
            'to.required' => 'تاريخ الانتهاء مطلوب.',
            'to.date' => 'تاريخ الانتهاء غير صالح.',
            'to.after_or_equal' => 'تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء أو مساوياً له.',
            'format.in' => 'التنسيق يجب أن يكون csv أو xlsx أو pdf.',
        ]);

        $transactions = $request->user()->transactions()
            ->with(['wallet', 'card'])
            ->whereBetween('created_at', [$request->from, $request->to])
            ->latest()
            ->get();

        $filename = "transactions_{$request->from}_to_{$request->to}.csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'Reference',
                'Date',
                'Type',
                'Category',
                'Title',
                'Amount',
                'Fee',
                'Net Amount',
                'Currency',
                'Status',
                'Wallet',
                'Card',
                'Description',
            ]);

            // Data rows
            foreach ($transactions as $txn) {
                fputcsv($file, [
                    $txn->reference,
                    $txn->created_at->format('Y-m-d H:i:s'),
                    $txn->type->label(),
                    $txn->category->label(),
                    $txn->title,
                    $txn->amount,
                    $txn->fee,
                    $txn->net_amount,
                    $txn->currency,
                    $txn->status->label(),
                    $txn->wallet?->currency,
                    $txn->card?->card_number_masked,
                    $txn->description,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get transaction types
     */
    public function types(): JsonResponse
    {
        $types = collect(TransactionType::cases())->map(fn($type) => [
            'value' => $type->value,
            'label' => $type->label(),
            'label_ar' => $type->labelAr(),
            'icon' => $type->icon(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }

    /**
     * Get transaction categories
     */
    public function categories(): JsonResponse
    {
        $categories = collect(TransactionCategory::cases())->map(fn($cat) => [
            'value' => $cat->value,
            'label' => $cat->label(),
            'label_ar' => $cat->labelAr(),
            'icon' => $cat->icon(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }
}
