<?php

use App\Models\Integration;
use App\Models\User;
use App\Services\StripeIssuingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

it('removed the SEO route entirely', function () {
    expect(Route::has('admin.system.seo'))->toBeFalse();
    expect(Route::has('admin.system.seo.update'))->toBeFalse();
});

it('renders integrations overview and system pages', function () {
    $this->seed(\Database\Seeders\SystemConfigSeeder::class);
    $this->seed(\Database\Seeders\StripeSeeder::class);
    $this->seed(\Database\Seeders\CCPaymentSeeder::class);

    // third-party now redirects to integrations overview
    $this->actingAs($this->admin)
        ->get(route('admin.system.third-party'))
        ->assertRedirect(route('admin.integrations.overview'));

    // integrations overview shows payment gateways + services
    $this->actingAs($this->admin)
        ->get(route('admin.integrations.overview'))
        ->assertOk()
        ->assertSee('مركز الربط', false)
        ->assertSee('بيانات الربط', false);

    $this->actingAs($this->admin)->get(route('admin.system.channels'))->assertOk();
    $this->actingAs($this->admin)->get(route('admin.system.messages'))->assertOk();
    $this->actingAs($this->admin)->get(route('admin.system.maintenance'))->assertOk();
});

it('saves stripe gateway credentials and the issuing service reads them from DB', function () {
    $this->seed(\Database\Seeders\StripeSeeder::class);
    $stripe = Integration::where('key', 'stripe')->first();

    Mail::fake();

    // Step 1: send credentials → expects OTP challenge
    $resp = $this->actingAs($this->admin)
        ->putJson(route('admin.integrations.update', $stripe), [
            'name' => $stripe->name,
            'name_ar' => $stripe->name_ar,
            'credentials' => ['secret' => 'sk_test_ABC123', 'publishable_key' => 'pk_test_XYZ'],
            'is_active' => '1',
        ])
        ->assertOk()
        ->assertJson(['requires_otp' => true]);

    $pendingToken = $resp->json('pending_token');

    // Manually seed OTP in cache (array driver resets between HTTP requests)
    Cache::put('admin_otp:' . $this->admin->id, '123456', 300);

    // Step 2: verify OTP to complete the update
    $this->actingAs($this->admin)
        ->putJson(route('admin.integrations.update', $stripe), [
            'pending_token' => $pendingToken,
            'otp_code' => '123456',
        ])
        ->assertOk()
        ->assertJson(['success' => true]);

    $stripe->refresh();
    expect($stripe->is_active)->toBeTrue();
    expect($stripe->getCredential('secret'))->toBe('sk_test_ABC123');

    // StripeIssuingService must now pick up the admin-entered key (not just env).
    expect((new StripeIssuingService())->isConfigured())->toBeTrue();
});

it('keeps an existing secret when the field is submitted blank (write-only secrets)', function () {
    $this->seed(\Database\Seeders\StripeSeeder::class);
    $stripe = Integration::where('key', 'stripe')->first();
    $stripe->update(['credentials' => ['secret' => 'sk_live_KEEPME']]);

    $this->actingAs($this->admin)
        ->putJson(route('admin.integrations.update', $stripe), [
            'name' => $stripe->name,
            'name_ar' => $stripe->name_ar,
            'credentials' => ['secret' => ''],
            'is_active' => '1',
        ])
        ->assertOk()
        ->assertJson(['success' => true]);

    expect($stripe->refresh()->getCredential('secret'))->toBe('sk_live_KEEPME');
});

it('tests ccpayment gateway connectivity and flags missing credentials', function () {
    $this->seed(\Database\Seeders\CCPaymentSeeder::class);
    $cc = Integration::where('key', 'ccpayment')->first();

    // No creds → error
    $cc->update(['credentials' => []]);
    $this->actingAs($this->admin)
        ->postJson(route('admin.integrations.test', $cc))
        ->assertOk()
        ->assertJson(['success' => false]);

    // Both creds → success
    $cc->update(['credentials' => ['app_id' => 'A', 'app_secret' => 'B']]);
    $this->actingAs($this->admin)
        ->postJson(route('admin.integrations.test', $cc))
        ->assertOk()
        ->assertJson(['success' => true]);

    expect($cc->refresh()->last_synced_at)->not->toBeNull();
});

it('rejects gateway routes for non-gateway integrations (routes removed)', function () {
    expect(Route::has('admin.system.gateways.update'))->toBeFalse();
    expect(Route::has('admin.system.gateways.test'))->toBeFalse();
});
