<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_balances', function (Blueprint $table) {
            $table->id();
            $table->string('currency', 3)->index();               // USD, SYP
            $table->decimal('balance', 20, 2)->default(0);        // current ledger
            $table->decimal('previous_balance', 20, 2)->default(0);
            $table->decimal('daily_change', 20, 2)->default(0);
            $table->string('source', 50)->default('aggregation'); // aggregation | manual | injection
            $table->timestamp('snapped_at')->useCurrent();         // when this snapshot was taken
            $table->timestamps();

            $table->index(['currency', 'snapped_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_balances');
    }
};
