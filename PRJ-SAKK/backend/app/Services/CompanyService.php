<?php

namespace App\Services;

use App\Enums\TransactionCategory;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Company;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

/**
 * Company onboarding + funding (the non-payroll half of the company feature).
 * Payroll itself lives in PayrollService.
 */
class CompanyService
{
    private const CURRENCIES = ['USD', 'SYP'];

    /** Create a company application for a user (self-service). One per user. */
    public function apply(User $user, array $data): Company
    {
        if (Company::where('user_id', $user->id)->exists()) {
            throw new \RuntimeException('لديك شركة مسجّلة بالفعل');
        }

        return Company::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'legal_name' => $data['legal_name'] ?? null,
            'owner_name' => $data['owner_name'] ?? trim("{$user->first_name} {$user->last_name}"),
            'email' => $data['email'] ?? $user->email,
            'phone' => $data['phone'] ?? $user->phone,
            'tax_id' => $data['tax_id'] ?? null,
            'commercial_register' => $data['commercial_register'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'governorate' => $data['governorate'] ?? null,
            'is_active' => true,
            'kyc_status' => 'pending',
            'kyc_submitted_at' => now(),
        ]);
    }

    /**
     * Prefund the company wallet from the operator's personal wallet
     * (self-service top-up). Atomic, race-safe, same-currency.
     *
     * @throws \RuntimeException on validation/balance failures
     */
    public function topUpFromOperator(Company $company, User $operator, float $amount, string $currency): Wallet
    {
        $currency = strtoupper($currency);
        if (!in_array($currency, self::CURRENCIES, true)) {
            throw new \RuntimeException('العملة غير مدعومة');
        }
        if ($amount <= 0) {
            throw new \RuntimeException('المبلغ غير صالح');
        }

        return DB::transaction(function () use ($company, $operator, $amount, $currency) {
            $source = Wallet::where('user_id', $operator->id)
                ->where('currency', $currency)
                ->lockForUpdate()
                ->first();

            if (!$source) {
                throw new \RuntimeException("لا تملك محفظة {$currency}");
            }

            $target = Wallet::where('company_id', $company->id)
                ->where('currency', $currency)
                ->lockForUpdate()
                ->first();
            if (!$target) {
                $created = $company->companyWallet($currency);
                $target = Wallet::whereKey($created->id)->lockForUpdate()->first();
            }

            if ($source->is_frozen || $target->is_frozen) {
                throw new \RuntimeException('المحفظة مجمّدة');
            }
            if ((float) $source->available_balance < $amount) {
                throw new \RuntimeException('رصيدك لا يكفي للشحن');
            }

            $sourceBefore = (float) $source->balance;
            if (!$source->debit($amount)) {
                throw new \RuntimeException('رصيدك لا يكفي للشحن');
            }
            $targetBefore = (float) $target->balance;
            $target->credit($amount);

            Transaction::create([
                'user_id' => $operator->id,
                'wallet_id' => $source->id,
                'company_id' => $company->id,
                'type' => TransactionType::TRANSFER_OUT,
                'category' => TransactionCategory::PAYROLL,
                'currency' => $currency,
                'amount' => -$amount,
                'fee' => 0,
                'net_amount' => -$amount,
                'balance_before' => $sourceBefore,
                'balance_after' => (float) $source->balance,
                'status' => TransactionStatus::COMPLETED,
                'title' => 'شحن محفظة الشركة',
                'metadata' => ['source' => 'company_topup', 'company_id' => $company->id],
                'completed_at' => now(),
            ]);

            Transaction::create([
                'user_id' => $operator->id,
                'wallet_id' => $target->id,
                'company_id' => $company->id,
                'type' => TransactionType::DEPOSIT,
                'category' => TransactionCategory::PAYROLL,
                'currency' => $currency,
                'amount' => $amount,
                'fee' => 0,
                'net_amount' => $amount,
                'balance_before' => $targetBefore,
                'balance_after' => (float) $target->balance,
                'status' => TransactionStatus::COMPLETED,
                'title' => 'إيداع في محفظة الشركة',
                'metadata' => ['source' => 'company_topup', 'operator_id' => $operator->id],
                'completed_at' => now(),
            ]);

            return $target->fresh();
        });
    }
}
