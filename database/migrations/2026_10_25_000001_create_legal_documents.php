<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Central legal documents — Privacy Policy, Terms and Conditions, Refund & Cancellation
 * Policy and Waiver — written once in admin and applied to every tour and booking.
 * Each saved change becomes a new version; bookings point at the exact versions the
 * customer accepted, so older acceptances never change when a document is edited.
 */
return new class extends Migration
{
    private const DOCUMENTS = [
        'privacy' => ['privacy-policy', 'Privacy Policy', 'Personal Data Protection and Privacy Policy', false, true],
        'terms' => ['terms-and-conditions', 'Terms and Conditions', 'Terms and Conditions of Booking', false, true],
        'refund' => ['refund-policy', 'Refund & Cancellation Policy', 'Refund and Cancellation Policy', true, true],
        'waiver' => ['waiver', 'Waiver', 'Release of Liability and Assumption of Risk', true, true],
    ];

    public function up(): void
    {
        Schema::create('legal_documents', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('heading')->nullable();
            $table->longText('body')->nullable();
            $table->boolean('is_published')->default(true);
            $table->boolean('show_on_tours')->default(false);
            $table->boolean('require_acceptance')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('legal_document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('title');
            $table->string('heading')->nullable();
            $table->longText('body')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->unique(['legal_document_id', 'version']);
        });

        Schema::create('booking_legal_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('legal_document_version_id')->constrained()->restrictOnDelete();
            $table->timestamp('accepted_at');
            $table->unique(['booking_id', 'legal_document_version_id'], 'booking_legal_unique');
        });

        // Privacy and Terms already have text on their policy pages; carry it over.
        $existing = DB::table('pages')
            ->join('page_sections', fn ($j) => $j->on('page_sections.page_id', '=', 'pages.id')->where('page_sections.key', 'content'))
            ->join('page_section_translations', fn ($j) => $j->on('page_section_translations.page_section_id', '=', 'page_sections.id')->where('locale', 'en'))
            ->whereIn('pages.key', ['privacy-policy', 'terms-and-conditions'])
            ->get(['pages.key', 'page_section_translations.title', 'page_section_translations.body'])
            ->keyBy('key');

        $placeholders = [
            'refund' => '<p>DreamTour’s Refund and Cancellation Policy will be published on this page. It applies to every DreamTour trip unless a trip page states otherwise.</p><p>The final wording is being prepared. For any question in the meantime, please contact us.</p>',
            'waiver' => '<p>By booking a DreamTour trip you confirm that you take part voluntarily, understand that travel and outdoor activities carry risks, will follow the instructions of the tour leader and driver, and are responsible for your own belongings and health needs.</p><p>The final waiver wording is being prepared by DreamTour.</p>',
        ];

        $order = 0;
        foreach (self::DOCUMENTS as $key => [$slug, $title, $heading, $showOnTours, $require]) {
            $page = $existing->get($slug);
            $body = $page->body ?? ($placeholders[$key] ?? '<p>This page is being prepared.</p>');
            $docHeading = $page->title ?? $heading;
            $id = DB::table('legal_documents')->insertGetId([
                'key' => $key, 'slug' => $slug, 'title' => $title, 'heading' => $docHeading, 'body' => $body,
                'is_published' => true, 'show_on_tours' => $showOnTours, 'require_acceptance' => $require,
                'sort_order' => ++$order, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('legal_document_versions')->insert([
                'legal_document_id' => $id, 'version' => 1, 'title' => $title, 'heading' => $docHeading, 'body' => $body, 'created_at' => now(),
            ]);
        }

        // Refund policy and waiver get public pages like the other two (SEO title in Pages & SEO).
        $pageOrder = (int) DB::table('pages')->max('sort_order');
        foreach (['refund-policy' => 'Refund & Cancellation Policy', 'waiver' => 'Waiver'] as $slug => $title) {
            if (DB::table('pages')->where('key', $slug)->exists()) {
                continue;
            }
            $pageId = DB::table('pages')->insertGetId(['key' => $slug, 'template' => 'policy', 'is_active' => true, 'sort_order' => ++$pageOrder, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('page_translations')->insert(['page_id' => $pageId, 'locale' => 'en', 'title' => $title, 'slug' => $slug, 'meta_title' => null, 'meta_description' => null, 'meta_keywords' => null]);
        }

        // The text now lives in Legal documents; the old page sections would only confuse editors.
        $sectionIds = DB::table('page_sections')->join('pages', 'pages.id', '=', 'page_sections.page_id')
            ->whereIn('pages.key', ['privacy-policy', 'terms-and-conditions'])->where('page_sections.key', 'content')->pluck('page_sections.id');
        DB::table('page_section_translations')->whereIn('page_section_id', $sectionIds)->delete();
        DB::table('page_sections')->whereIn('id', $sectionIds)->delete();

        $legalOrder = (int) DB::table('navigation_items')->where('location', 'footer_legal')->max('sort_order');
        if (!DB::table('navigation_items')->where('location', 'footer_legal')->where('url', '/refund-policy')->exists()) {
            $navId = DB::table('navigation_items')->insertGetId(['location' => 'footer_legal', 'url' => '/refund-policy', 'target' => '_self', 'is_active' => true, 'sort_order' => ++$legalOrder, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('navigation_item_translations')->insert(['navigation_item_id' => $navId, 'locale' => 'en', 'title' => 'Refund Policy']);
        }

        // Booking links now come from the documents themselves.
        DB::table('settings')->whereIn('key', ['terms_url', 'privacy_url'])->delete();

        foreach (['list', 'edit'] as $verb) {
            $id = DB::table('permissions')->insertGetId(['name' => "$verb-legal-documents", 'guard_name' => 'web', 'group_name' => 'Website CMS', 'created_at' => now(), 'updated_at' => now()]);
            $roles = DB::table('role_has_permissions')->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                ->where('permissions.name', "$verb-pages")->pluck('role_has_permissions.role_id');
            foreach ($roles as $roleId) {
                DB::table('role_has_permissions')->insert(['permission_id' => $id, 'role_id' => $roleId]);
            }
        }
        app()['cache']->forget('spatie.permission.cache');
    }

    public function down(): void
    {
        $docs = DB::table('legal_documents')->whereIn('key', ['privacy', 'terms'])->get()->keyBy('key');
        foreach (['privacy' => 'privacy-policy', 'terms' => 'terms-and-conditions'] as $key => $pageKey) {
            $pageId = DB::table('pages')->where('key', $pageKey)->value('id');
            if ($pageId && ($doc = $docs->get($key))) {
                $sectionId = DB::table('page_sections')->insertGetId(['page_id' => $pageId, 'key' => 'content', 'type' => 'content', 'is_active' => true, 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()]);
                DB::table('page_section_translations')->insert(['page_section_id' => $sectionId, 'locale' => 'en', 'title' => $doc->heading, 'body' => $doc->body]);
            }
        }
        foreach (['terms_url' => '/terms-and-conditions', 'privacy_url' => '/privacy-policy'] as $key => $value) {
            DB::table('settings')->updateOrInsert(['key' => $key], ['value' => $value, 'created_at' => now(), 'updated_at' => now()]);
        }
        $pageIds = DB::table('pages')->whereIn('key', ['refund-policy', 'waiver'])->pluck('id');
        DB::table('page_translations')->whereIn('page_id', $pageIds)->delete();
        DB::table('pages')->whereIn('id', $pageIds)->delete();
        $navIds = DB::table('navigation_items')->where('location', 'footer_legal')->where('url', '/refund-policy')->pluck('id');
        DB::table('navigation_item_translations')->whereIn('navigation_item_id', $navIds)->delete();
        DB::table('navigation_items')->whereIn('id', $navIds)->delete();
        DB::table('permissions')->whereIn('name', ['list-legal-documents', 'edit-legal-documents'])->delete();
        app()['cache']->forget('spatie.permission.cache');
        Schema::dropIfExists('booking_legal_acceptances');
        Schema::dropIfExists('legal_document_versions');
        Schema::dropIfExists('legal_documents');
    }
};
