<?php

use App\Mail\AgentApprovedMail;
use App\Mail\CompanyApprovedMail;
use App\Mail\MerchantApprovedMail;
use App\Models\Agent;
use App\Models\Company;
use App\Models\Merchant;
use App\Models\User;
use App\Services\PartnerApprovalNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

// ─────────────────────────────────────────────────────────────────
// Helper: build minimal pending records (no factory needed for agent/merchant)
// ─────────────────────────────────────────────────────────────────

function pendingAgentWithUser(): array
{
    $user  = User::factory()->create(['email' => 'agent@test.com']);
    $agent = Agent::create([
        'user_id'    => $user->id,
        'name'       => 'وكيل التجربة',
        'phone'      => '0999000001',
        'address'    => 'دمشق',
        'city'       => 'دمشق',
        'latitude'   => 0,
        'longitude'  => 0,
        'is_active'  => false,
        'is_verified' => false,
        'kyc_status' => 'pending',
    ]);
    return [$user, $agent->fresh()];
}

function pendingMerchantWithUser(): array
{
    $user     = User::factory()->create(['email' => 'merchant@test.com']);
    $merchant = Merchant::create([
        'user_id'    => $user->id,
        'store_name' => 'متجر التجربة',
        'type'       => 'physical',
        'phone'      => '0999000002',
        'is_active'  => false,
        'is_verified' => false,
        'kyc_status' => 'pending',
    ]);
    return [$user, $merchant->fresh()];
}

function pendingCompanyWithUser(): array
{
    $user    = User::factory()->create(['email' => 'company@test.com']);
    $company = Company::factory()->create(['user_id' => $user->id]);
    return [$user, $company->fresh()];
}

// ─────────────────────────────────────────────────────────────────
// 1. Notifier sends exactly ONE mail per audience on first approval
// ─────────────────────────────────────────────────────────────────

it('sends exactly one AgentApprovedMail on first approval with correct portal URL', function () {
    Mail::fake();
    [, $agent] = pendingAgentWithUser();

    $agent->forceFill(['kyc_status' => 'approved', 'is_verified' => true])->save();
    (new PartnerApprovalNotifier())->notifyAgent($agent->fresh());

    Mail::assertSent(AgentApprovedMail::class, 1);
    Mail::assertSent(AgentApprovedMail::class, function (AgentApprovedMail $mail) {
        return $mail->loginUrl === route('agent.login');
    });

    expect($agent->fresh()->approval_notified_at)->not->toBeNull();
});

it('sends exactly one MerchantApprovedMail on first approval with correct portal URL', function () {
    Mail::fake();
    [, $merchant] = pendingMerchantWithUser();

    $merchant->forceFill(['kyc_status' => 'approved', 'is_verified' => true])->save();
    (new PartnerApprovalNotifier())->notifyMerchant($merchant->fresh());

    Mail::assertSent(MerchantApprovedMail::class, 1);
    Mail::assertSent(MerchantApprovedMail::class, function (MerchantApprovedMail $mail) {
        return $mail->loginUrl === route('merchant.login');
    });

    expect($merchant->fresh()->approval_notified_at)->not->toBeNull();
});

it('sends exactly one CompanyApprovedMail on first approval with correct portal URL', function () {
    Mail::fake();
    [, $company] = pendingCompanyWithUser();

    $company->forceFill(['kyc_status' => 'approved', 'is_verified' => true, 'payroll_enabled' => true])->save();
    (new PartnerApprovalNotifier())->notifyCompany($company->fresh());

    Mail::assertSent(CompanyApprovedMail::class, 1);
    Mail::assertSent(CompanyApprovedMail::class, function (CompanyApprovedMail $mail) {
        return $mail->loginUrl === route('company.login');
    });

    expect($company->fresh()->approval_notified_at)->not->toBeNull();
});

// ─────────────────────────────────────────────────────────────────
// 2. Idempotency: second call does NOT re-send
// ─────────────────────────────────────────────────────────────────

