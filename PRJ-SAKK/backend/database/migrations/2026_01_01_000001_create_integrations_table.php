<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // shamcash, ccpayment, google_maps, virtual_cards, messaging, notifications
            $table->string('name');
            $table->string('name_ar');
            $table->text('description')->nullable();
            $table->text('description_ar')->nullable();
            $table->string('icon')->default('link');
            $table->string('category')->default('payment'); // payment, messaging, location, cards, notifications
            $table->boolean('is_active')->default(false);
            $table->boolean('is_visible')->default(true);
            // NOTE: 'config' and 'credentials' are 'text' (NOT 'json') because the
            // Integration model casts both `encrypted:array` — the stored value is
            // AES ciphertext (base64), which is not valid JSON. A native `json`
            // column would reject every INSERT/UPDATE on MySQL 5.7+/PostgreSQL
            // (both validate JSON-typed columns at write time). sqlite has no
            // enforced json type so this bug was invisible in local/dev. 'settings'
            // stays 'json' — it uses the plain 'array' cast (plaintext JSON).
            $table->text('config')->nullable(); // all config keys/values (encrypted)
            $table->text('credentials')->nullable(); // encrypted credentials
            $table->json('settings')->nullable(); // feature settings (plaintext)
            $table->text('webhook_url')->nullable();
            $table->string('environment', 20)->default('sandbox');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->text('last_error_message')->nullable();
            $table->integer('sync_count')->default(0);
            $table->integer('error_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
