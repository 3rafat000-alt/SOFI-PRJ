<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SEV-2 fix for already-migrated databases (fresh installs are already fixed
 * by the 'json'->'text' edit in 2026_01_01_000001_create_integrations_table).
 *
 * integrations.config and integrations.credentials are cast `encrypted:array`
 * on the Integration model — the stored value is AES ciphertext, not valid
 * JSON. On MySQL 5.7+/PostgreSQL a native `json` column enforces valid JSON
 * on every write and rejects ciphertext outright. This alters the column
 * type on any DB where the original (buggy) migration already ran.
 *
 * Driver notes:
 *  - mysql/pgsql: native `change()` works without doctrine/dbal on Laravel
 *    11+ (this app is Laravel 12, doctrine/dbal is NOT installed — verified).
 *  - sqlite: `json` columns have no enforced native JSON type (SQLite stores
 *    them with TEXT affinity); changing to `text` is a type-safe no-op that
 *    does not touch existing row bytes. Ciphertext is preserved unchanged
 *    on every driver — this migration alters column TYPE only, never data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integrations', function (Blueprint $table) {
            $table->text('config')->nullable()->change();
            $table->text('credentials')->nullable()->change();
        });
    }

    /**
     * Limitation: reverting to `json` is only safe if every row's ciphertext
     * happens to be valid JSON, which it structurally is NOT (AES output).
     * On mysql/pgsql, restoring `json` here would make the column
     * immediately un-writable again for the same reason 1a fixed — so this
     * down() restores the TYPE only (matching the original schema) and
     * relies on the operator knowing this reintroduces the SEV-2 bug on
     * mysql/pgsql. On sqlite it is a genuine no-op either way (no enforced
     * JSON type). Provided for migration-history symmetry, not recommended
     * to run against a live mysql/pgsql database with real encrypted rows.
     */
    public function down(): void
    {
        Schema::table('integrations', function (Blueprint $table) {
            $table->json('config')->nullable()->change();
            $table->json('credentials')->nullable()->change();
        });
    }
};
