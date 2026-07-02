<?php

use App\Models\Company;
use App\Models\CompanyEmployee;
use App\Models\PayrollBatch;
use App\Models\PayrollItem;
use App\Models\User;
use App\Models\Wallet;

function operatorOf(Company $company): User
{
    return User::find($company->user_id);
}

it('a logged-in user without a company sees 403 no-access page — no Company record created', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/company');
    $response->assertStatus(403);
    $response->assertSee('لا تملك حساب شريك بعد');

    expect(Company::where('user_id', $user->id)->exists())->toBeFalse();
});

it('runs payroll end-to-end through the portal', function () {
    $company = Company::factory()->payrollReady()->create();
    $operator = operatorOf($company);

    // Fund company USD wallet.
    $wallet = $company->companyWallet('USD');
    $wallet->forceFill(['balance' => 1000, 'available_balance' => 1000])->save();

    // A registered + verified employee.
    $employee = User::factory()->create(['phone' => '0982111111', 'phone_verified_at' => now()]);
    CompanyEmployee::create([
        'company_id' => $company->id, 'phone' => '0982111111',
        'name' => 'Worker', 'default_amount' => 100, 'default_currency' => 'USD', 'is_active' => true,
    ]);

    // Create the batch via the portal.
    $this->actingAs($operator)->post(route('company.payroll.store'), [
        'currency' => 'USD',
        'title' => 'June',
        'sel' => ['0982111111'],
        'amt' => ['0982111111' => 100],
    ])->assertRedirect();

    $batch = PayrollBatch::where('company_id', $company->id)->firstOrFail();

    // Run it.
    $this->actingAs($operator)->post(route('company.payroll.run', $batch))->assertRedirect();

    expect($batch->fresh()->status)->toBe(PayrollBatch::STATUS_COMPLETED);
    expect((float) Wallet::where('user_id', $employee->id)->where('currency', 'USD')->value('balance'))->toBe(100.0);
});

it('blocks IDOR: an operator cannot view another company\'s batch', function () {
    $companyA = Company::factory()->payrollReady()->create();
    $companyB = Company::factory()->payrollReady()->create();

    $batchB = PayrollBatch::factory()->create(['company_id' => $companyB->id]);

    $this->actingAs(operatorOf($companyA))
        ->get(route('company.payroll.show', $batchB))
        ->assertForbidden();
});

it('blocks IDOR: an operator cannot delete another company\'s employee', function () {
    $companyA = Company::factory()->payrollReady()->create();
    $companyB = Company::factory()->payrollReady()->create();
    $empB = CompanyEmployee::factory()->create(['company_id' => $companyB->id]);

    $this->actingAs(operatorOf($companyA))
        ->delete(route('company.employees.destroy', $empB))
        ->assertForbidden();

    expect(CompanyEmployee::whereKey($empB->id)->exists())->toBeTrue();
});

it('top-up moves money from the operator wallet into the company wallet', function () {
    $company = Company::factory()->payrollReady()->create();
    $operator = operatorOf($company);
    Wallet::where('user_id', $operator->id)->where('currency', 'USD')
        ->update(['balance' => 500, 'available_balance' => 500]);

    $this->actingAs($operator)->post(route('company.wallet.topup'), [
        'amount' => 300, 'currency' => 'USD',
    ])->assertRedirect();

    expect((float) $company->companyWallet('USD')->fresh()->balance)->toBe(300.0);
    expect((float) Wallet::where('user_id', $operator->id)->where('currency', 'USD')->value('balance'))->toBe(200.0);
});

it('renders every portal page without Blade errors', function () {
    $company = Company::factory()->payrollReady()->create();
    $operator = operatorOf($company);
    $company->companyWallet('USD')->forceFill(['balance' => 100, 'available_balance' => 100])->save();
    CompanyEmployee::factory()->create(['company_id' => $company->id]);
    $batch = PayrollBatch::factory()->create(['company_id' => $company->id]);

    $this->actingAs($operator)->get(route('company.dashboard'))->assertOk();
    $this->actingAs($operator)->get(route('company.employees.index'))->assertOk();
    $this->actingAs($operator)->get(route('company.wallet.index'))->assertOk();
    $this->actingAs($operator)->get(route('company.payroll.index'))->assertOk();
    $this->actingAs($operator)->get(route('company.payroll.create'))->assertOk();
    $this->actingAs($operator)->get(route('company.payroll.show', $batch))->assertOk();
});

it('blocks running payroll for an unverified company (gate)', function () {
    $company = Company::factory()->create(['user_id' => User::factory()]); // not payroll-ready
    $operator = operatorOf($company);
    $company->companyWallet('USD')->forceFill(['balance' => 1000, 'available_balance' => 1000])->save();
    CompanyEmployee::create([
        'company_id' => $company->id, 'phone' => '0982111111',
        'default_amount' => 100, 'default_currency' => 'USD', 'is_active' => true,
    ]);

    $this->actingAs($operator)->post(route('company.payroll.store'), [
        'currency' => 'USD', 'sel' => ['0982111111'], 'amt' => ['0982111111' => 100],
    ])->assertRedirect();

    $batch = PayrollBatch::where('company_id', $company->id)->firstOrFail();
    $this->actingAs($operator)->post(route('company.payroll.run', $batch))->assertRedirect();

    // Gate held — nothing paid.
    expect($batch->fresh()->status)->not->toBe(PayrollBatch::STATUS_COMPLETED);
    expect(PayrollItem::where('payroll_batch_id', $batch->id)->where('status', 'paid')->count())->toBe(0);
});
