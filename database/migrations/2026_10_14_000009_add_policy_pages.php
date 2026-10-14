<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Privacy Policy and Terms and Conditions pages, their footer links and the URLs the
 * booking consent checkboxes point to — all editable in admin. The wording is a
 * placeholder until DreamTour supplies its final policies.
 */
return new class extends Migration
{
    private const PAGES = [
        'privacy-policy' => ['Privacy Policy', 'Personal Data Protection and Privacy Policy', 'DreamTour’s Personal Data Protection and Privacy Policy will be published on this page.'],
        'terms-and-conditions' => ['Terms and Conditions', 'Terms and Conditions of Booking', 'DreamTour’s Terms and Conditions of Booking will be published on this page.'],
    ];

    public function up(): void
    {
        $order = (int) DB::table('pages')->max('sort_order');
        foreach (self::PAGES as $key => [$title, $heading, $text]) {
            if (DB::table('pages')->where('key', $key)->exists()) {
                continue;
            }
            $pageId = DB::table('pages')->insertGetId(['key' => $key, 'template' => 'policy', 'is_active' => true, 'sort_order' => ++$order, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('page_translations')->insert(['page_id' => $pageId, 'locale' => 'en', 'title' => $title, 'slug' => $key, 'meta_title' => null, 'meta_description' => null, 'meta_keywords' => null]);
            $sectionId = DB::table('page_sections')->insertGetId(['page_id' => $pageId, 'key' => 'content', 'type' => 'content', 'is_active' => true, 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('page_section_translations')->insert([
                'page_section_id' => $sectionId, 'locale' => 'en', 'title' => $heading,
                'body' => "<p>{$text}</p><p>The final wording is being prepared. For any question in the meantime, please contact us.</p>",
            ]);
        }

        $legalOrder = (int) DB::table('navigation_items')->where('location', 'footer_legal')->max('sort_order');
        foreach (['/privacy-policy' => 'Privacy Policy', '/terms-and-conditions' => 'Terms of Service'] as $url => $label) {
            if (DB::table('navigation_items')->where('location', 'footer_legal')->where('url', $url)->exists()) {
                continue;
            }
            $navId = DB::table('navigation_items')->insertGetId(['location' => 'footer_legal', 'url' => $url, 'target' => '_self', 'is_active' => true, 'sort_order' => ++$legalOrder, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('navigation_item_translations')->insert(['navigation_item_id' => $navId, 'locale' => 'en', 'title' => $label]);
        }

        foreach (['terms_url' => '/terms-and-conditions', 'privacy_url' => '/privacy-policy'] as $key => $value) {
            DB::table('settings')->updateOrInsert(['key' => $key], ['value' => $value, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        $pageIds = DB::table('pages')->whereIn('key', array_keys(self::PAGES))->pluck('id');
        $sectionIds = DB::table('page_sections')->whereIn('page_id', $pageIds)->pluck('id');
        DB::table('page_section_translations')->whereIn('page_section_id', $sectionIds)->delete();
        DB::table('page_sections')->whereIn('id', $sectionIds)->delete();
        DB::table('page_translations')->whereIn('page_id', $pageIds)->delete();
        DB::table('pages')->whereIn('id', $pageIds)->delete();
        $navIds = DB::table('navigation_items')->where('location', 'footer_legal')->whereIn('url', ['/privacy-policy', '/terms-and-conditions'])->pluck('id');
        DB::table('navigation_item_translations')->whereIn('navigation_item_id', $navIds)->delete();
        DB::table('navigation_items')->whereIn('id', $navIds)->delete();
        DB::table('settings')->whereIn('key', ['terms_url', 'privacy_url'])->delete();
    }
};
