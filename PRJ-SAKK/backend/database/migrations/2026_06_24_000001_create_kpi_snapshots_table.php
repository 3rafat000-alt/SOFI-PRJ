<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * KPI Snapshots Table — Immutable record of all KPI calculations
     *
     * Purpose: Store historical snapshots of all KPI metrics for trending,
     * compliance reporting, and alert threshold evaluation.
     *
     * Design notes:
     * - UUID primary key for global uniqueness + distributed-system safety
     * - Composite unique index on (kpi_name, computed_at DESC) to prevent duplicates
     *   within the same calculation window and enable efficient latest-value queries
     * - DECIMAL(18,8) for value (supports up to 10M with 8 decimal precision)
     * - Threshold fields support traffic-light KPI evaluation (red/yellow/green zones)
     * - owner_id enables escalation routing (which team owns this KPI?)
     * - source field tracks origin: 'scheduler', 'manual', 'api_trigger' for audit
     * - No soft-deletes: snapshots are immutable, once computed never retracted
     */
    public function up(): void
    {
        Schema::create('kpi_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // KPI Identity
            $table->string('kpi_name', 100); // e.g., 'mau', 'dau', 'gmv', 'aml_flag_rate'

            // Value & Thresholds
            $table->decimal('value', 18, 8); // KPI value (e.g., 25000 for 25k MAU)
            $table->decimal('threshold_green', 18, 8)->nullable(); // Upper bound (good)
            $table->decimal('threshold_yellow', 18, 8)->nullable(); // Warning bound
            $table->decimal('threshold_red', 18, 8)->nullable(); // Critical bound (bad)

            // Timing & Source
            $table->timestamp('computed_at'); // When was this calculated?
            $table->string('source', 50); // scheduler | manual | api_trigger

            // Ownership & Escalation
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();

            // Audit
            $table->timestamps();

            // Indexes
            // Latest value query: SELECT * FROM kpi_snapshots WHERE kpi_name = ? ORDER BY computed_at DESC LIMIT 1
            $table->index(['kpi_name', 'computed_at'], 'idx_kpi_latest');

            // Time-series aggregation: SELECT * FROM kpi_snapshots WHERE computed_at BETWEEN ? AND ?
            $table->index(['computed_at'], 'idx_kpi_computed');

            // Owner workload: SELECT * FROM kpi_snapshots WHERE owner_id = ? ORDER BY computed_at DESC
            $table->index(['owner_id', 'computed_at'], 'idx_kpi_owner');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_snapshots');
    }
};
