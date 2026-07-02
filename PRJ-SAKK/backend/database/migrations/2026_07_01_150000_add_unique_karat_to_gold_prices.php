<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * GoldPriceService::firstOrNew(['karat' => ...]) races to duplicate rows
     * without a DB-level unique constraint. Defensive dedup runs first in
     * case stray duplicate karat rows already exist (keeps the most
     * recently updated row per karat, deletes the rest), then the unique
     * index is added.
     */
    public function up(): void
    {
        $duplicateKarats = DB::table('gold_prices')
            ->select('karat')
            ->groupBy('karat')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('karat');

        foreach ($duplicateKarats as $karat) {
            $rows = DB::table('gold_prices')
                ->where('karat', $karat)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get();

            $keepId = $rows->first()->id;

            DB::table('gold_prices')
                ->where('karat', $karat)
                ->where('id', '!=', $keepId)
                ->delete();
        }

        // BUG FIX (blocked the entire test suite): the original create-table
        // migration (2026_06_20_100000_create_gold_prices_table.php) already
        // declares `$table->unique('karat')`, which Laravel names
        // `gold_prices_karat_unique` by convention — identical to the name
        // this migration tries to add. On fresh installs (sqlite :memory:
        // included) this collided with "index already exists". The column is
        // already uniquely constrained by the original migration, so this
        // step is now a no-op guarded against re-adding a duplicate index.
        if (! $this->indexExists('gold_prices', 'gold_prices_karat_unique')) {
            Schema::table('gold_prices', function (Blueprint $table) {
                $table->unique('karat', 'gold_prices_karat_unique');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $rows = $connection->select("PRAGMA index_list(\"{$table}\")");
            foreach ($rows as $row) {
                if (($row->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        // mysql/pgsql etc — fall back to information_schema style check.
        return collect($connection->select(
            'SHOW INDEX FROM ' . $table . ' WHERE Key_name = ?',
            [$indexName]
        ))->isNotEmpty();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gold_prices', function (Blueprint $table) {
            $table->dropUnique('gold_prices_karat_unique');
        });
    }
};
