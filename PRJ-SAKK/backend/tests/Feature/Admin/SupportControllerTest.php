<?php

use App\Models\SystemSetting;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

it('renders the support settings page with defaults', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.system.support'))
        ->assertOk()
        ->assertViewIs('admin.system.support')
        ->assertViewHas('cfg', function ($cfg) {
            return $cfg['support_email'] === 'support@zanjour.com'
                && $cfg['support_enabled'] === true;
        });
});

it('renders the support settings page with persisted values', function () {
    SystemSetting::set('support_email', 'help@sakk.test', 'string');
    SystemSetting::set('support_phone', '+963111', 'string');

    $this->actingAs($this->admin)
        ->get(route('admin.system.support'))
        ->assertOk()
        ->assertViewHas('cfg', function ($cfg) {
            return $cfg['support_email'] === 'help@sakk.test'
                && $cfg['support_phone'] === '+963111';
        });
});

it('updates the support settings and persists every field', function () {
    $payload = [
        'support_enabled' => '1',
        'support_email' => 'newsupport@sakk.test',
        'support_phone' => '+963222333',
        'support_whatsapp' => '+963444555',
        'support_telegram' => '@sakk_support',
        'support_hours' => '24/7',
        'support_message' => 'نحن هنا لمساعدتك',
        'support_faq_url' => 'https://sakk.test/faq',
    ];

    $this->actingAs($this->admin)
        ->put(route('admin.system.support.update'), $payload)
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(SystemSetting::get('support_email'))->toBe('newsupport@sakk.test');
    expect(SystemSetting::get('support_phone'))->toBe('+963222333');
    expect(SystemSetting::get('support_whatsapp'))->toBe('+963444555');
    expect(SystemSetting::get('support_telegram'))->toBe('@sakk_support');
    expect(SystemSetting::get('support_hours'))->toBe('24/7');
    expect(SystemSetting::get('support_message'))->toBe('نحن هنا لمساعدتك');
    expect(SystemSetting::get('support_faq_url'))->toBe('https://sakk.test/faq');
    expect((bool) SystemSetting::get('support_enabled'))->toBeTrue();
});

it('unchecking support_enabled persists false', function () {
    SystemSetting::set('support_enabled', '1', 'boolean');

    $this->actingAs($this->admin)
        ->put(route('admin.system.support.update'), [
            // support_enabled omitted == unchecked checkbox
            'support_email' => 'a@b.com',
        ])
        ->assertRedirect();

    expect((bool) SystemSetting::get('support_enabled'))->toBeFalse();
});

it('rejects an invalid support email on update', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.system.support.update'), [
            'support_email' => 'not-an-email',
        ])
        ->assertSessionHasErrors('support_email');
});

it('rejects an invalid faq url on update', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.system.support.update'), [
            'support_faq_url' => 'not a url',
        ])
        ->assertSessionHasErrors('support_faq_url');
});

it('rejects a support_hours value over the max length', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.system.support.update'), [
            'support_hours' => str_repeat('a', 161),
        ])
        ->assertSessionHasErrors('support_hours');
});
