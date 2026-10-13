<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * These section rows carried no editable copy: the blocks they named are either pure UI
 * (gallery filters), or driven by another admin screen entirely (the contact map comes from
 * Global settings, the visa disclaimer from the visa service records, the about statistics
 * from Company content). Leaving them listed made the admin advertise fields that changed
 * nothing on the site.
 */
return new class extends Migration
{
    private const ORPHANS = [
        'about' => ['statistics'],
        'contact' => ['map'],
        'visa-support' => ['disclaimer'],
        'gallery' => ['filters', 'albums'],
        'international-tours' => ['benefits'],
        'cruises' => ['benefits'],
    ];

    public function up(): void
    {
        foreach (self::ORPHANS as $pageKey => $sectionKeys) {
            $pageId = DB::table('pages')->where('key', $pageKey)->value('id');

            if (! $pageId) {
                continue;
            }

            $ids = DB::table('page_sections')
                ->where('page_id', $pageId)
                ->whereIn('key', $sectionKeys)
                ->pluck('id');

            DB::table('page_section_translations')->whereIn('page_section_id', $ids)->delete();
            DB::table('page_sections')->whereIn('id', $ids)->delete();
        }
    }

    public function down(): void
    {
        // Nothing to restore: the rows held no copy, and CmsSeeder no longer lists them.
    }
};
