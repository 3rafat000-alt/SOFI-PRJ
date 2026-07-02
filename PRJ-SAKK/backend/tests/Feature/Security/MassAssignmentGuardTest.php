<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * SEC-002 — mass-assignment privilege-escalation guard.
 *
 * `is_admin` is intentionally absent from User::$fillable (User.php:47-48), so the
 * mass-assignment paths (create / fill / update) can NEVER set it from caller-supplied
 * input. The only way to flip the privilege flag is forceFill at a trusted site
 * (installer, seeder, admin updateUser). `is_active` is the other non-fillable boolean
 * (cast at User.php:77, never listed in $fillable) and is guarded the same way.
 *
 * Scope note: kyc_level, kyc_status, status and *_verified_at are intentionally NOT in
 * $fillable (SEC-003). They are gated in KycService, AuthService, and admin controllers.
 * This file pins only the genuine $fillable guards (is_admin, is_active) and proves the
 * fillable columns are settable as a positive control.
 *
 * RefreshDatabase is required: User::created (User.php:94-100) auto-creates a USD wallet on
 * every persisted user, which needs the DB. phpunit.xml forces an isolated :memory: store.
 */

/** Build a fully-valid user via raw mass assignment, smuggling the privileged keys. */
function massAssignUser(array $extra = []): User
{
    static $seq = 0;
    $seq++;

    return User::create(array_merge([
        'first_name' => 'Mallory',
        'last_name'  => 'Attacker',
        'email'      => "attacker{$seq}@test.com",
        'phone'      => '+96390000' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
        'password'   => 'secret123',
        'pin_code'   => '123456', // pin_code is NOT NULL in the dev schema; supply it like the factory does
    ], $extra));
}

// ──────────────── is_admin: the privilege flag ────────────────

it('does not set is_admin via User::create mass assignment', function () {
    $u = massAssignUser(['is_admin' => true]);

    // The smuggled key was stripped by the allowlist, so it was never in the INSERT;
    // the persisted column falls back to its DB default (false).
    expect($u->fresh()->is_admin)->toBeFalse();
});

it('does not set is_admin via fill', function () {
    $u = User::factory()->create();
    // Baseline: the persisted column is false (DB default; factory never sets is_admin).
    expect($u->fresh()->is_admin)->toBeFalse();

    $u->fill(['is_admin' => true]);
    $u->save();

    expect($u->fresh()->is_admin)->toBeFalse();
});

it('does not set is_admin via update', function () {
    $u = User::factory()->create();

    $u->update(['is_admin' => true]);

    expect($u->fresh()->is_admin)->toBeFalse();
});

it('allows is_admin only via forceFill', function () {
    $u = User::factory()->create();
    expect($u->fresh()->is_admin)->toBeFalse();

    // forceFill bypasses the allowlist — the single trusted escalation path.
    $u->forceFill(['is_admin' => true])->save();

    expect($u->fresh()->is_admin)->toBeTrue();
});

// ──────────────── is_active: the other non-fillable boolean ────────────────

it('does not set is_active via mass assignment', function () {
    // Smuggle is_active=false; DB default is true, column is not fillable, so it stays true.
    $u = massAssignUser(['is_active' => false]);

    expect((bool) $u->fresh()->is_active)->toBeTrue();
});

it('allows is_active only via forceFill', function () {
    $u = User::factory()->create();

    $u->forceFill(['is_active' => false])->save();

    expect((bool) $u->fresh()->is_active)->toBeFalse();
});

// ──────────────── positive control: fillable columns ARE assignable ────────────────

it('does set fillable identity fields via mass assignment (allowlist works both ways)', function () {
    // Proves the guard is an allowlist, not a blanket lock: whitelisted keys go through.
    // kyc_level/kyc_status/status are intentionally NOT fillable (SEC-003); only fillable
    // columns can be set via mass assignment.
    $u = massAssignUser([
        'first_name' => 'Whitelisted',
        'phone'      => '+96399990000',
    ]);

    expect($u->fresh()->first_name)->toBe('Whitelisted');
    expect($u->fresh()->phone)->toBe('+96399990000');
});
