<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Section 1: 3rd-party & security service configs (encrypted) ──
        Schema::create('service_configs', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();      // sms | mail | firebase_otp | recaptcha
            $table->string('name');
            $table->string('name_ar');
            $table->string('group')->default('security'); // security | messaging
            $table->string('icon')->default('settings');
            $table->string('driver')->nullable();  // twilio | vonage | smtp | firebase | google | ...
            $table->text('credentials')->nullable(); // encrypted JSON (secrets)
            $table->json('settings')->nullable();     // non-secret options
            $table->boolean('is_active')->default(false);
            $table->timestamp('last_tested_at')->nullable();
            $table->boolean('last_test_ok')->nullable();
            $table->timestamps();
        });

        // ── Section 2: notification channel matrix (event × recipient) ──
        Schema::create('notification_channels', function (Blueprint $table) {
            $table->id();
            $table->string('event_key');
            $table->string('event_label');
            $table->string('event_label_ar');
            $table->string('recipient'); // admin | customer | merchant | agent
            $table->boolean('via_email')->default(false);
            $table->boolean('via_sms')->default(false);
            $table->boolean('via_push')->default(true);
            $table->boolean('via_in_app')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['event_key', 'recipient']);
        });

        // ── Section 4: SEO page meta for the marketing site ──
        Schema::create('page_meta', function (Blueprint $table) {
            $table->id();
            $table->string('page_key')->unique(); // home | about | features | pricing | faq | contact | privacy | terms
            $table->string('title')->nullable();
            $table->string('title_ar')->nullable();
            $table->text('description')->nullable();
            $table->text('description_ar')->nullable();
            $table->string('keywords')->nullable();
            $table->string('og_title')->nullable();
            $table->string('og_description')->nullable();
            $table->string('og_image')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('robots')->default('index,follow');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── Section 3: link existing notification_templates to events ──
        Schema::table('notification_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('notification_templates', 'event_key')) {
                $table->string('event_key')->nullable()->after('code');
            }
            if (!Schema::hasColumn('notification_templates', 'recipient')) {
                $table->string('recipient')->default('customer')->after('event_key');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_configs');
        Schema::dropIfExists('notification_channels');
        Schema::dropIfExists('page_meta');
        Schema::table('notification_templates', function (Blueprint $table) {
            $table->dropColumn(['event_key', 'recipient']);
        });
    }
};
