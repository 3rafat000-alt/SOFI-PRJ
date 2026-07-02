<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            // Directed request: the specific user asked to pay (gets a
            // notification and can accept/reject). Null = open link/QR request.
            $table->foreignId('requestee_id')->nullable()->after('user_id')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable()->after('paid_at');
            $table->string('response_note', 140)->nullable()->after('responded_at');

            $table->index(['requestee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requestee_id');
            $table->dropColumn(['responded_at', 'response_note']);
        });
    }
};