it('does NOT re-send agent approval mail when approval_notified_at is already set', function () {
    Mail::fake();
    [, $agent] = pendingAgentWithUser();
    $agent->forceFill(['kyc_status' => 'approved', 'is_verified' => true])->save();

    $notifier = new PartnerApprovalNotifier();
    $notifier->notifyAgent($agent->fresh()); // first call → sends
    $notifier->notifyAgent($agent->fresh()); // second call → no-op

    Mail::assertSent(AgentApprovedMail::class, 1);
});

it('does NOT re-send merchant approval mail when approval_notified_at is already set', function () {
    Mail::fake();
    [, $merchant] = pendingMerchantWithUser();
    $merchant->forceFill(['kyc_status' => 'approved', 'is_verified' => true])->save();

    $notifier = new PartnerApprovalNotifier();
    $notifier->notifyMerchant($merchant->fresh());
    $notifier->notifyMerchant($merchant->fresh());

    Mail::assertSent(MerchantApprovedMail::class, 1);
});

it('does NOT re-send company approval mail when approval_notified_at is already set', function () {
    Mail::fake();
    [, $company] = pendingCompanyWithUser();
    $company->forceFill(['kyc_status' => 'approved', 'is_verified' => true])->save();

    $notifier = new PartnerApprovalNotifier();
    $notifier->notifyCompany($company->fresh());
    $notifier->notifyCompany($company->fresh());

    Mail::assertSent(CompanyApprovedMail::class, 1);
});

// ─────────────────────────────────────────────────────────────────
// 3. Mail failure does NOT throw — approval is not rolled back
// ─────────────────────────────────────────────────────────────────

it('a mailer failure does not propagate out of notifyAgent', function () {
    Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP down'));

    [, $agent] = pendingAgentWithUser();
    $agent->forceFill(['kyc_status' => 'approved', 'is_verified' => true])->save();

    // Must not throw.
    expect(fn () => (new PartnerApprovalNotifier())->notifyAgent($agent->fresh()))
        ->not->toThrow(\Throwable::class);
});

it('a mailer failure does not propagate out of notifyMerchant', function () {
    Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP down'));

    [, $merchant] = pendingMerchantWithUser();
    $merchant->forceFill(['kyc_status' => 'approved', 'is_verified' => true])->save();

    expect(fn () => (new PartnerApprovalNotifier())->notifyMerchant($merchant->fresh()))
        ->not->toThrow(\Throwable::class);
});

it('a mailer failure does not propagate out of notifyCompany', function () {
    Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP down'));

    [, $company] = pendingCompanyWithUser();
    $company->forceFill(['kyc_status' => 'approved', 'is_verified' => true])->save();

    expect(fn () => (new PartnerApprovalNotifier())->notifyCompany($company->fresh()))
        ->not->toThrow(\Throwable::class);
});

// ─────────────────────────────────────────────────────────────────
// 4. Mobile API apply path regression — still creates pending records
// ─────────────────────────────────────────────────────────────────

it('mobile POST /api/v1/partner/apply (agent) still creates a pending Agent record', function () {
    $user = User::factory()->create(['kyc_status' => \App\Enums\KycStatus::VERIFIED]);
    \Laravel\Sanctum\Sanctum::actingAs($user);

    $this->postJson('/api/v1/partner/apply', [
        'type'     => 'agent',
        'name'     => 'وكيل موبايل',
        'phone'    => '0999888777',
        'address'  => 'حلب',
        'city'     => 'حلب',
        'services' => ['cash_in'],
    ])->assertStatus(201);

    $agent = Agent::where('user_id', $user->id)->first();
    expect($agent)->not->toBeNull();
    expect($agent->kyc_status)->toBe('pending');
    expect($agent->is_active)->toBeFalse();
});

it('mobile POST /api/v1/company/apply still creates a pending Company record', function () {
    $user = User::factory()->create(['kyc_status' => \App\Enums\KycStatus::VERIFIED]);
    \Laravel\Sanctum\Sanctum::actingAs($user);

    $this->postJson('/api/v1/company/apply', [
        'name' => 'شركة موبايل',
        'city' => 'دمشق',
    ])->assertStatus(201);

    $company = Company::where('user_id', $user->id)->first();
    expect($company)->not->toBeNull();
    expect($company->kyc_status)->toBe('pending');
});
