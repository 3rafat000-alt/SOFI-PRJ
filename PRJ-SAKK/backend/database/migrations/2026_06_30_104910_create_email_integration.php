<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

return new class extends Migration
{
    public function up(): void
    {
        $existing = DB::table('integrations')->where('key', 'email')->first();
        if ($existing) {
            return;
        }

        $messaging = DB::table('integrations')->where('key', 'messaging')->first();
        $mailCreds = [];

        if ($messaging && $messaging->credentials) {
            $raw = $messaging->credentials;

            // Model cast is `encrypted:array` -> Crypt::encryptString/decryptString
            // (NOT the raw encrypt/decrypt, which uses PHP serialize() under the
            // hood and produces a different ciphertext format the model cannot
            // read back). Try the current format first, then fall back to the
            // legacy serialized format for rows written before this fix.
            try {
                $all = json_decode(Crypt::decryptString($raw), true) ?? [];
            } catch (\Exception $e) {
                try {
                    $all = json_decode(Crypt::decrypt($raw), true) ?? [];
                } catch (\Exception $e2) {
                    $all = json_decode($raw, true) ?? [];
                }
            }

            $mailKeys = ['mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_from_address', 'mail_from_name'];
            foreach ($mailKeys as $k) {
                if (isset($all[$k])) {
                    $mailCreds[$k] = $all[$k];
                }
            }

            // Remove mail keys from messaging
            $keep = array_diff_key($all, array_flip($mailKeys));
            DB::table('integrations')
                ->where('id', $messaging->id)
                ->update(['credentials' => Crypt::encryptString(json_encode($keep))]);
        }

        $now = now();
        DB::table('integrations')->insert([
            'category' => 'messaging',
            'key' => 'email',
            'name' => 'Email',
            'name_ar' => 'البريد الإلكتروني',
            'description' => 'SMTP email delivery for transactional and marketing emails',
            'description_ar' => 'إرسال البريد الإلكتروني للمعاملات والتسويق عبر SMTP',
            'icon' => 'mail',
            'environment' => 'sandbox',
            'is_active' => true,
            'credentials' => Crypt::encryptString(json_encode($mailCreds ?: [
                'mail_host' => '',
                'mail_port' => '587',
                'mail_username' => '',
                'mail_password' => '',
                'mail_from_address' => '',
                'mail_from_name' => 'SAKK',
            ])),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $emailId = DB::table('integrations')->where('key', 'email')->first()->id;
        DB::table('integration_logs')->insert([
            'integration_id' => $emailId,
            'level' => 'success',
            'action' => 'config_update',
            'message' => 'تم إنشاء تكامل البريد الإلكتروني',
            'created_at' => $now,
        ]);
    }

    public function down(): void
    {
        $email = DB::table('integrations')->where('key', 'email')->first();
        if (!$email) return;

        try {
            $mailCreds = json_decode(Crypt::decryptString($email->credentials), true) ?? [];
        } catch (\Exception $e) {
            try {
                $mailCreds = json_decode(Crypt::decrypt($email->credentials), true) ?? [];
            } catch (\Exception $e2) {
                $mailCreds = json_decode($email->credentials, true) ?? [];
            }
        }

        $messaging = DB::table('integrations')->where('key', 'messaging')->first();
        if ($messaging && $messaging->credentials) {
            try {
                $all = json_decode(Crypt::decryptString($messaging->credentials), true) ?? [];
            } catch (\Exception $e) {
                try {
                    $all = json_decode(Crypt::decrypt($messaging->credentials), true) ?? [];
                } catch (\Exception $e2) {
                    $all = json_decode($messaging->credentials, true) ?? [];
                }
            }
            foreach ($mailCreds as $k => $v) {
                $all[$k] = $v;
            }
            DB::table('integrations')
                ->where('id', $messaging->id)
                ->update(['credentials' => Crypt::encryptString(json_encode($all))]);
        }

        DB::table('integrations')->where('key', 'email')->delete();
    }
};
