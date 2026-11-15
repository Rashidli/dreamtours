<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-tour switch for the homepage hero carousel, independent of "Show on home".
 * Flagged published tours appear as slides (cover image, name, link) ahead of the
 * Company content hero slides. Existing tours start switched off.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tours', fn (Blueprint $table) => $table->boolean('show_in_carousel')->default(false)->after('show_on_home'));
    }

    public function down(): void
    {
        Schema::table('tours', fn (Blueprint $table) => $table->dropColumn('show_in_carousel'));
    }
};
