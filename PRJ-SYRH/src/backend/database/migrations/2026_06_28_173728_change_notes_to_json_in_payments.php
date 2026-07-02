<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PostgreSQL-only: convert plain-text notes to JSONB
        // SQLite `:memory:` in tests — no-op (column already exists as TEXT)
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement("
                UPDATE payments
                SET notes = json_build_object('note', notes)
                WHERE notes IS NOT NULL
                AND LEFT(TRIM(notes::text), 1) NOT IN ('{', '[')
            ");
            DB::statement('ALTER TABLE payments ALTER COLUMN notes TYPE JSONB USING notes::jsonb');
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE payments ALTER COLUMN notes TYPE TEXT USING notes::text');
        }
    }
};
