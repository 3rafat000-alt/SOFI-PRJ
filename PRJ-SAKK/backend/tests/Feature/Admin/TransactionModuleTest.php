<?php

use App\Enums\TransactionCategory;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\ActivityLog;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── Helpers (unique names — avoid collision with other module tests) ───────────
function txAdmin(): User
{
    return User::factory()->create(['is_admin' => true]);
}

function txUser(): User
{
    return User::factory()->create(['is_admin' => false]);
}

// ─────────────────────────────────────────────────────────────────────────────
// READ — index, fragment, kpis, quick-view, export
// ─────────────────────────────────────────────────────────────────────────────

it('renders the transactions index for an admin', function () {
    Transaction::factory()->count(3)->create();

    $this->actingAs(txAdmin())
        ->get(route('admin.transactions'))
        ->assertOk()
        ->assertSee('المعاملات');
});

it('returns only the table partial for an AJAX fragment request', function () {
    Transaction::factory()->count(2)->create();

    $html = $this->actingAs(txAdmin())
        ->get(route('admin.transactions'), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->getContent();

    // Fragment must NOT carry the full layout shell.
    expect($html)->toContain('tx-table-wrap');
    expect($html)->toContain('المرجع');
    expect($html)->not->toContain('<!DOCTYPE html');
});

it('filters the index by status', function () {
    Transaction::factory()->create(['status' => TransactionStatus::COMPLETED, 'reference' => 'TXN-DONE']);
    Transaction::factory()->pending()->create(['reference' => 'TXN-WAIT']);

    $html = $this->actingAs(txAdmin())
        ->get(route('admin.transactions', ['status' => 'pending']), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->getContent();

    expect($html)->toContain('TXN-WAIT');
    expect($html)->not->toContain('TXN-DONE');
});

it('returns kpi aggregates as json', function () {
    Transaction::factory()->deposit()->create(['status' => TransactionStatus::COMPLETED]);
    Transaction::factory()->pending()->create();

    $this->actingAs(txAdmin())
        ->getJson(route('admin.transactions.kpis'))
        ->assertOk()
        ->assertJsonStructure([
            'today_volume', 'total_deposits', 'total_withdrawals',
            'total_fees', 'pending_count', 'failed_count',
        ])
        ->assertJsonPath('pending_count', 1);
});

it('returns quick-view json for a transaction', function () {
    $tx = Transaction::factory()->create();

    $this->actingAs(txAdmin())
        ->getJson(route('admin.transactions.quick-view', $tx->id))
        ->assertOk()
        ->assertJsonPath('transaction.reference', $tx->reference)
        ->assertJsonStructure(['transaction' => ['amount', 'status', 'currency'], 'user', 'view_url']);
});

it('exports a real server-side csv and logs the action', function () {
    Transaction::factory()->count(2)->create();

    $res = $this->actingAs(txAdmin())->get(route('admin.transactions.export'));

    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('text/csv');
    expect(ActivityLog::where('action', 'transactions.export')->exists())->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// MUTATION — reverse (the only allowed admin action)
// ─────────────────────────────────────────────────────────────────────────────

it('reverses a completed transaction and appends an audited adjustment', function () {
    $wallet = Wallet::factory()->create([
        'balance'           => 100000,
        'available_balance' => 100000,
    ]);
    $tx = Transaction::factory()->deposit()->create([
        'wallet_id' => $wallet->id,
        'status'    => TransactionStatus::COMPLETED,
        'type'      => TransactionType::DEPOSIT,
        'category'  => TransactionCategory::WALLET,
    ]);

    $balanceBefore = (float) $wallet->fresh()->balance;

    $this->actingAs(txAdmin())
        ->postJson(route('admin.transactions.reverse', $tx->id), ['reason' => 'تصحيح خطأ إيداع'])
        ->assertOk()
        ->assertJsonPath('success', true);

    // Original is flagged reversed.
    expect($tx->fresh()->status)->toBe(TransactionStatus::REVERSED);

    // An audited reversal adjustment now exists for the same wallet.
    $adjustment = Transaction::where('type', TransactionType::ADJUSTMENT)
        ->where('wallet_id', $wallet->id)
        ->first();
    expect($adjustment)->not->toBeNull();
    expect((float) $adjustment->amount)->toBe(-(float) $tx->amount);
    expect($adjustment->metadata['reversal_reason'])->toBe('تصحيح خطأ إيداع');

    // Wallet balance corrected: deposit credited, reversal debits it back.
    expect((float) $wallet->fresh()->balance)->toBe($balanceBefore - (float) $tx->amount);

    // ActivityLog written.
    expect(ActivityLog::where('action', 'transactions.reversed')->exists())->toBeTrue();
});

it('reverses a completed withdrawal and credits the wallet', function () {
    $wallet = Wallet::factory()->create([
        'balance'           => 100000,
        'available_balance' => 100000,
    ]);
    $tx = Transaction::factory()->create([
        'wallet_id' => $wallet->id,
        'status'    => TransactionStatus::COMPLETED,
        'type'      => TransactionType::WITHDRAWAL,
        'category'  => TransactionCategory::WALLET,
        'amount'    => -500,
    ]);

    $balanceBefore = (float) $wallet->fresh()->balance;

    $this->actingAs(txAdmin())
        ->postJson(route('admin.transactions.reverse', $tx->id), ['reason' => 'خطأ في السحب'])
        ->assertOk()
        ->assertJsonPath('success', true);

    // Wallet corrected: withdrawal debited, reversal credits it back.
    expect((float) $wallet->fresh()->balance)->toBe($balanceBefore + 500);

    // Adjustment record exists with positive amount (undoing a debit).
    $adjustment = Transaction::where('type', TransactionType::ADJUSTMENT)
        ->where('wallet_id', $wallet->id)
        ->first();
    expect($adjustment)->not->toBeNull();
    expect((float) $adjustment->amount)->toBe(500.0);
});

it('refuses to reverse a non-completed transaction', function () {
    $tx = Transaction::factory()->pending()->create();

    $this->actingAs(txAdmin())
        ->postJson(route('admin.transactions.reverse', $tx->id), ['reason' => 'محاولة'])
        ->assertStatus(422);

    expect($tx->fresh()->status)->toBe(TransactionStatus::PENDING);
});

it('refuses to reverse an adjustment transaction', function () {
    $tx = Transaction::factory()->create([
        'status'   => TransactionStatus::COMPLETED,
        'type'     => TransactionType::ADJUSTMENT,
        'category' => TransactionCategory::ADJUSTMENT,
    ]);

    $this->actingAs(txAdmin())
        ->postJson(route('admin.transactions.reverse', $tx->id), ['reason' => 'محاولة عكس تسوية'])
        ->assertStatus(422);
});

it('requires a reason to reverse', function () {
    // The app's exception handler only renders JSON for api/* — admin web routes
    // redirect on a ValidationException (see UserModule "requires a reason").
    $tx = Transaction::factory()->create(['status' => TransactionStatus::COMPLETED, 'type' => TransactionType::DEPOSIT]);

    $this->actingAs(txAdmin())
        ->post(route('admin.transactions.reverse', $tx->id), ['reason' => ''])
        ->assertRedirect()
        ->assertSessionHasErrors(['reason']);

    // No mutation occurred.
    expect($tx->fresh()->status)->toBe(TransactionStatus::COMPLETED);
});

// ─────────────────────────────────────────────────────────────────────────────
// SCOPE — there is no edit/update surface; non-admins are blocked
// ─────────────────────────────────────────────────────────────────────────────

it('has no edit route for a transaction', function () {
    $tx = Transaction::factory()->create();

    $this->actingAs(txAdmin())
        ->get("/admin/transactions/{$tx->id}/edit")
        ->assertNotFound();
});

it('has no update route for a transaction', function () {
    $tx = Transaction::factory()->create(['amount' => 100]);

    $status = $this->actingAs(txAdmin())
        ->put("/admin/transactions/{$tx->id}", ['amount' => 999999])
        ->status();

    expect($status)->toBeIn([404, 405]);
    expect((float) $tx->fresh()->amount)->toBe(100.0);
});

it('blocks a non-admin from the transactions surface', function () {
    $tx = Transaction::factory()->create();

    $status = $this->actingAs(txUser())
        ->get(route('admin.transactions'))
        ->status();

    // admin middleware either 403s or redirects away.
    expect($status)->toBeIn([403, 302]);
});
