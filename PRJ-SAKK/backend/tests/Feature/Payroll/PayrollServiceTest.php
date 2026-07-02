<?php

use App\Models\Company;
use App\Models\PayrollBatch;
use App\Models\PayrollItem;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\KycService;
use App\Services\PayrollService;
use App\Support\PhoneNormalizer;

function payrollCompany(): Company
{
    return Company::factory()->payrollReady()->create();
}

function fundCompany(Company $company, string $currency, float $amount): Wallet
{
    $wallet = $company->companyWallet($currency);
    $wallet->forceFill(['balance' => $amount, 'available_balance' => $amount])->save();

    return $wallet->fresh();
}

function verifiedEmployee(string $phone): User
{
    return User::factory()->create([
        'phone' => $phone,
        'phone_verified_at' => now(),
    ]);
}

function svc(): PayrollService
{
    return app(PayrollService::class);
}

it('pays a registered employee and holds an unregistered one', function () {
    $company = payrollCompany();
    fundCompany($company, 'USD', 1000);

    $employee = verifiedEmployee('0982111111');

    $batch = svc()->createBatch($company, 'USD', [
        ['phone' => '0982111111', 'amount' => 100, 'name' => 'Registered'],
        ['phone' => '0983222222', 'amount' => 50, 'name' => 'Unregistered'],
    ]);

    $batch = svc()->run($batch);

    expect($batch->status)->toBe(PayrollBatch::STATUS_PARTIAL);
    expect($batch->paid_count)->toBe(1);
    expect($batch->held_count)->toBe(1);

    // Registered employee actually received the money.
    $empWallet = Wallet::where('user_id', $employee->id)->where('currency', 'USD')->first();
    expect((float) $empWallet->balance)->toBe(100.0);

    // Company: 100 paid out, 50 reserved (held), 850 still available.
    $coWallet = $company->companyWallet('USD')->fresh();
    expect((float) $coWallet->balance)->toBe(900.0);
    expect((float) $coWallet->pending_balance)->toBe(50.0);
    expect((float) $coWallet->available_balance)->toBe(850.0);

    // Reconciliation invariant: held sum == company pending_balance.
    $heldSum = (float) PayrollItem::where('company_id', $company->id)
        ->where('status', PayrollItem::STATUS_HELD)->sum('amount');
    expect($heldSum)->toBe((float) $coWallet->pending_balance);

    // Ledger pair written for the paid item.
    expect(Transaction::where('type', 'salary_in')->where('user_id', $employee->id)->count())->toBe(1);
    expect(Transaction::where('type', 'payroll_out')->where('company_id', $company->id)->count())->toBe(1);
});

it('releases held salary when the employee verifies their phone', function () {
    $company = payrollCompany();
    fundCompany($company, 'USD', 1000);

    $batch = svc()->createBatch($company, 'USD', [
        ['phone' => '0983222222', 'amount' => 75, 'name' => 'Future Hire'],
    ]);
    svc()->run($batch);

    expect($batch->fresh()->held_count)->toBe(1);
    $coWallet = $company->companyWallet('USD')->fresh();
    expect((float) $coWallet->pending_balance)->toBe(75.0);

    // The employee now registers with that phone and verifies it.
    $user = User::factory()->create(['phone' => '0983222222', 'phone_verified_at' => null]);
    // Simulate the KYC phone-verify hook path.
    $user->forceFill(['phone_verified_at' => now()])->save();
    $released = svc()->releaseHeldFor($user);

    expect($released)->toBe(1);

    $empWallet = Wallet::where('user_id', $user->id)->where('currency', 'USD')->first();
    expect((float) $empWallet->balance)->toBe(75.0);

    $coWallet = $company->companyWallet('USD')->fresh();
    expect((float) $coWallet->pending_balance)->toBe(0.0);
    expect((float) $coWallet->balance)->toBe(925.0); // 1000 - 75 captured
    // T-008 invariant: capture restored available_balance (pending → available).
    expect((float) $coWallet->available_balance)->toBe(1000.0);

    $item = PayrollItem::where('company_id', $company->id)->first();
    expect($item->status)->toBe(PayrollItem::STATUS_PAID);
    expect($item->employee_user_id)->toBe($user->id);
    expect($batch->fresh()->status)->toBe(PayrollBatch::STATUS_COMPLETED);
});

