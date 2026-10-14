<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Heading and on/off switch for the Private Tours "places" list; the places themselves are company content. */
return new class extends Migration
{
    public function up(): void
    {
        $pageId = DB::table('pages')->where('key', 'private-tours')->value('id');
        if (!$pageId || DB::table('page_sections')->where('page_id', $pageId)->where('key', 'places')->exists()) {
            return;
        }
        $id = DB::table('page_sections')->insertGetId([
            'page_id' => $pageId, 'key' => 'places', 'type' => 'content', 'is_active' => true, 'sort_order' => 3,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('page_section_translations')->insert([
            'page_section_id' => $id, 'locale' => 'en', 'eyebrow' => 'Where We Can Take You',
            'title' => 'Places For Your Private Tour', 'subtitle' => 'Pick a destination and we will plan the day around your group.',
        ]);
    }

    public function down(): void
    {
        $ids = DB::table('page_sections')->where('key', 'places')->pluck('id');
        DB::table('page_section_translations')->whereIn('page_section_id', $ids)->delete();
        DB::table('page_sections')->whereIn('id', $ids)->delete();
    }
};
