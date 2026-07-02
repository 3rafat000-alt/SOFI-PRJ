<?php

use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

// ==================== MIN_MAX_PAIRS cross-field guard ====================

it('rejects a min_withdrawal update that would exceed the existing max_withdrawal', function () {
    SystemSetting::set('max_withdrawal', 100, 'decimal');

    $response = $this->actingAs($this->admin)
        ->postJson(route('admin.settings.setting.update'), [
            'key' => 'min_withdrawal',
            'value' => 200,
        ]);

    $response->assertStatus(422)
        ->assertJson(['ok' => false]);

    // Unaffected — the invalid write must not have persisted.
    expect((float) SystemSetting::get('min_withdrawal', 0))->not->toBe(200.0);
});

it('accepts a min_withdrawal update that stays under the existing max_withdrawal', function () {
    SystemSetting::set('max_withdrawal', 100, 'decimal');

    $response = $this->actingAs($this->admin)
        ->postJson(route('admin.settings.setting.update'), [
            'key' => 'min_withdrawal',
            'value' => 10,
        ]);

    $response->assertOk()->assertJson(['ok' => true]);
    expect((float) SystemSetting::get('min_withdrawal'))->toBe(10.0);
});

// ==================== DECIMAL_MAX percent cap ====================

it('rejects withdrawal_fee_percent above the 100 cap', function () {
    // The DECIMAL_MAX guard is manually validated (mirroring the sibling
    // MIN_MAX_PAIRS/currency guards) and returns a clean JSON 422 —
    // consistent regardless of XHR headers, unlike the old
    // $request->validate() path which used to redirect on non-XHR requests.
    $response = $this->actingAs($this->admin)
        ->postJson(route('admin.settings.setting.update'), [
            'key' => 'withdrawal_fee_percent',
            'value' => 150,
        ]);

    $response->assertStatus(422)
        ->assertJson(['ok' => false]);

    // The invalid value must never have persisted.
    expect((float) SystemSetting::get('withdrawal_fee_percent', 0))->not->toBe(150.0);
});

it('accepts withdrawal_fee_percent at a valid value under the cap', function () {
    $response = $this->actingAs($this->admin)
        ->postJson(route('admin.settings.setting.update'), [
            'key' => 'withdrawal_fee_percent',
            'value' => 50,
        ]);

    $response->assertOk()->assertJson(['ok' => true]);
    expect((float) SystemSetting::get('withdrawal_fee_percent'))->toBe(50.0);
});

// ==================== Audit logging ====================

it('writes an audit log entry when an allowed setting is updated', function () {
    $before = AuditLog::where('action', 'settings.update')->count();

    $response = $this->actingAs($this->admin)
        ->postJson(route('admin.settings.setting.update'), [
            'key' => 'withdrawal_fee_percent',
            'value' => 25,
        ]);

    $response->assertOk();

    $after = AuditLog::where('action', 'settings.update')->count();
    expect($after)->toBe($before + 1);

    $log = AuditLog::where('action', 'settings.update')->latest()->first();
    expect($log->model_type)->toBe('SystemSetting');
    expect($log->model_id)->toBe('withdrawal_fee_percent');
});
