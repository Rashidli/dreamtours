<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Tracking IDs for analytics / marketing tags, loaded only with the visitor's cookie consent. */
return new class extends Migration
{
    private const KEYS = ['google_analytics_id', 'google_tag_manager_id', 'meta_pixel_id'];

    public function up(): void
    {
        foreach (self::KEYS as $key) {
            DB::table('settings')->updateOrInsert(['key' => $key], ['value' => '', 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', self::KEYS)->delete();
    }
};
