<?php

namespace App\Services;

use App\Models\Fee;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * FeeService - Central fee calculation for SAKK Wallet
 * 
 * Handles all fee calculations for:
 * - Deposits (USDT via CCPayment)
 * - Withdrawals (USDT)
 * - Card funding (Stripe Virtual Cards)
 */
class FeeService
{
    // ==========================================
    // Deposit Fees
    // ==========================================

    /**
     * Calculate USDT deposit fee (CCPayment)
     */
    public function calculateDepositUsdtFee(float $amount): array
    {
        return $this->calculateFee(Fee::CODE_DEPOSIT_USDT, $amount);
    }

    // ==========================================
    // Withdrawal Fees
    // ==========================================

    /**
     * Calculate USDT withdrawal fee (CCPayment)
     */
    public function calculateWithdrawUsdtFee(float $amount): array
    {
        return $this->calculateFee(Fee::CODE_WITHDRAW_USDT, $amount);
    }

    // ==========================================
    // Card Fees
    // ==========================================

    /**
     * Calculate card funding fee
     */
    public function calculateCardFundFee(float $amount): array
    {
        return $this->calculateFee(Fee::CODE_CARD_FUND, $amount);
    }

    /**
     * Calculate card creation fee (one-time)
     */
    public function calculateCardCreationFee(): array
    {
        return $this->calculateFee(Fee::CODE_CARD_CREATION, 0);
    }

    // ==========================================
    // Gold Fees
    // ==========================================

    /**
     * Calculate gold buy fee
     */
    public function calculateGoldBuyFee(float $amount): array
    {
        return $this->calculateFee(Fee::CODE_GOLD_BUY, $amount);
    }

    /**
     * Calculate gold sell fee
     */
    public function calculateGoldSellFee(float $amount): array
    {
        return $this->calculateFee(Fee::CODE_GOLD_SELL, $amount);
    }

    // ==========================================
    // Generic Fee Calculation
    // ==========================================

    /**
     * Calculate fee by code
     */
    public function calculateFee(string $feeCode, float $amount): array
    {
        $fee = Fee::getByCode($feeCode);

        if (!$fee) {
            // Return zero fee if not configured — but flag it: a missing Fee
            // row silently letting gold trades through fee-free is a revenue
            // leak, not a legitimate zero-fee policy. Log, don't block.
            if (str_starts_with($feeCode, 'gold_')) {
                Log::warning('Gold: fee code not configured, resolving to 0', ['fee_code' => $feeCode, 'amount' => $amount]);
            }

            return [
                'success' => true,
                'fee_code' => $feeCode,
                'gross_amount' => $amount,
                'fee' => 0,
                'net_amount' => $amount,
                'currency' => 'USD',
                'message' => 'Fee not configured, no fee applied',
            ];
        }

        // Check amount limits
        if (!$fee->isAmountAllowed($amount)) {
            return [
                'success' => false,
                'fee_code' => $feeCode,
                'error' => 'amount_out_of_range',
                'message' => "المبلغ يجب أن يكون بين {$fee->min_amount} و " . ($fee->max_amount ?? '∞'),
                'min_amount' => $fee->min_amount,
                'max_amount' => $fee->max_amount,
            ];
        }

        $breakdown = $fee->getFeeBreakdown($amount);

        return [
            'success' => true,
            ...$breakdown,
        ];
    }

    /**
     * Calculate fee for deposit based on payment method and currency
     */
    public function calculateDepositFee(string $paymentMethod, string $currency, float $amount): array
    {
        $feeCode = match (true) {
            $paymentMethod === 'ccpayment' => Fee::CODE_DEPOSIT_USDT,
            default => "deposit_{$paymentMethod}_{$currency}",
        };

        return $this->calculateFee($feeCode, $amount);
    }

    /**
     * Calculate fee for withdrawal based on payment method and currency
     */
    public function calculateWithdrawalFee(string $paymentMethod, string $currency, float $amount): array
    {
        $feeCode = match (true) {
            $paymentMethod === 'ccpayment' => Fee::CODE_WITHDRAW_USDT,
            default => "withdraw_{$paymentMethod}_{$currency}",
        };

        return $this->calculateFee($feeCode, $amount);
    }

    // ==========================================
    // Admin Functions
    // ==========================================

    /**
     * Get all fees grouped by type
     */
    public function getAllFeesGrouped(): Collection
    {
        return Fee::orderBy('type')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('type');
    }

    /**
     * Get fees for a specific type
     */
    public function getFeesByType(string $type): Collection
    {
        return Fee::byType($type)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Update a fee
     */
    public function updateFee(string $code, array $data): ?Fee
    {
        $fee = Fee::where('code', $code)->first();
        
        if (!$fee) {
            return null;
        }

        $fee->update($data);
        Fee::clearCache($code);

        return $fee->fresh();
    }

    /**
     * Toggle fee active status
     */
    public function toggleFeeStatus(string $code): ?Fee
    {
        $fee = Fee::where('code', $code)->first();
        
        if (!$fee) {
            return null;
        }

        $fee->update(['is_active' => !$fee->is_active]);
        Fee::clearCache($code);

        return $fee->fresh();
    }

    // ==========================================
    // Fee Preview (for UI)
    // ==========================================

    /**
     * Get fee preview for all transaction types
     * Useful for showing users the fee structure
     */
    public function getFeePreview(float $sampleAmount = 100): array
    {
        $fees = Fee::active()->get();
        $preview = [];

        foreach ($fees as $fee) {
            $preview[$fee->code] = [
                'name_ar' => $fee->name_ar,
                'name_en' => $fee->name_en,
                'type' => $fee->type,
                'currency' => $fee->currency,
                'structure' => [
                    'fixed' => $fee->fixed_amount,
                    'percentage' => $fee->percentage == 0 ? '0%' : rtrim(rtrim((string) $fee->percentage, '0'), '.') . '%',
                    'min' => $fee->min_fee,
                    'max' => $fee->max_fee,
                ],
                'example' => [
                    'amount' => $sampleAmount,
                    'fee' => $fee->calculateFee($sampleAmount),
                    'net' => $sampleAmount - $fee->calculateFee($sampleAmount),
                ],
                'limits' => [
                    'min' => $fee->min_amount,
                    'max' => $fee->max_amount,
                ],
            ];
        }

        return $preview;
    }
}
