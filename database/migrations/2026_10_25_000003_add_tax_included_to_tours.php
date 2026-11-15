<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-tour choice: the price already includes taxes, or taxes are added on top
 * (the "+ TAX" note from Global settings). Bookings keep the choice made when they
 * were submitted, so their emails and pages stay consistent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tours', fn (Blueprint $table) => $table->boolean('tax_included')->default(false)->after('deposit_per_person'));
        Schema::table('bookings', fn (Blueprint $table) => $table->boolean('tax_included')->default(false)->after('currency'));
    }

    public function down(): void
    {
        Schema::table('tours', fn (Blueprint $table) => $table->dropColumn('tax_included'));
        Schema::table('bookings', fn (Blueprint $table) => $table->dropColumn('tax_included'));
    }
};
