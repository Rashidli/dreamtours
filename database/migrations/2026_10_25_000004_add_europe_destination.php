<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A "Europe" continent in Destinations, so countries such as France or Italy can be
 * added under it from admin (Europe › France › Paris). No tour uses it yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('destination_translations')->where('locale', 'en')->where('name', 'Europe')->exists()) {
            return;
        }
        $id = DB::table('destinations')->insertGetId(['parent_id' => null, 'type' => 'continent', 'is_active' => true, 'sort_order' => (int) DB::table('destinations')->max('sort_order') + 1, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('destination_translations')->insert(['destination_id' => $id, 'locale' => 'en', 'name' => 'Europe', 'slug' => 'europe', 'description' => null]);
    }

    public function down(): void
    {
        $id = DB::table('destination_translations')->where('locale', 'en')->where('slug', 'europe')->value('destination_id');
        if ($id && !DB::table('tours')->where('destination_id', $id)->exists() && !DB::table('destinations')->where('parent_id', $id)->exists()) {
            DB::table('destination_translations')->where('destination_id', $id)->delete();
            DB::table('destinations')->where('id', $id)->delete();
        }
    }
};