it('is idempotent: re-processing a paid item never double-pays', function () {
    $company = payrollCompany();
    fundCompany($company, 'USD', 1000);
    $employee = verifiedEmployee('0982111111');

    $batch = svc()->createBatch($company, 'USD', [
        ['phone' => '0982111111', 'amount' => 200],
    ]);

    svc()->run($batch);

    // Re-processing the same (already paid) item is a no-op — the status recheck
    // under lock prevents a second payment on retry / duplicate job.
    $item = PayrollItem::where('payroll_batch_id', $batch->id)->first();
    svc()->processItem($item);
    svc()->processItem($item->fresh());

    $empWallet = Wallet::where('user_id', $employee->id)->where('currency', 'USD')->first();
    expect((float) $empWallet->balance)->toBe(200.0); // paid exactly once
    expect(Transaction::where('type', 'salary_in')->where('user_id', $employee->id)->count())->toBe(1);

    // A COMPLETED batch cannot be naively re-run (only PARTIAL/PENDING retries).
    expect(fn () => svc()->run($batch->fresh()))->toThrow(RuntimeException::class);
});

it('createBatch is idempotent on the idempotency key', function () {
    $company = payrollCompany();
    $key = 'fixed-key-123';

    $a = svc()->createBatch($company, 'USD', [['phone' => '0982111111', 'amount' => 10]], null, $key);
    $b = svc()->createBatch($company, 'USD', [['phone' => '0982111111', 'amount' => 10]], null, $key);

    expect($b->id)->toBe($a->id);
    expect(PayrollBatch::count())->toBe(1);
    expect(PayrollItem::where('payroll_batch_id', $a->id)->count())->toBe(1);
});

it('supports SYP batches', function () {
    $company = payrollCompany();
    fundCompany($company, 'SYP', 5_000_000);
    $employee = verifiedEmployee('0982444444');

    $batch = svc()->createBatch($company, 'SYP', [
        ['phone' => '0982444444', 'amount' => 1_300_000],
    ]);
    $batch = svc()->run($batch);

    expect($batch->status)->toBe(PayrollBatch::STATUS_COMPLETED);
    $empWallet = Wallet::where('user_id', $employee->id)->where('currency', 'SYP')->first();
    expect((float) $empWallet->balance)->toBe(1_300_000.0);
});

it('fails a frozen-wallet item but pays the rest (partial completion)', function () {
    $company = payrollCompany();
    fundCompany($company, 'USD', 1000);

    $ok = verifiedEmployee('0982111111');
    $frozen = verifiedEmployee('0982555555');
    Wallet::where('user_id', $frozen->id)->where('currency', 'USD')
        ->update(['is_frozen' => true]);

    $batch = svc()->createBatch($company, 'USD', [
        ['phone' => '0982111111', 'amount' => 100],
        ['phone' => '0982555555', 'amount' => 100],
    ]);
    $batch = svc()->run($batch);

    expect($batch->status)->toBe(PayrollBatch::STATUS_PARTIAL);
    expect($batch->paid_count)->toBe(1);
    expect($batch->failed_count)->toBe(1);

    expect((float) Wallet::where('user_id', $ok->id)->where('currency', 'USD')->value('balance'))->toBe(100.0);
    // Frozen employee got nothing; company only debited the one good payment.
    expect((float) $company->companyWallet('USD')->fresh()->balance)->toBe(900.0);
});

it('blocks payroll when the company is not enabled (gate)', function () {
    $company = Company::factory()->create(); // not payrollReady
    fundCompany($company, 'USD', 1000);
    verifiedEmployee('0982111111');

    $batch = svc()->createBatch($company, 'USD', [['phone' => '0982111111', 'amount' => 100]]);

    expect(fn () => svc()->run($batch))
        ->toThrow(RuntimeException::class);
});

it('rejects an underfunded batch before paying anyone', function () {
    $company = payrollCompany();
    fundCompany($company, 'USD', 50);
    $employee = verifiedEmployee('0982111111');

    $batch = svc()->createBatch($company, 'USD', [['phone' => '0982111111', 'amount' => 100]]);

    expect(fn () => svc()->run($batch))->toThrow(RuntimeException::class);

    // Nobody was paid.
    expect((float) Wallet::where('user_id', $employee->id)->where('currency', 'USD')->value('balance'))->toBe(0.0);
});

