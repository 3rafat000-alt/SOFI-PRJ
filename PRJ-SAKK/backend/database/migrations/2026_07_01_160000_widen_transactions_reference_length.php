<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Widen transactions.reference from varchar(32) to varchar(64).
     *
     * CCPayment deposit webhooks key the transaction reference off their
     * `recordId`, which can exceed 32 chars and overflow the original
     * column (SEV-2). Uses raw SQL rather than Schema::table()->change()
     * because doctrine/dbal is not installed in this project. The unique
     * index on `reference` is untouched by a plain MODIFY COLUMN — do not
     * re-declare it (see migration-double-index-hazard).
     */
    public function up(): void
    {
        if (! Schema::hasColumn('transactions', 'reference')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `transactions` MODIFY `reference` VARCHAR(64) NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE transactions ALTER COLUMN reference TYPE VARCHAR(64)');
        } elseif ($driver === 'sqlite') {
            // SQLite has no real varchar length enforcement; nothing to alter.
            return;
        }
    }

    /**
     * Reverse the widening back to varchar(32).
     *
     * Note: if any existing reference exceeds 32 chars this down() will
     * fail on MySQL (data truncation) — that is intentional, it protects
     * against silently losing data on rollback.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('transactions', 'reference')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `transactions` MODIFY `reference` VARCHAR(32) NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE transactions ALTER COLUMN reference TYPE VARCHAR(32)');
        }
    }
};
