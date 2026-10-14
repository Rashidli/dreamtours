<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Settings for the shared motto band and the footer Destinations switch, and the
 * homepage visa-support introduction section, all editable in admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['motto' => 'Don’t just dream places — go and see them.', 'footer_show_destinations' => '1'] as $key => $value) {
            DB::table('settings')->updateOrInsert(['key' => $key], ['value' => $value, 'created_at' => now(), 'updated_at' => now()]);
        }

        $homeId = DB::table('pages')->where('key', 'home')->value('id');
        if ($homeId && !DB::table('page_sections')->where('page_id', $homeId)->where('key', 'visa_intro')->exists()) {
            $id = DB::table('page_sections')->insertGetId([
                'page_id' => $homeId, 'key' => 'visa_intro', 'type' => 'content', 'button_url' => '/visa-support',
                'is_active' => true, 'sort_order' => 8, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('page_section_translations')->insert([
                'page_section_id' => $id, 'locale' => 'en', 'eyebrow' => 'Visa Support', 'title' => 'Need help with your visa?',
                'subtitle' => 'We help you complete application forms, check your documents for completeness and book visa appointments.',
                'button_text' => 'More Information',
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', ['motto', 'footer_show_destinations'])->delete();
        $ids = DB::table('page_sections')->where('key', 'visa_intro')->pluck('id');
        DB::table('page_section_translations')->whereIn('page_section_id', $ids)->delete();
        DB::table('page_sections')->whereIn('id', $ids)->delete();
    }
};
