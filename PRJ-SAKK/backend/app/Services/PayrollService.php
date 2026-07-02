<?php

namespace App\Services;

use App\Enums\TransactionCategory;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\CompanyEmployee;
use App\Models\PayrollBatch;
use App\Models\PayrollItem;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\Wallet;
use App\Support\LedgerHaltGuard;
use App\Support\PhoneNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Salary distribution (توزيع الرواتب).
 *
 * A company prefunds a dedicated wallet, then pays many employees in one batch.
 * Mirrors TransferService's atomic lock→debit→credit→ledger pattern, but:
 *   - the source is a COMPANY wallet (not a user wallet),
 *   - NO cashback, NO P2P limit coupling (payroll is its own type/category),
 *   - employees not yet registered+phone-verified get their salary HELD in the
 *     company wallet's pending_balance (via Wallet::hold) and released later by
 *     releaseHeldFor() when they activate.
 *
 * Idempotency: each PayrollItem carries a unique key and a status; processItem()
 * re-checks the status under a row lock, so a double-submit / job retry pays each
 * employee at most once. Each item commits in its OWN transaction — a mid-batch
 * failure leaves earlier payments intact (partial completion).
 */
class PayrollService
{
    /** Inline vs queued threshold — batches larger than this go to the queue. */
    public const INLINE_THRESHOLD = 20;

    private const CURRENCIES = ['USD', 'SYP'];

    /** Per-run memo caches to avoid redundant lookups across items in the same batch. */
    private array $companyCache = [];

    private array $batchCache = [];

    /**
     * Build a runnable batch from raw rows ([['phone','amount','name'?], ...]).
     * Idempotent on $idempotencyKey: a repeat returns the existing batch.
     *
     * @param array<int,array{phone:string,amount:int|float,name?:string}> $rows
     */
    public function createBatch(
        Company $company,
        string $currency,
        array $rows,
        ?User $creator = null,
        ?string $idempotencyKey = null,
        ?string $title = null,
    ): PayrollBatch {
        $currency = strtoupper($currency);
        if (!in_array($currency, self::CURRENCIES, true)) {
            throw new \RuntimeException('العملة غير مدعومة');
        }

        $idempotencyKey ??= (string) Str::uuid();

        // Idempotency: a repeated submit returns the already-created batch.
        $existing = PayrollBatch::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }

        $clean = $this->normalizeRows($rows, $currency);
        if (empty($clean)) {
            throw new \RuntimeException('لا يوجد موظفون صالحون في الدفعة');
        }

