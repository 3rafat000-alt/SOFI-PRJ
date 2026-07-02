<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // users - phone is used for login but has no index (email is UNIQUE so already indexed)
        Schema::table('users', function (Blueprint $table) {
            $table->index('phone', 'idx_users_phone');
            $table->index(['kyc_level', 'kyc_status'], 'idx_users_kyc_level_status');
        });

        // transactions - recipient_id is queried but no index
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['recipient_id', 'created_at'], 'idx_trans_recipient_created');
            $table->index('external_reference', 'idx_trans_external_ref');
        });

        // activity_logs - user_id + created_at is common query
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'idx_activity_user_created');
        });

        // system_settings - group is queried
        Schema::table('system_settings', function (Blueprint $table) {
            $table->index('group', 'idx_settings_group');
        });

        // devices - status is queried
        Schema::table('devices', function (Blueprint $table) {
            $table->index('status', 'idx_devices_status');
        });

        // card_pricing - brand+type unique needed
        Schema::table('card_pricing', function (Blueprint $table) {
            $table->unique(['brand', 'type'], 'uq_card_pricing_brand_type');
        });

        // integration_logs - integration_id + action + created_at
        Schema::table('integration_logs', function (Blueprint $table) {
            $table->index(['integration_id', 'created_at'], 'idx_integration_logs_integration_created');
        });

        // admin_notifications - status + created_at
        Schema::table('admin_notifications', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'idx_admin_notifications_status_created');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_phone');
            $table->dropIndex('idx_users_kyc_level_status');
        });
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_trans_recipient_created');
            $table->dropIndex('idx_trans_external_ref');
        });
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('idx_activity_user_created');
        });
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropIndex('idx_settings_group');
        });
        Schema::table('devices', function (Blueprint $table) {
            $table->dropIndex('idx_devices_status');
        });
        Schema::table('card_pricing', function (Blueprint $table) {
            $table->dropUnique('uq_card_pricing_brand_type');
        });
        Schema::table('integration_logs', function (Blueprint $table) {
            $table->dropIndex('idx_integration_logs_integration_created');
        });
        Schema::table('admin_notifications', function (Blueprint $table) {
            $table->dropIndex('idx_admin_notifications_status_created');
        });
    }
};
