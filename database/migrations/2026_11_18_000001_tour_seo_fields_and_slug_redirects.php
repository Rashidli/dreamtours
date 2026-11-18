<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive only: SEO title/description per tour translation, and the old tour URLs
 * (slugs) that now 301 to the tour's current address, so changing a slug in admin
 * never breaks links already in Google, emails or social posts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tour_translations', function (Blueprint $table) {
            if (!Schema::hasColumn('tour_translations', 'meta_title')) {
                $table->string('meta_title')->nullable()->after('slug');
            }
            if (!Schema::hasColumn('tour_translations', 'meta_description')) {
                $table->text('meta_description')->nullable()->after('meta_title');
            }
        });

        if (!Schema::hasTable('tour_slug_redirects')) {
            Schema::create('tour_slug_redirects', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tour_id')->constrained()->cascadeOnDelete();
                $table->string('locale', 10)->default('en');
                $table->string('slug');
                $table->timestamps();
                $table->unique(['locale', 'slug']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_slug_redirects');
        Schema::table('tour_translations', function (Blueprint $table) {
            $table->dropColumn(['meta_title', 'meta_description']);
        });
    }
};