        return DB::transaction(function () use ($company, $currency, $clean, $creator, $idempotencyKey, $title) {
            $total = array_sum(array_column($clean, 'amount'));

            $batch = PayrollBatch::create([
                'company_id' => $company->id,
                'currency' => $currency,
                'status' => PayrollBatch::STATUS_PENDING,
                'idempotency_key' => $idempotencyKey,
                'total_amount' => $total,
                'items_count' => count($clean),
                'created_by' => $creator?->id,
                'title' => $title,
            ]);

            foreach ($clean as $row) {
                $employee = $this->upsertRosterEntry($company, $row, $currency);

                PayrollItem::create([
                    'payroll_batch_id' => $batch->id,
                    'company_id' => $company->id,
                    'company_employee_id' => $employee?->id,
                    'employee_phone' => $row['phone'],
                    'employee_name' => $row['name'] ?? $employee?->name,
                    'currency' => $currency,
                    'amount' => $row['amount'],
                    'status' => PayrollItem::STATUS_PENDING,
                    'idempotency_key' => $batch->id . ':' . $row['phone'],
                ]);
            }

            return $batch;
        });
    }

    /**
     * Run a batch INLINE: gate → preflight funding → process every pending item.
     * Safe to re-run (only pending items are touched). Returns the fresh batch.
     */
    public function run(PayrollBatch $batch): PayrollBatch
    {
        $this->assertRunnable($batch);

        $batch->forceFill([
            'status' => PayrollBatch::STATUS_PROCESSING,
            'processed_at' => $batch->processed_at ?? now(),
        ])->save();

        $this->assertFunded($batch);

        $batch->loadMissing('company');
        $pending = $batch->items()->where('status', PayrollItem::STATUS_PENDING)->get();

        // Perf: resolve every payee (registered + phone-verified User) in ONE
        // query up front instead of one `resolvePayableUser()` query per item
        // inside the loop (was ~N queries for an N-item batch). This is a pure
        // read-path optimization — processItem() still re-checks status under
        // its own row lock and runs its own per-item transaction, unchanged.
        $payeesByPhone = $this->resolvePayableUsersFor($pending);

        foreach ($pending as $item) {
            $this->processItem($item, $payeesByPhone[$item->employee_phone] ?? false);
        }

        return $this->finalize($batch);
    }

    /**
     * Entry point used by the portal: gate + preflight synchronously (so the
     * operator gets immediate "insufficient funds" feedback), then run inline for
     * small batches or hand off to the queue for large ones.
     */
    public function dispatchBatch(PayrollBatch $batch): PayrollBatch
    {
        $pendingCount = $batch->items()->where('status', PayrollItem::STATUS_PENDING)->count();

        if ($pendingCount <= self::INLINE_THRESHOLD) {
            return $this->run($batch);
        }

        $this->assertRunnable($batch);
        $batch->forceFill([
            'status' => PayrollBatch::STATUS_PROCESSING,
            'processed_at' => $batch->processed_at ?? now(),
        ])->save();
        $this->assertFunded($batch);

        \App\Jobs\ProcessPayrollBatchJob::dispatch($batch->id);

        return $batch->fresh();
    }

    /**
     * Process ONE item atomically and idempotently. Used by both the inline path
     * and PayEmployeeJob. Re-checks item status under a lock; registered+verified
     * employees are paid immediately, everyone else is HELD.
     *
     * @param User|false|null $preloadedPayee Optional perf hint from run()'s
     *   batch payee lookup: a resolved User, or `false` meaning "already looked
     *   up, no match". Pass null (default) to resolve individually — this is
     *   the path PayEmployeeJob and direct/test callers use.
     */
    public function processItem(PayrollItem $item, User|false|null $preloadedPayee = null): string
    {
        LedgerHaltGuard::assertNotHalted();

        $outcome = DB::transaction(function () use ($item, $preloadedPayee) {
            /** @var PayrollItem $item */
            $item = PayrollItem::whereKey($item->id)->lockForUpdate()->first();
            if (!$item || $item->status !== PayrollItem::STATUS_PENDING) {
                return $item?->status ?? PayrollItem::STATUS_CANCELLED; // already handled
            }

            $companyWallet = Wallet::where('company_id', $item->company_id)
                ->where('currency', $item->currency)
                ->lockForUpdate()
                ->first();

            if (!$companyWallet || $companyWallet->is_frozen || !$companyWallet->is_active) {
                return $this->failItem($item, 'محفظة الشركة غير متاحة');
            }

            $amount = (float) $item->amount;
            $payee = $preloadedPayee === null
                ? $this->resolvePayableUser($item->employee_phone)
                : ($preloadedPayee ?: null);

            if ($payee) {
                return $this->payRegistered($item, $companyWallet, $payee, $amount);
            }

            // Unregistered / unverified — reserve the salary in pending_balance.
            // Guard: ensure enough free balance exists after existing holds.
            if (($companyWallet->balance - $companyWallet->pending_balance) < $amount
                || (float) $companyWallet->available_balance < $amount
                || !$companyWallet->hold($amount)) {
                return $this->failItem($item, 'رصيد الشركة غير كافٍ');
            }
            $item->forceFill([
                'status' => PayrollItem::STATUS_HELD,
                'held_at' => now(),
            ])->save();

            return PayrollItem::STATUS_HELD;
        });

        // Side effects outside the money transaction (never roll money back on a
        // notification failure).
        $this->notifyForOutcome($item->fresh(), $outcome);

        return $outcome;
    }

    /**
     * Release every held salary for a user who just became phone-verified.
     * Idempotent: re-checks each item is still `held` under a lock. Returns the
     * number of items released.
     */
    public function releaseHeldFor(User $user): int
    {
        if (!$user->phone || !$user->phone_verified_at) {
            return 0;
        }

        $canonical = PhoneNormalizer::canonical($user->phone);
        if ($canonical === '') {
            return 0;
        }

        $items = PayrollItem::where('status', PayrollItem::STATUS_HELD)
            ->where('employee_phone', $canonical)
            ->get();

        $released = 0;
        foreach ($items as $heldItem) {
            $ok = DB::transaction(function () use ($heldItem, $user) {
                $item = PayrollItem::whereKey($heldItem->id)->lockForUpdate()->first();
                if (!$item || $item->status !== PayrollItem::STATUS_HELD) {
                    return false; // already released by a concurrent verify
                }

                $companyWallet = Wallet::where('company_id', $item->company_id)
                    ->where('currency', $item->currency)
                    ->lockForUpdate()
                    ->first();

                $amount = (float) $item->amount;
                if (!$companyWallet || (float) $companyWallet->pending_balance < $amount) {
                    return false; // reconciliation drift — leave held for review
                }

                $employeeWallet = $this->lockOrCreateUserWallet($user, $item->currency);
                if ($employeeWallet->is_frozen) {
                    return false; // can't deliver yet; keep held
                }

                // Capture the reserved funds out of the company wallet, deliver.
                $companyBefore = (float) $companyWallet->balance;
                if (!$companyWallet->capture($amount)) {
                    return false;
                }
                $employeeBefore = (float) $employeeWallet->balance;
                $employeeWallet->credit($amount);

                $this->recordLedgerPair($item, $companyWallet, $companyBefore, $employeeWallet, $employeeBefore, $user, $amount);

                $item->forceFill([
                    'status' => PayrollItem::STATUS_PAID,
                    'employee_user_id' => $user->id,
                    'paid_at' => now(),
                ])->save();

                $this->bumpBatchCounters($item->payroll_batch_id, paid: 1, held: -1);

                return true;
            });

            if ($ok) {
                $released++;
                $this->linkRosterUser($heldItem->company_id, $canonical, $user->id);
                $this->notifyEmployeePaid($user, $heldItem->fresh());
            }
        }

        // A released item may have flipped a partially_completed batch to complete.
        foreach ($items->pluck('payroll_batch_id')->unique() as $batchId) {
            if ($batch = PayrollBatch::find($batchId)) {
                $this->finalize($batch);
            }
        }

        return $released;
    }

    /**
     * Return salary held longer than $days back to the company's available
     * balance and mark those items cancelled (the invitee never registered).
     * Idempotent + atomic per item. Returns the number of items expired.
     */
    public function expireHeldOlderThan(int $days = 30): int
    {
        $cutoff = now()->subDays($days);
        $stale = PayrollItem::where('status', PayrollItem::STATUS_HELD)
            ->where('held_at', '<', $cutoff)
            ->get();

        $expired = 0;
        foreach ($stale as $heldItem) {
            $ok = DB::transaction(function () use ($heldItem) {
                $item = PayrollItem::whereKey($heldItem->id)->lockForUpdate()->first();
                if (!$item || $item->status !== PayrollItem::STATUS_HELD) {
                    return false;
                }
                $wallet = Wallet::where('company_id', $item->company_id)
                    ->where('currency', $item->currency)
                    ->lockForUpdate()
                    ->first();
                if ($wallet && (float) $wallet->pending_balance >= (float) $item->amount) {
                    $wallet->release((float) $item->amount); // pending → available
                }
                $item->forceFill([
                    'status' => PayrollItem::STATUS_CANCELLED,
                    'failure_reason' => 'انتهت مهلة الاستحقاق — لم يُسجّل الموظف',
                ])->save();
                $this->bumpBatchCounters($item->payroll_batch_id, held: -1);

                return true;
            });
            if ($ok) {
                $expired++;
                $this->notifyHeldExpired($heldItem);
            }
        }

        foreach ($stale->pluck('payroll_batch_id')->unique() as $batchId) {
            if ($batch = PayrollBatch::find($batchId)) {
                $this->finalize($batch);
            }
        }

        return $expired;
    }

    // ==================== Internals ====================

    /** Pay a registered, phone-verified employee immediately. */
    private function payRegistered(PayrollItem $item, Wallet $companyWallet, User $payee, float $amount): string
    {
        if ((float) $companyWallet->available_balance < $amount) {
            return $this->failItem($item, 'رصيد الشركة غير كافٍ');
        }

        $employeeWallet = $this->lockOrCreateUserWallet($payee, $item->currency);
        if ($employeeWallet->is_frozen) {
            return $this->failItem($item, 'محفظة الموظف مجمّدة');
        }

        $companyBefore = (float) $companyWallet->balance;
        if (!$companyWallet->debit($amount)) {
            return $this->failItem($item, 'رصيد الشركة غير كافٍ');
        }
        $employeeBefore = (float) $employeeWallet->balance;
        $employeeWallet->credit($amount);

        $this->recordLedgerPair($item, $companyWallet, $companyBefore, $employeeWallet, $employeeBefore, $payee, $amount);

        $item->forceFill([
            'status' => PayrollItem::STATUS_PAID,
            'employee_user_id' => $payee->id,
            'paid_at' => now(),
        ])->save();

        $this->linkRosterUser($item->company_id, $item->employee_phone, $payee->id);

        return PayrollItem::STATUS_PAID;
    }

    /** Write the company-side PAYROLL_OUT + employee-side SALARY_IN ledger rows. */
    private function recordLedgerPair(
        PayrollItem $item,
        Wallet $companyWallet,
        float $companyBefore,
        Wallet $employeeWallet,
        float $employeeBefore,
        User $payee,
        float $amount,
    ): void {
        $company = $this->companyCache[$item->company_id]
            ??= Company::find($item->company_id);
        $batch = $this->batchCache[$item->payroll_batch_id]
            ??= PayrollBatch::find($item->payroll_batch_id);
        $companyName = $company?->name ?? 'شركة';
        // Operator the company-side row is attributed to (transactions.user_id is
        // NOT NULL); company_id carries the real attribution.
        $operatorId = $batch?->created_by ?? $company?->user_id ?? $payee->id;

        $out = Transaction::create([
            'user_id' => $operatorId,
            'wallet_id' => $companyWallet->id,
            'company_id' => $company?->id,
            'type' => TransactionType::PAYROLL_OUT,
            'category' => TransactionCategory::PAYROLL,
            'currency' => $item->currency,
            'amount' => -$amount,
            'fee' => 0,
            'net_amount' => -$amount,
            'balance_before' => $companyBefore,
            'balance_after' => (float) $companyWallet->balance,
            'status' => TransactionStatus::COMPLETED,
            'title' => 'دفع راتب إلى ' . ($item->employee_name ?: $item->employee_phone),
            'metadata' => [
                'source' => 'payroll',
                'batch_id' => $item->payroll_batch_id,
                'payroll_item_id' => $item->id,
                'employee_phone' => $item->employee_phone,
            ],
            'completed_at' => now(),
        ]);

        $in = Transaction::create([
            'user_id' => $payee->id,
            'wallet_id' => $employeeWallet->id,
            'company_id' => $company?->id,
            'type' => TransactionType::SALARY_IN,
            'category' => TransactionCategory::PAYROLL,
            'currency' => $item->currency,
            'amount' => $amount,
            'fee' => 0,
            'net_amount' => $amount,
            'balance_before' => $employeeBefore,
            'balance_after' => (float) $employeeWallet->balance,
            'status' => TransactionStatus::COMPLETED,
            'title' => 'راتب من ' . $companyName,
            'metadata' => [
                'source' => 'payroll',
                'batch_id' => $item->payroll_batch_id,
                'company_name' => $companyName,
            ],
            'completed_at' => now(),
        ]);

        $item->forceFill(['transaction_id' => $in->id])->save();

        ActivityLog::log(
            'payroll.paid',
            entity: $out,
            newValues: [
                'company_id' => $company?->id,
                'employee_user_id' => $payee->id,
                'amount' => $amount,
                'currency' => $item->currency,
            ],
            description: "Payroll {$amount} {$item->currency} to {$payee->id}",
        );
    }

    private function failItem(PayrollItem $item, string $reason): string
    {
        $item->forceFill([
            'status' => PayrollItem::STATUS_FAILED,
            'failure_reason' => $reason,
        ])->save();

        return PayrollItem::STATUS_FAILED;
    }

    /** Recompute rollups + final status from the item rows. */
    public function finalize(PayrollBatch $batch): PayrollBatch
    {
        $counts = $batch->items()
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $paid = (int) ($counts[PayrollItem::STATUS_PAID] ?? 0);
        $held = (int) ($counts[PayrollItem::STATUS_HELD] ?? 0);
        $failed = (int) ($counts[PayrollItem::STATUS_FAILED] ?? 0);
        $pending = (int) ($counts[PayrollItem::STATUS_PENDING] ?? 0);

        $status = match (true) {
            $pending > 0 => PayrollBatch::STATUS_PROCESSING,
            $failed > 0 && $paid === 0 && $held === 0 => PayrollBatch::STATUS_FAILED,
            $failed > 0 || $held > 0 => PayrollBatch::STATUS_PARTIAL,
            default => PayrollBatch::STATUS_COMPLETED,
        };

        $batch->forceFill([
            'paid_count' => $paid,
            'held_count' => $held,
            'failed_count' => $failed,
            'status' => $status,
            'completed_at' => $pending === 0 ? ($batch->completed_at ?? now()) : null,
        ])->save();

        return $batch->fresh();
    }

    private function assertRunnable(PayrollBatch $batch): void
    {
        $company = $batch->company;
        if (!$company || !$company->canRunPayroll()) {
            throw new \RuntimeException('الشركة غير مفعّلة لتوزيع الرواتب');
        }
        if (!$batch->isRunnable()) {
            throw new \RuntimeException('لا يمكن تشغيل هذه الدفعة في حالتها الحالية');
        }
    }

    /** Preflight: the company wallet must cover all still-pending items. */
    private function assertFunded(PayrollBatch $batch): void
    {
        $needed = (float) $batch->items()
            ->where('status', PayrollItem::STATUS_PENDING)
            ->sum('amount');

        if ($needed <= 0) {
            return;
        }

        $wallet = Wallet::where('company_id', $batch->company_id)
            ->where('currency', $batch->currency)
            ->first();

        if (!$wallet || (float) $wallet->available_balance < $needed) {
            throw new \RuntimeException('رصيد محفظة الشركة لا يكفي لتغطية الدفعة');
        }
    }

    /** Resolve a registered + phone-verified user for a canonical phone, or null. */
    private function resolvePayableUser(string $canonicalPhone): ?User
    {
        $variants = PhoneNormalizer::variants($canonicalPhone);
        if (empty($variants)) {
            return null;
        }

        return User::whereIn('phone', $variants)
            ->whereNotNull('phone_verified_at')
            ->first();
    }

    /**
     * Batch equivalent of resolvePayableUser(): one query for every distinct
     * phone in $items instead of one query per item. Returns a map keyed by
     * the item's OWN employee_phone value → resolved User (only for matches;
     * misses are simply absent, callers should `?? false` on lookup miss to
     * distinguish "not looked up" from "looked up, no match").
     *
     * @param \Illuminate\Support\Collection<int,PayrollItem> $items
     * @return array<string,User|false>
     */
    private function resolvePayableUsersFor($items): array
    {
        $phones = $items->pluck('employee_phone')->unique()->filter()->values();
        if ($phones->isEmpty()) {
            return [];
        }

        $variantsByPhone = [];
        $allVariants = [];
        foreach ($phones as $phone) {
            $variants = PhoneNormalizer::variants($phone);
            $variantsByPhone[$phone] = $variants;
            foreach ($variants as $variant) {
                $allVariants[$variant] = true;
            }
        }

        if (empty($allVariants)) {
            return [];
        }

        $users = User::whereIn('phone', array_keys($allVariants))
            ->whereNotNull('phone_verified_at')
            ->get()
            ->keyBy('phone');

        $result = [];
        foreach ($variantsByPhone as $phone => $variants) {
            $match = false;
            foreach ($variants as $variant) {
                if ($users->has($variant)) {
                    $match = $users->get($variant);
                    break;
                }
            }
            $result[$phone] = $match;
        }

        return $result;
    }

    private function lockOrCreateUserWallet(User $user, string $currency): Wallet
    {
        $wallet = Wallet::where('user_id', $user->id)
            ->where('currency', $currency)
            ->lockForUpdate()
            ->first();

        if (!$wallet) {
            $created = $user->wallets()->create(['currency' => $currency, 'is_default' => false]);
            $wallet = Wallet::whereKey($created->id)->lockForUpdate()->first();
        }

        return $wallet;
    }

    /**
     * @param array<int,array{phone:string,amount:int|float,name?:string}> $rows
     * @return array<int,array{phone:string,amount:float,name:?string}>
     */
    private function normalizeRows(array $rows, string $currency): array
    {
        $byPhone = [];
        foreach ($rows as $row) {
            $phone = PhoneNormalizer::canonical($row['phone'] ?? '');
            $amount = (float) ($row['amount'] ?? 0);
            if ($phone === '' || $amount <= 0) {
                continue; // skip junk rows
            }
            // Last write wins per phone (dedupe within a batch).
            $byPhone[$phone] = [
                'phone' => $phone,
                'amount' => $amount,
                'name' => isset($row['name']) ? trim((string) $row['name']) : ($byPhone[$phone]['name'] ?? null),
            ];
        }

        return array_values($byPhone);
    }

    private function upsertRosterEntry(Company $company, array $row, string $currency): ?CompanyEmployee
    {
        return CompanyEmployee::updateOrCreate(
            ['company_id' => $company->id, 'phone' => $row['phone']],
            [
                'name' => $row['name'] ?? null,
                'default_amount' => $row['amount'],
                'default_currency' => $currency,
                'is_active' => true,
            ],
        );
    }

    private function linkRosterUser(int $companyId, string $canonicalPhone, int $userId): void
    {
        CompanyEmployee::where('company_id', $companyId)
            ->where('phone', $canonicalPhone)
            ->whereNull('employee_user_id')
            ->update(['employee_user_id' => $userId, 'status' => 'active']);
    }

    private function bumpBatchCounters(int $batchId, int $paid = 0, int $held = 0, int $failed = 0): void
    {
        $batch = PayrollBatch::find($batchId);
        if (!$batch) {
            return;
        }
        $batch->forceFill([
            'paid_count' => max(0, $batch->paid_count + $paid),
            'held_count' => max(0, $batch->held_count + $held),
            'failed_count' => max(0, $batch->failed_count + $failed),
        ])->save();
    }

    // ==================== Notifications ====================

    private function notifyForOutcome(?PayrollItem $item, string $outcome): void
    {
        if (!$item) {
            return;
        }
        if ($outcome === PayrollItem::STATUS_PAID && $item->employee_user_id) {
            if ($user = User::find($item->employee_user_id)) {
                $this->notifyEmployeePaid($user, $item);
            }
        }
        if ($outcome === PayrollItem::STATUS_HELD) {
            $this->inviteHeldEmployee($item);
        }
    }

    /**
     * Invite an unregistered employee (held salary) to register so their salary
     * is released. Delivered over the OpenWA WhatsApp gateway; a safe no-op when
     * the gateway is disabled. Never throws.
     */
    private function inviteHeldEmployee(?PayrollItem $item): void
    {
        if (!$item || !$item->employee_phone) {
            return;
        }
        try {
            $company = Company::find($item->company_id);
            $companyName = $company?->name ?? 'شركة';
            $formatted = \App\Support\Money::format((float) $item->amount, $item->currency);
            $link = rtrim((string) config('app.url'), '/');

            $text = "مرحباً 👋\n{$companyName} حوّلت لك راتباً بقيمة {$formatted} عبر SAKK."
                . "\nحمّل تطبيق SAKK وسجّل بهذا الرقم لاستلام راتبك: {$link}";

            app(\App\Services\WhatsAppService::class)->sendText($item->employee_phone, $text);
        } catch (\Throwable $e) {
            logger()->warning('Payroll held-invite failed: ' . $e->getMessage());
        }
    }

    private function notifyHeldExpired(PayrollItem $item): void
    {
        $company = Company::find($item->company_id);
        if (!$company || !$company->user_id) {
            return;
        }
        $formatted = \App\Support\Money::format((float) $item->amount, $item->currency);
        $title = 'صلاحية الراتب منتهية';
        $body = "تم إلغاء راتب بقيمة {$formatted} للموظف {$item->employee_name} لعدم تسجيله";

        try {
            UserNotification::create([
                'user_id' => $company->user_id,
                'uuid' => Str::uuid(),
                'template_code' => 'held_salary_expired',
                'channel' => 'in_app',
                'title' => $title,
                'body' => $body,
                'data' => [
                    'type' => 'held_expired',
                    'amount' => (float) $item->amount,
                    'currency' => $item->currency,
                    'company_id' => $company->id,
                    'employee_phone' => $item->employee_phone,
                    'employee_name' => $item->employee_name,
                ],
                'sent_at' => now(),
                'status' => 'sent',
            ]);
        } catch (\Throwable $e) {
            logger()->warning('Payroll held-expired notify failed: ' . $e->getMessage());
        }
    }

    private function notifyEmployeePaid(User $user, ?PayrollItem $item): void
    {
        if (!$item) {
            return;
        }
        $company = Company::find($item->company_id);
        $companyName = $company?->name ?? 'شركة';
        $formatted = \App\Support\Money::format((float) $item->amount, $item->currency);

        $title = 'وصل راتبك';
        $body = "استلمت راتباً بقيمة {$formatted} من {$companyName}";

        try {
            UserNotification::create([
                'user_id' => $user->id,
                'uuid' => Str::uuid(),
                'template_code' => 'salary_received',
                'channel' => 'in_app',
                'title' => $title,
                'body' => $body,
                'data' => [
                    'type' => 'salary_in',
                    'amount' => (float) $item->amount,
                    'currency' => $item->currency,
                    'company_name' => $companyName,
                ],
                'sent_at' => now(),
                'status' => 'sent',
            ]);

            app(\App\Services\FCMService::class)->send(
                $user->fcm_token,
                $title,
                $body,
                ['type' => 'salary_in', 'currency' => $item->currency, 'amount' => (string) $item->amount],
            );
        } catch (\Throwable $e) {
            logger()->error('Payroll notify failed: ' . $e->getMessage());
        }
    }
}
