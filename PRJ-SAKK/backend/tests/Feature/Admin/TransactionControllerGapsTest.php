<?php

use App\Enums\TransactionCategory;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\ActivityLog;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Gap-fill for Admin\TransactionController: show() (detail page + audit trail +
 * reversal/original linkage) and invoice() — not covered by TransactionModuleTest.
 */

function txGapAdmin(): User
{
    return User::factory()->create(['is_admin' => true]);
}

it('show renders the transaction detail page with its audit trail', function () {
    $admin = txGapAdmin();
    $tx = Transaction::factory()->deposit()->create(['status' => TransactionStatus::COMPLETED]);

    ActivityLog::log('transactions.viewed', null, $tx, null, null, 'test note');

    $response = $this->actingAs($admin)->get(route('admin.transactions.show', $tx->id));

    $response->assertOk()->assertViewIs('admin.transactions.show');
    expect($response->viewData('transaction')->id)->toBe($tx->id);
    expect($response->viewData('activity'))->toHaveCount(1);
});

it('show links a reversed transaction to the adjustment it spawned', function () {
    $admin = txGapAdmin();
    $wallet = \App\Models\Wallet::factory()->create(['balance' => 10000, 'available_balance' => 10000]);
    $tx = Transaction::factory()->deposit()->create([
        'status' => TransactionStatus::COMPLETED,
        'wallet_id' => $wallet->id,
    ]);

    $this->actingAs($admin)
        ->postJson(route('admin.transactions.reverse', $tx->id), ['reason' => 'test reversal linkage']);

    $response = $this->actingAs($admin)->get(route('admin.transactions.show', $tx->fresh()->id));

    $response->assertOk();
    expect($response->viewData('reversal'))->not->toBeNull();
    expect($response->viewData('original'))->toBeNull();
});

it('show links a reversal adjustment back to its original transaction', function () {
    $admin = txGapAdmin();
    $wallet = \App\Models\Wallet::factory()->create(['balance' => 10000, 'available_balance' => 10000]);
    $tx = Transaction::factory()->deposit()->create([
        'status' => TransactionStatus::COMPLETED,
        'wallet_id' => $wallet->id,
    ]);

    $this->actingAs($admin)
        ->postJson(route('admin.transactions.reverse', $tx->id), ['reason' => 'test original linkage']);

    $adjustment = Transaction::where('type', TransactionType::ADJUSTMENT)
        ->where('metadata->original_transaction_id', $tx->id)
        ->orderByDesc('id')
        ->first();

    $response = $this->actingAs($admin)->get(route('admin.transactions.show', $adjustment->id));

    $response->assertOk();
    expect($response->viewData('original')->id)->toBe($tx->id);
    expect($response->viewData('reversal'))->toBeNull();
});

it('invoice renders the standalone printable invoice view', function () {
    $admin = txGapAdmin();
    $tx = Transaction::factory()->deposit()->create();

    $response = $this->actingAs($admin)->get(route('admin.transactions.invoice', $tx->id));

    $response->assertOk()->assertViewIs('admin.invoices.transaction');
    expect($response->viewData('transaction')->id)->toBe($tx->id);
});

it('blocks a non-admin from viewing the transaction detail page', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $tx = Transaction::factory()->deposit()->create();

    $status = $this->actingAs($user)
        ->get(route('admin.transactions.show', $tx->id))
        ->status();

    // admin middleware either 403s or redirects away.
    expect($status)->toBeIn([403, 302]);
});

it('returns 404 for a non-existent transaction on show', function () {
    $admin = txGapAdmin();

    $this->actingAs($admin)
        ->get(route('admin.transactions.show', 999999))
        ->assertNotFound();
});
