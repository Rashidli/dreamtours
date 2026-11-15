<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adults, children and infants: each tour sets the age limits and whether an infant
 * takes a seat; each date can carry an "infant" price (0 = free). A booking now keeps
 * its passenger count and the seats it occupies separately — a lap infant is a
 * passenger without a seat — and the departure counts seats.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->unsignedTinyInteger('infant_max_age')->default(1)->after('age_restriction');
            $table->unsignedTinyInteger('child_max_age')->default(11)->after('infant_max_age');
            $table->boolean('infant_needs_seat')->default(false)->after('child_max_age');
        });
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedInteger('seat_count')->default(0)->after('participant_count');
        });
        DB::table('bookings')->update(['seat_count' => DB::raw('participant_count')]);
    }

    public function down(): void
    {
        Schema::table('tours', fn (Blueprint $table) => $table->dropColumn(['infant_max_age', 'child_max_age', 'infant_needs_seat']));
        Schema::table('bookings', fn (Blueprint $table) => $table->dropColumn('seat_count'));
    }
};