it('dedupes employees within a batch and skips junk rows', function () {
    $company = payrollCompany();

    $batch = svc()->createBatch($company, 'USD', [
        ['phone' => '0982111111', 'amount' => 100],
        ['phone' => '+963982111111', 'amount' => 150], // same canonical → last wins
        ['phone' => '', 'amount' => 99],               // junk: no phone
        ['phone' => '0983000000', 'amount' => 0],      // junk: zero amount
    ]);

    expect(PayrollItem::where('payroll_batch_id', $batch->id)->count())->toBe(1);
    $item = PayrollItem::where('payroll_batch_id', $batch->id)->first();
    expect((float) $item->amount)->toBe(150.0);
    expect($item->employee_phone)->toBe(PhoneNormalizer::canonical('0982111111'));
});

it('expires stale holds back to the company wallet', function () {
    $company = payrollCompany();
    fundCompany($company, 'USD', 1000);

    $batch = svc()->createBatch($company, 'USD', [['phone' => '0983222222', 'amount' => 80]]);
    svc()->run($batch);

    $item = PayrollItem::where('company_id', $company->id)->first();
    expect($item->status)->toBe(PayrollItem::STATUS_HELD);
    expect((float) $company->companyWallet('USD')->fresh()->pending_balance)->toBe(80.0);

    // Age the hold beyond the window, then expire.
    $item->forceFill(['held_at' => now()->subDays(40)])->save();
    $released = svc()->expireHeldOlderThan(30);

    expect($released)->toBe(1);
    expect($item->fresh()->status)->toBe(PayrollItem::STATUS_CANCELLED);
    $w = $company->companyWallet('USD')->fresh();
    expect((float) $w->pending_balance)->toBe(0.0);
    expect((float) $w->available_balance)->toBe(1000.0); // fully restored
});

it('blocks hold when free balance is exhausted by existing holds', function () {
    $company = payrollCompany();
    $wallet = fundCompany($company, 'USD', 100);

    // Hold 100 (unregistered employee).
    $batch = svc()->createBatch($company, 'USD', [['phone' => '0983222222', 'amount' => 100]]);
    svc()->run($batch);
    expect($batch->fresh()->held_count)->toBe(1);
    expect((float) $wallet->fresh()->available_balance)->toBe(0.0);

    // Second batch — assertFunded blocks run() because avail is 0.
    // Test the per-item guard directly via processItem instead.
    $batch2 = svc()->createBatch($company, 'USD', [['phone' => '0983333333', 'amount' => 10]]);
    $item = $batch2->items()->first();
    $result = svc()->processItem($item);
    expect($result)->toBe(PayrollItem::STATUS_FAILED);
    expect($item->fresh()->failure_reason)->toBe('رصيد الشركة غير كافٍ');
});

it('notifies company owner when held salary expires', function () {
    $company = payrollCompany();
    $company->update(['user_id' => User::factory()->create()->id]);
    fundCompany($company, 'USD', 500);

    $batch = svc()->createBatch($company, 'USD', [['phone' => '0983222222', 'amount' => 70]]);
    svc()->run($batch);
    expect($batch->fresh()->held_count)->toBe(1);

    // Age then expire.
    PayrollItem::where('company_id', $company->id)->update(['held_at' => now()->subDays(40)]);
    svc()->expireHeldOlderThan(30);

    $notification = \App\Models\UserNotification::where('user_id', $company->user_id)->first();
    expect($notification)->not->toBeNull();
    expect($notification->template_code)->toBe('held_salary_expired');
    expect((float) $notification->data['amount'])->toBe(70.0);
});

it('release through the real KYC verifyPhoneCode hook', function () {
    $company = payrollCompany();
    fundCompany($company, 'USD', 500);

    $batch = svc()->createBatch($company, 'USD', [['phone' => '0982777777', 'amount' => 60]]);
    svc()->run($batch);
    expect($batch->fresh()->held_count)->toBe(1);

    // User registers + we drive the KYC service's verify path (issues + confirms).
    $user = User::factory()->create(['phone' => '0982777777', 'phone_verified_at' => null]);
    $kyc = app(KycService::class);
    $send = $kyc->sendPhoneVerification($user);
    $kyc->verifyPhoneCode($user->fresh(), (string) $send['code']);

    $empWallet = Wallet::where('user_id', $user->id)->where('currency', 'USD')->first();
    expect((float) $empWallet->balance)->toBe(60.0);
    expect(PayrollItem::where('company_id', $company->id)->first()->status)->toBe(PayrollItem::STATUS_PAID);
});
