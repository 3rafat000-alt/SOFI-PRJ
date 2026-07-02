<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\TransactionCategory;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin Transactions — "money movement ledger".
 *
 * Transactions are append-only financial truth. There is NO edit surface here:
 * the single legitimate admin mutation is reverse() on a COMPLETED transaction,
 * which leaves the original intact (status → reversed) and appends an audited
 * adjustment. Everything else is read-only. Mirrors the UserController command
 * centre (live KPI fetch, AJAX fragment search, server-side export, slide-over).
 */
class TransactionController extends Controller
{
    /** Columns the frontend may sort on — prevents arbitrary ORDER BY injection. */
    private const SORT_ALLOWLIST = ['reference', 'amount', 'fee', 'status', 'created_at'];

    /** Filter keys that, when present, change the "no results" empty-state copy. */
    private const FILTER_KEYS = ['search', 'type', 'category', 'status', 'currency', 'date_from', 'date_to'];

    // ==================== Read actions ====================

    /**
     * Transactions index — full page or table-only fragment for AJAX live search.
     *
     * Fragment trigger: `$request->ajax()` OR `?fragment=1`.
     * Fragment renders `admin.transactions.partials._table` with `$transactions`.
     * Paginator always carries the query string so AJAX pagination keeps filters.
     */
    public function index(Request $request)
    {
        $query = Transaction::with('user');

        $this->applyFilters($query, $request);

        // --- Sort ---
        $sortCol = in_array($request->get('sort'), self::SORT_ALLOWLIST, true)
            ? $request->get('sort')
            : 'created_at';
        $sortDir = strtolower((string) $request->get('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortCol, $sortDir);

        $transactions = $query->paginate(20)->withQueryString();

        $isFragment = $request->ajax() || $request->boolean('fragment');
        if ($isFragment) {
            return view('admin.transactions.partials._table', compact('transactions'))->render();
        }

        return view('admin.transactions.index', compact('transactions'));
    }

    /**
     * KPI aggregates JSON — cached 30 s to avoid recomputing on every strip load.
     * Volume figures use SUM(ABS(amount)) so mixed-sign rows report gross movement.
     */
    public function kpis(Request $request): JsonResponse
    {
        $data = Cache::remember('admin.transactions.kpis', 30, function (): array {
            $absSum = fn ($q) => (float) $q->sum(DB::raw('ABS(amount)'));

            return [
                'today_volume'      => $absSum(
                    Transaction::whereDate('created_at', today())
                        ->where('status', TransactionStatus::COMPLETED->value)
                ),
                'total_deposits'    => $absSum(
                    Transaction::where('type', TransactionType::DEPOSIT->value)
                        ->where('status', TransactionStatus::COMPLETED->value)
                ),
                'total_withdrawals' => $absSum(
                    Transaction::where('type', TransactionType::WITHDRAWAL->value)
                        ->where('status', TransactionStatus::COMPLETED->value)
                ),
                'total_fees'        => (float) Transaction::where('status', TransactionStatus::COMPLETED->value)->sum('fee'),
                'pending_count'     => Transaction::where('status', TransactionStatus::PENDING->value)->count(),
                'failed_count'      => Transaction::where('status', TransactionStatus::FAILED->value)->count(),
            ];
        });

        return response()->json($data);
    }

    /**
     * Server-side CSV export — same filter logic as index(), never paginated.
     * Replaces the old client-side DOM-scrape export (which only saw the current
     * page and exported rendered text, not data). Streams in 500-row chunks.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = Transaction::with('user');
        $this->applyFilters($query, $request);

        ActivityLog::log('transactions.export', null, null, null, null, 'Admin exported transactions CSV');

        $filename = 'transactions_' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'reference', 'user', 'email', 'type', 'category',
                'currency', 'amount', 'fee', 'net_amount', 'status', 'created_at',
            ]);

            $query->chunk(500, function ($transactions) use ($handle): void {
                foreach ($transactions as $tx) {
                    fputcsv($handle, [
                        $tx->reference,
                        $tx->user ? trim($tx->user->first_name . ' ' . $tx->user->last_name) : '',
                        $tx->user?->email,
                        $tx->type instanceof TransactionType ? $tx->type->value : $tx->type,
                        $tx->category instanceof TransactionCategory ? $tx->category->value : $tx->category,
                        $tx->currency,
                        number_format((float) $tx->amount, 2, '.', ''),
                        number_format((float) ($tx->fee ?? 0), 2, '.', ''),
                        number_format((float) ($tx->net_amount ?? $tx->amount), 2, '.', ''),
                        $tx->status instanceof TransactionStatus ? $tx->status->value : $tx->status,
                        $tx->created_at?->toDateTimeString(),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Transaction detail — read-only sections + reverse danger zone.
     * Loads the audit trail and resolves the linked reversal (if any) in both
     * directions: the adjustment this tx spawned, or the original it reverses.
     */
    public function show(Transaction $transaction)
    {
        $transaction->load(['user', 'wallet', 'recipient', 'recipientWallet', 'card']);

        // Audit trail for this exact record.
        $activity = ActivityLog::where('entity_type', Transaction::class)
            ->where('entity_id', $transaction->id)
            ->latest()
            ->take(30)
            ->get();

        // If this completed tx was reversed, find the adjustment it spawned.
        $reversal = null;
        if ($transaction->status === TransactionStatus::REVERSED) {
            $reversal = Transaction::where('type', TransactionType::ADJUSTMENT)
                ->where('metadata->original_transaction_id', $transaction->id)
                ->orderByDesc('id')
                ->first();
        }

        // If this row IS a reversal adjustment, link back to the original.
        $original = null;
        if (! empty($transaction->metadata['original_transaction_id'])) {
            $original = Transaction::find($transaction->metadata['original_transaction_id']);
        }

        return view('admin.transactions.show', compact('transaction', 'activity', 'reversal', 'original'));
    }

    /**
     * Quick-view JSON for the index slide-over panel.
     */
    public function quickView(Transaction $transaction): JsonResponse
    {
        $transaction->load(['user', 'wallet']);

        return response()->json([
            'transaction' => [
                'reference'  => $transaction->reference,
                'type'       => $transaction->type instanceof TransactionType ? $transaction->type->value : $transaction->type,
                'type_label' => $transaction->type instanceof TransactionType ? $transaction->type->labelAr() : (string) $transaction->type,
                'category'   => $transaction->category instanceof TransactionCategory ? $transaction->category->labelAr() : (string) $transaction->category,
                'status'     => $transaction->status instanceof TransactionStatus ? $transaction->status->value : $transaction->status,
                'currency'   => $transaction->currency,
                'amount'     => (float) $transaction->amount,
                'fee'        => (float) ($transaction->fee ?? 0),
                'net_amount' => (float) ($transaction->net_amount ?? $transaction->amount),
                'created_at' => $transaction->created_at?->toDateTimeString(),
            ],
            'user' => $transaction->user ? [
                'id'        => $transaction->user->id,
                'full_name' => trim($transaction->user->first_name . ' ' . $transaction->user->last_name),
                'email'     => $transaction->user->email,
            ] : null,
            'wallet'   => $transaction->wallet ? ['currency' => $transaction->wallet->currency] : null,
            'view_url' => route('admin.transactions.show', $transaction->id),
        ]);
    }

    // ==================== Mutating action (the only one) ====================

    /**
     * Reverse a COMPLETED transaction — the sole admin mutation on this surface.
     *
     * Leaves the original intact (status → reversed) and appends an audited
     * adjustment carrying the reason. Guards: must be completed, must not itself
     * be an adjustment/reversal (Transaction::reverse() refuses those). Wrapped in
     * a DB transaction; writes ActivityLog. Returns JSON for the Alpine modal.
     */
    public function reverse(Request $request, Transaction $transaction): JsonResponse
    {
        Gate::authorize('reverse', $transaction);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        if ($transaction->status === TransactionStatus::REVERSED) {
            return response()->json(['success' => false, 'message' => 'المعاملة معكوسة مسبقاً'], 422);
        }

        if ($transaction->status !== TransactionStatus::COMPLETED) {
            return response()->json(['success' => false, 'message' => 'يمكن عكس المعاملات المكتملة فقط'], 422);
        }

        if ($transaction->type === TransactionType::ADJUSTMENT
            || $transaction->category === TransactionCategory::ADJUSTMENT) {
            return response()->json(['success' => false, 'message' => 'لا يمكن عكس معاملة تسوية'], 422);
        }

        try {
            $reversal = DB::transaction(function () use ($transaction, $validated) {
                $reversal = $transaction->reverse();

                if (! $reversal) {
                    throw new \RuntimeException('Transaction reversal returned null');
                }

                $reversal->update([
                    'metadata' => array_merge(
                        $reversal->metadata ?? [],
                        ['reversal_reason' => $validated['reason'], 'reversed_by' => auth()->id()]
                    ),
                ]);

                ActivityLog::log(
                    'transactions.reversed',
                    $transaction->user,
                    $transaction,
                    ['status' => TransactionStatus::COMPLETED->value],
                    ['status' => TransactionStatus::REVERSED->value, 'reversal_reference' => $reversal->reference],
                    $validated['reason']
                );

                return $reversal;
            });

            return response()->json([
                'success'             => true,
                'message'             => 'تم عكس المعاملة بنجاح',
                'reversal_reference'  => $reversal->reference,
            ]);
        } catch (\Throwable $e) {
            Log::error('Admin transaction reversal failed', [
                'transaction_id' => $transaction->id,
                'error'          => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'فشل عكس المعاملة'], 500);
        }
    }

    // ==================== Invoice ====================

    /** Standalone, print/PDF-ready invoice in the Damascene identity. */
    public function invoice(Transaction $transaction)
    {
        $transaction->load(['user', 'wallet']);

        return view('admin.invoices.transaction', compact('transaction'));
    }

    // ==================== Helpers ====================

    /**
     * Shared filter logic for index() and export() so the CSV always matches
     * exactly what the admin sees on screen.
     */
    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search): void {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q) use ($search): void {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }
    }
}
