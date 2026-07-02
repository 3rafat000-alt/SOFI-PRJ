<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Concerns\VerifiesTransactionAuth;
use App\Http\Controllers\Controller;
use App\Models\GoldPrice;
use App\Models\GoldWallet;
use App\Models\GoldTransaction;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Enums\TransactionType;
use App\Enums\TransactionCategory;
use App\Enums\TransactionStatus;
use App\Services\FeeService;
use App\Services\ExchangeRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GoldSavingsController extends Controller
{
    use VerifiesTransactionAuth;

    /**
     * Current USD→SYP rate for the audit trail column (usd_rate_at_time).
     * Fails soft to null — a missing rate must never block a gold trade.
     */
    private function currentUsdSypRate(): ?float
    {
        try {
            $rateData = app(ExchangeRateService::class)->getRate('USD', 'SYP');
            if ($rateData['success'] ?? false) {
                return (float) $rateData['rate'];
            }
        } catch (\Throwable $e) {
            Log::warning('Gold: failed to resolve USD/SYP rate for audit trail', ['error' => $e->getMessage()]);
        }
        return null;
    }

    /**
     * Precise valuation of a wallet's gold holdings: each karat's grams
     * priced at THAT karat's own active sell_price and summed — replaces
     * the old blended-average valuation, which mispriced mixed-karat
     * wallets (e.g. 1g 24k valued the same as 1g 18k).
     *
     * A karat holding whose price is not currently active contributes 0
     * to the value (documented choice — grams are still owned, but cannot
     * be marked-to-market without a live price; avoids fabricating a value
     * from a stale/disabled price row).
     *
     * @param GoldWallet $wallet Wallet with its `holdings` relation eager-loaded.
     * @param \Illuminate\Support\Collection $pricesByKarat GoldPrice::active() rows keyed by karat.
     */
    private function currentGoldValue(GoldWallet $wallet, $pricesByKarat): float
    {
        $value = 0.0;
        foreach ($wallet->holdings as $holding) {
            $price = $pricesByKarat->get($holding->karat);
            if (!$price) {
                continue;
            }
            $value += (float) $holding->balance_grams * (float) $price->sell_price;
        }
        return $value;
    }

    public function prices(): JsonResponse
    {
        $prices = GoldPrice::active()->get()->map(function ($price) {
            return [
                'karat' => $price->karat,
                'karat_label' => $price->karat_label,
                'purity' => $price->purity,
                'buy_price' => (float) $price->buy_price,
                'sell_price' => (float) $price->sell_price,
                'spread' => (float) $price->spread,
            ];
        });

        return response()->json(['data' => $prices]);
    }

    public function wallet(Request $request): JsonResponse
    {
        $user = $request->user();
        $goldWallet = GoldWallet::firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance_grams' => 0,
                'total_bought_grams' => 0,
                'total_sold_grams' => 0,
                'total_invested_usd' => 0,
                'current_value_usd' => 0,
            ]
        );

        $goldWallet->load('holdings');
        $prices = GoldPrice::active()->get()->keyBy('karat');
        $currentValue = $this->currentGoldValue($goldWallet, $prices);

        $breakdown = $prices->map(function ($price) {
            return [
                'karat' => $price->karat,
                'karat_label' => $price->karat_label,
                'buy_price' => (float) $price->buy_price,
                'sell_price' => (float) $price->sell_price,
            ];
        });

        $usdWallet = Wallet::where('user_id', $user->id)->where('currency', 'USD')->first();

        return response()->json([
            'data' => [
                'balance_grams' => (float) $goldWallet->balance_grams,
                'current_value_usd' => round($currentValue, 2),
                'total_invested_usd' => (float) $goldWallet->total_invested_usd,
                'total_bought_grams' => (float) $goldWallet->total_bought_grams,
                'total_sold_grams' => (float) $goldWallet->total_sold_grams,
                // Unrealized P/L only: current market value of remaining grams
                // minus the remaining (not-yet-sold) cost basis. total_invested_usd
                // is now reduced on every sell by the average cost of grams sold
                // (see GoldWallet::debitGrams), so this no longer double-counts.
                'profit_loss_usd' => round($currentValue - (float) $goldWallet->total_invested_usd, 2),
                'usd_balance' => $usdWallet ? (float) $usdWallet->available_balance : 0,
                'prices' => $breakdown->values(),
            ],
        ]);
    }

    public function buy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'karat' => 'required|string|in:24,22,21,18',
            'grams' => 'required|numeric|min:0.1',
            'pin' => 'required_without:biometric_token|string',
        ]);

        $user = $request->user();

        // Second factor (fail-closed): a valid PIN or a cryptographically verified
        // biometric signature. The old code let mere presence of `biometric_token`
        // skip the PIN (the client sent a hardcoded "biometric" string) — fixed (SEC C2).
        if (!$this->verifyTransactionFactor($request, $user)) {
            return response()->json(['message' => 'فشل التحقق الأمني — رمز PIN أو البصمة غير صحيح.'], 422);
        }

        $price = GoldPrice::active()->where('karat', $validated['karat'])->first();
        if (!$price) {
            return response()->json(['message' => 'سعر هذا العيار غير متاح حالياً'], 422);
        }

        $totalCost = round($validated['grams'] * $price->buy_price, 2);
        $feeResult = app(FeeService::class)->calculateGoldBuyFee($totalCost);
        $fee = $feeResult['fee'];
        $grandTotal = $totalCost + $fee;

        try {
            return DB::transaction(function () use ($user, $price, $validated, $totalCost, $fee, $grandTotal) {
            // Lock the USD wallet under pessimistic lock to prevent TOCTOU
            $usdWallet = Wallet::where('user_id', $user->id)
                ->where('currency', 'USD')
                ->lockForUpdate()
                ->first();

            if (!$usdWallet || (float) $usdWallet->available_balance < $grandTotal) {
                return response()->json(['message' => 'رصيد غير كافٍ في محفظة الدولار'], 422);
            }
            $goldWallet = GoldWallet::where('user_id', $user->id)->lockForUpdate()->first();
            if (!$goldWallet) {
                $goldWallet = GoldWallet::create([
                    'user_id' => $user->id,
                    'balance_grams' => 0,
                    'total_bought_grams' => 0,
                    'total_sold_grams' => 0,
                    'total_invested_usd' => 0,
                    'current_value_usd' => 0,
                ]);
            }

            $balanceBefore = (float) $usdWallet->balance;
            // FIX (frozen-wallet free gold): debit() returns false silently if
            // the wallet is frozen or funds are insufficient — must not proceed
            // to credit gold unless USD actually moved. Throwing rolls back the
            // whole DB::transaction (Transaction row + gold credit included).
            if (!$usdWallet->debit($grandTotal, 'شراء ذهب ' . $price->karat_label)) {
                throw new \RuntimeException('gold_buy_debit_failed');
            }

            $usdRate = $this->currentUsdSypRate();

            Transaction::create([
                'user_id' => $user->id,
                'wallet_id' => $usdWallet->id,
                'type' => TransactionType::WITHDRAWAL,
                'category' => TransactionCategory::INVESTMENT,
                'currency' => 'USD',
                'amount' => -$grandTotal,
                'fee' => $fee,
                'net_amount' => -$totalCost,
                'balance_before' => $balanceBefore,
                'balance_after' => (float) $usdWallet->balance,
                'status' => TransactionStatus::COMPLETED,
                'title' => 'شراء ذهب - ' . $price->karat_label,
                'description' => "شراء {$validated['grams']} غرام {$price->karat_label} بسعر \${$price->buy_price}/غرام",
                'metadata' => [
                    'gold_karat' => $validated['karat'],
                    'gold_grams' => $validated['grams'],
                    'gold_price_per_gram' => $price->buy_price,
                ],
            ]);

            $goldWallet->creditGrams($validated['grams'], $totalCost, $validated['karat']);

            $goldTx = GoldTransaction::create([
                'user_id' => $user->id,
                'gold_wallet_id' => $goldWallet->id,
                'type' => 'buy',
                'karat' => $validated['karat'],
                'grams' => $validated['grams'],
                'price_per_gram_usd' => $price->buy_price,
                'total_usd' => $totalCost,
                'fee_usd' => $fee,
                'usd_rate_at_time' => $usdRate,
                'status' => 'completed',
                'notes' => "شراء {$validated['grams']} غرام {$price->karat_label}",
            ]);

            return response()->json([
                'message' => 'تم شراء الذهب بنجاح',
                'data' => [
                    'reference' => $goldTx->reference,
                    'grams' => (float) $validated['grams'],
                    'karat' => $validated['karat'],
                    'total_paid_usd' => $grandTotal,
                    'fee_usd' => $fee,
                    'new_balance_grams' => (float) $goldWallet->fresh()->balance_grams,
                ],
            ]);
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'gold_buy_debit_failed') {
                return response()->json(['message' => 'تعذّر خصم المبلغ — المحفظة مجمّدة أو الرصيد غير كافٍ'], 422);
            }
            throw $e;
        }
    }

    public function sell(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'karat' => 'required|string|in:24,22,21,18',
            'grams' => 'required|numeric|min:0.1',
            'pin' => 'required_without:biometric_token|string',
        ]);

        $user = $request->user();

        // Second factor (fail-closed) — see SEC C2.
        if (!$this->verifyTransactionFactor($request, $user)) {
            return response()->json(['message' => 'فشل التحقق الأمني — رمز PIN أو البصمة غير صحيح.'], 422);
        }

        $price = GoldPrice::active()->where('karat', $validated['karat'])->first();
        if (!$price) {
            return response()->json(['message' => 'سعر هذا العيار غير متاح حالياً'], 422);
        }

        $totalRevenue = round($validated['grams'] * $price->sell_price, 2);
        $feeResult = app(FeeService::class)->calculateGoldSellFee($totalRevenue);
        $fee = $feeResult['fee'];
        $netAmount = $totalRevenue - $fee;

        // FIX (sell net<=0): if the fee eats the entire revenue (or more),
        // reject before any writes — otherwise gold gets debited but no USD
        // is credited (credit() silently no-ops on amount<=0).
        if ($netAmount <= 0) {
            return response()->json(['message' => 'صافي المبلغ بعد الرسوم غير كافٍ لإتمام البيع'], 422);
        }

        $usdWallet = Wallet::firstOrCreate(
            ['user_id' => $user->id, 'currency' => 'USD'],
            ['balance' => 0, 'available_balance' => 0]
        );

        try {
            return DB::transaction(function () use ($user, $price, $validated, $totalRevenue, $fee, $netAmount, $usdWallet) {
            // Lock the gold wallet under pessimistic lock
            $goldWallet = GoldWallet::where('user_id', $user->id)->lockForUpdate()->first();
            if (!$goldWallet) {
                return response()->json(['message' => 'رصيد ذهب غير كافٍ'], 422);
            }
            // Lock the USD wallet row — guaranteed to exist after pre-creation above
            $usdWallet = Wallet::where('user_id', $user->id)->where('currency', 'USD')->lockForUpdate()->first();

            // FIX (karat arbitrage): debit is scoped to the karat's own holding —
            // a user cannot buy 18k grams then sell declaring 24k at the higher
            // price. Rejects if THAT karat's balance is insufficient, even if the
            // wallet's other karats have enough grams.
            if (!$goldWallet->debitGrams($validated['grams'], $validated['karat'])) {
                return response()->json(['message' => 'رصيد ذهب غير كافٍ لهذا العيار'], 422);
            }

            $balanceBefore = (float) $usdWallet->balance;
            // FIX (sell net<=0 already guarded above); also check credit() itself —
            // if it still fails (e.g. wallet frozen) roll back the gold debit too.
            if (!$usdWallet->credit($netAmount, 'بيع ذهب ' . $price->karat_label)) {
                throw new \RuntimeException('gold_sell_credit_failed');
            }

            $usdRate = $this->currentUsdSypRate();

            Transaction::create([
                'user_id' => $user->id,
                'wallet_id' => $usdWallet->id,
                'type' => TransactionType::DEPOSIT,
                'category' => TransactionCategory::INVESTMENT,
                'currency' => 'USD',
                'amount' => $netAmount,
                'fee' => $fee,
                'net_amount' => $netAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => (float) $usdWallet->balance,
                'status' => TransactionStatus::COMPLETED,
                'title' => 'بيع ذهب - ' . $price->karat_label,
                'description' => "بيع {$validated['grams']} غرام {$price->karat_label} بسعر \${$price->sell_price}/غرام",
                'metadata' => [
                    'gold_karat' => $validated['karat'],
                    'gold_grams' => $validated['grams'],
                    'gold_price_per_gram' => $price->sell_price,
                ],
            ]);

            $goldTx = GoldTransaction::create([
                'user_id' => $user->id,
                'gold_wallet_id' => $goldWallet->id,
                'type' => 'sell',
                'karat' => $validated['karat'],
                'grams' => $validated['grams'],
                'price_per_gram_usd' => $price->sell_price,
                'total_usd' => $totalRevenue,
                'fee_usd' => $fee,
                'usd_rate_at_time' => $usdRate,
                'status' => 'completed',
                'notes' => "بيع {$validated['grams']} غرام {$price->karat_label}",
            ]);

            return response()->json([
                'message' => 'تم بيع الذهب بنجاح',
                'data' => [
                    'reference' => $goldTx->reference,
                    'grams' => (float) $validated['grams'],
                    'karat' => $validated['karat'],
                    'total_received_usd' => $netAmount,
                    'fee_usd' => $fee,
                    'new_balance_grams' => (float) $goldWallet->fresh()->balance_grams,
                ],
            ]);
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'gold_sell_credit_failed') {
                return response()->json(['message' => 'تعذّر إيداع المبلغ — المحفظة مجمّدة'], 422);
            }
            throw $e;
        }
    }

    public function transactions(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = GoldTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        if ($karat = $request->get('karat')) {
            $query->where('karat', $karat);
        }

        $perPage = min((int) $request->get('per_page', 20), 50);

        return response()->json(
            $query->paginate($perPage)->through(function ($tx) {
                return [
                    'reference' => $tx->reference,
                    'type' => $tx->type,
                    'karat' => $tx->karat,
                    'grams' => (float) $tx->grams,
                    'price_per_gram_usd' => (float) $tx->price_per_gram_usd,
                    'total_usd' => (float) $tx->total_usd,
                    'fee_usd' => (float) $tx->fee_usd,
                    'status' => $tx->status,
                    'created_at' => $tx->created_at->toIso8601String(),
                ];
            })
        );
    }

    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $goldWallet = GoldWallet::firstOrCreate(['user_id' => $user->id]);
        $goldWallet->load('holdings');

        $prices = GoldPrice::active()->get()->keyBy('karat');
        $currentValue = $this->currentGoldValue($goldWallet, $prices);

        $totalBought = GoldTransaction::where('user_id', $user->id)
            ->where('type', 'buy')->sum('total_usd');
        $totalSold = GoldTransaction::where('user_id', $user->id)
            ->where('type', 'sell')->sum('total_usd');
        $totalFees = GoldTransaction::where('user_id', $user->id)
            ->sum('fee_usd');
        $totalTransactions = GoldTransaction::where('user_id', $user->id)->count();

        $thisMonth = GoldTransaction::where('user_id', $user->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return response()->json([
            'data' => [
                'current_grams' => (float) $goldWallet->balance_grams,
                'current_value_usd' => round($currentValue, 2),
                'total_invested_usd' => (float) $goldWallet->total_invested_usd,
                'total_bought_usd' => round($totalBought, 2),
                'total_sold_usd' => round($totalSold, 2),
                'total_fees_paid_usd' => round($totalFees, 2),
                // P/L = realized + unrealized.
                //   cost_basis_of_sold_grams = totalBought - remainingInvested
                //     (remainingInvested = goldWallet->total_invested_usd, which is
                //     now correctly reduced by average cost basis on every sell —
                //     see GoldWallet::debitGrams).
                //   realized_pl   = totalSold - cost_basis_of_sold_grams
                //                 = totalSold - totalBought + remainingInvested
                //   unrealized_pl = currentValue - remainingInvested
                //   total = realized_pl + unrealized_pl
                //         = totalSold - totalBought + currentValue
                // (remainingInvested cancels out — kept as a comment, not code,
                // so the derivation stays auditable next to the one-line formula.)
                'profit_loss_usd' => round(($totalSold - $totalBought + $currentValue), 2),
                'total_transactions' => $totalTransactions,
                'this_month_transactions' => $thisMonth,
            ],
        ]);
    }
}
