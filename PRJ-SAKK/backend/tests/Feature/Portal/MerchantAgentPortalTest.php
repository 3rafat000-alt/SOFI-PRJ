<?php

use App\Models\Agent;
use App\Models\Merchant;
use App\Models\User;

function verifiedMerchant(): Merchant
{
    $m = Merchant::create([
        'user_id'    => User::factory()->create()->id,
        'store_name' => 'متجر الاختبار',
        'type'       => 'physical',
        'phone'      => '0911',
        'is_active'  => true,
        'kyc_status' => 'approved',
    ]);
    $m->forceFill(['is_verified' => true, 'has_api_access' => true])->save();

    return $m->fresh();
}

function verifiedAgent(): Agent
{
    $a = Agent::create([
        'user_id'   => User::factory()->create()->id,
        'name'      => 'وكيل الاختبار',
        'phone'     => '0922',
        'address'   => 'دمشق',
        'city'      => 'دمشق',
        'latitude'  => 0,
        'longitude' => 0,
        'services'  => ['cash_in', 'cash_out'],
        'is_active' => true,
        'kyc_status' => 'approved',
    ]);
    $a->forceFill(['is_verified' => true])->save();

    return $a->fresh();
}

// ──────────────── No-access page (design truth: no web onboarding) ────────────────

it('a logged-in user with no merchant record sees 403 no-access page (not onboarding)', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->get('/merchant');
    $response->assertStatus(403);
    $response->assertSee('لا تملك حساب شريك بعد');
    // No Merchant record must have been created.
    expect(Merchant::where('user_id', $user->id)->exists())->toBeFalse();
});

it('a logged-in user with no agent record sees 403 no-access page (not onboarding)', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->get('/agent');
    $response->assertStatus(403);
    $response->assertSee('لا تملك حساب شريك بعد');
    expect(Agent::where('user_id', $user->id)->exists())->toBeFalse();
});

it('onboarding routes are gone — merchant.onboarding returns 404', function () {
    $this->assertFalse(\Illuminate\Support\Facades\Route::has('merchant.onboarding'));
});

it('onboarding routes are gone — agent.onboarding returns 404', function () {
    $this->assertFalse(\Illuminate\Support\Facades\Route::has('agent.onboarding'));
});

it('onboarding routes are gone — company.onboarding returns 404', function () {
    $this->assertFalse(\Illuminate\Support\Facades\Route::has('company.onboarding'));
});

// ──────────────── Merchant portal (post-approval, working pages) ────────────────

it('renders merchant portal pages for an approved merchant', function () {
    $m  = verifiedMerchant();
    $op = User::find($m->user_id);
    $this->actingAs($op)->get(route('merchant.dashboard'))->assertOk();
    $this->actingAs($op)->get(route('merchant.profile'))->assertOk()->assertSee($m->api_key);
    $this->actingAs($op)->get(route('merchant.documents'))->assertOk();
});

it('regenerates merchant API keys (guarded-field forceFill)', function () {
    $m   = verifiedMerchant();
    $old = $m->api_key;

    $this->actingAs(User::find($m->user_id))
        ->post(route('merchant.keys.regenerate'))->assertRedirect();

    expect($m->fresh()->api_key)->not->toBe($old);
});

it('merchant login page renders with sakk identity', function () {
    $this->get(route('merchant.login'))->assertOk()->assertSee('بوابة التجار');
});

// ──────────────── Agent portal ────────────────

it('renders agent portal pages for an approved agent', function () {
    $a  = verifiedAgent();
    $op = User::find($a->user_id);
    $this->actingAs($op)->get(route('agent.dashboard'))->assertOk();
    $this->actingAs($op)->get(route('agent.profile'))->assertOk();
    $this->actingAs($op)->get(route('agent.documents'))->assertOk();
});

it('agent login page renders', function () {
    $this->get(route('agent.login'))->assertOk()->assertSee('بوابة الوكلاء');
});

it('redirects guests to the portal login', function () {
    $this->get('/merchant')->assertRedirect(route('merchant.login'));
    $this->get('/agent')->assertRedirect(route('agent.login'));
});
