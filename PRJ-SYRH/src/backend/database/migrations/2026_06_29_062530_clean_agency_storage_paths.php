<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Null logo_path and cover_path for agencies whose storage files don't exist.
        // Seeded SVGs (sharkat-bitar.svg etc.) exist only locally, not on production.
        // Frontend falls back to first-letter display when logo_path/cover_path is null.
        DB::table('agencies')->get()->each(function ($a) {
            $updates = [];
            if ($a->logo_path && !file_exists(storage_path('app/public/' . ltrim($a->logo_path, '/')))) {
                $updates['logo_path'] = null;
            }
            if ($a->cover_path && !file_exists(storage_path('app/public/' . ltrim($a->cover_path, '/')))) {
                $updates['cover_path'] = null;
            }
            if (!empty($updates)) {
                DB::table('agencies')->where('id', $a->id)->update($updates);
            }
        });
    }

    public function down(): void
    {
        // No rollback — data already existed; this only removes broken paths.
    }
};
