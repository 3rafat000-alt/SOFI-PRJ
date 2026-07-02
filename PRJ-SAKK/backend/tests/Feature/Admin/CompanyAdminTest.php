<?php

use App\Models\Company;
use App\Models\CompanyDocument;
use App\Models\User;
use App\Models\Wallet;

function admin(): User
{
    return User::factory()->create(['is_admin' => true]);
}

it('approving the last pending document activates payroll', function () {
    $company = Company::factory()->create(['user_id' => User::factory()]);
    $doc = CompanyDocument::create([
        'company_id' => $company->id, 'document_type' => 'commercial_register',
        'file_path' => 'company-documents/x.pdf', 'status' => 'pending',
    ]);

    $this->actingAs(admin())
        ->post(route('admin.companies.documents.approve', $doc))
        ->assertRedirect();

    $company->refresh();
    expect($company->payroll_enabled)->toBeTrue();
    expect($company->is_verified)->toBeTrue();
    expect($company->kyc_status)->toBe('approved');
});

it('rejecting a document re-locks payroll', function () {
    $company = Company::factory()->payrollReady()->create();
    $doc = CompanyDocument::create([
        'company_id' => $company->id, 'document_type' => 'tax_card',
        'file_path' => 'company-documents/y.pdf', 'status' => 'pending',
    ]);

    $this->actingAs(admin())
        ->post(route('admin.companies.documents.reject', $doc), ['rejection_reason' => 'غير واضح'])
        ->assertRedirect();

    $company->refresh();
    expect($company->payroll_enabled)->toBeFalse();
    expect($company->kyc_status)->toBe('documents_required');
});

it('admin top-up credits the company wallet', function () {
    $company = Company::factory()->create(['user_id' => User::factory()]);

    $this->actingAs(admin())
        ->post(route('admin.companies.topup', $company), ['amount' => 250, 'currency' => 'USD'])
        ->assertRedirect();

    expect((float) Wallet::where('company_id', $company->id)->where('currency', 'USD')->value('balance'))->toBe(250.0);
});

it('admin can create a company', function () {
    $this->actingAs(admin())
        ->post(route('admin.companies.store'), [
            'name' => 'شركة جديدة',
            'phone' => '0911111111',
            'payroll_enabled' => '1',
        ])->assertRedirect();

    $company = Company::where('name', 'شركة جديدة')->first();
    expect($company)->not->toBeNull();
    expect($company->payroll_enabled)->toBeTrue();   // immediate-enable path
    expect($company->kyc_status)->toBe('approved');
});

it('admin create can link an existing operator by email', function () {
    $operator = User::factory()->create(['email' => 'op@x.com']);

    $this->actingAs(admin())
        ->post(route('admin.companies.store'), ['name' => 'X', 'operator_email' => 'op@x.com'])
        ->assertRedirect();

    expect(Company::where('name', 'X')->value('user_id'))->toBe($operator->id);
});

it('admin create rejects unknown operator email', function () {
    $this->actingAs(admin())
        ->post(route('admin.companies.store'), ['name' => 'Y', 'operator_email' => 'ghost@x.com'])
        ->assertSessionHasErrors('operator_email');

    expect(Company::where('name', 'Y')->exists())->toBeFalse();
});

it('renders admin company pages', function () {
    $a = admin();
    $company = Company::factory()->payrollReady()->create();
    CompanyDocument::create(['company_id' => $company->id, 'document_type' => 'license', 'file_path' => 'company-documents/z.pdf', 'status' => 'pending']);

    $this->actingAs($a)->get(route('admin.companies.index'))->assertOk();
    $this->actingAs($a)->get(route('admin.companies.create'))->assertOk();
    $this->actingAs($a)->get(route('admin.companies.show', $company))->assertOk();
    $this->actingAs($a)->get(route('admin.companies.edit', $company))->assertOk();
    $this->actingAs($a)->get(route('admin.companies.documents'))->assertOk();
    $this->actingAs($a)->get(route('admin.companies.documents.show', $company))->assertOk();
});

it('blocks non-admins from the company admin', function () {
    $company = Company::factory()->create(['user_id' => User::factory()]);

    $this->actingAs(User::factory()->create(['is_admin' => false]))
        ->get(route('admin.companies.index'))
        ->assertRedirect(); // AdminMiddleware bounces to admin.login
});
