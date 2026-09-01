<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedSmallInteger('age')->nullable()->after('date_of_birth');
        });
        Schema::table('tour_itinerary_items', function (Blueprint $table) {
            $table->json('images')->nullable()->after('video_url');
        });
        Schema::create('tour_pickup_point', function (Blueprint $table) {
            $table->foreignId('tour_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pickup_point_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->primary(['tour_id', 'pickup_point_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_pickup_point');
        Schema::table('tour_itinerary_items', fn (Blueprint $table) => $table->dropColumn('images'));
        Schema::table('bookings', fn (Blueprint $table) => $table->dropColumn('age'));
    }
};
