<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Company employee roster. `phone` is stored in CANONICAL normalized form
 * (see App\Support\PhoneNormalizer) so it matches a SAKK user regardless of how
 * that user stored their own phone. `employee_user_id` is filled once the phone
 * resolves to a registered, phone-verified user.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_employees', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('phone'); // canonical normalized digits, e.g. 963982183111
            $table->string('name')->nullable();
            $table->string('national_id')->nullable();
            $table->string('job_title')->nullable();

            $table->decimal('default_amount', 18, 8)->nullable();
            $table->string('default_currency', 10)->nullable(); // USD | SYP

            $table->string('status', 20)->default('active'); // active, invited, inactive
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'phone']);
            $table->index(['company_id', 'is_active']);
            $table->index('phone');
            $table->index('employee_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_employees');
    }
};
