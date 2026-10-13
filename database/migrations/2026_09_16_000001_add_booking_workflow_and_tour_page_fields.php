<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Owner feedback, September 2026: step-by-step booking with deposit and payment proof,
 * room/child pricing per departure, inclusion icons, itinerary flags and moderated
 * per-tour reviews.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->decimal('deposit_per_person', 10, 2)->nullable()->after('activity_level');
        });
        Schema::table('tour_translations', function (Blueprint $table) {
            $table->text('deposit_policy')->nullable()->after('refund_policy');
        });

        // Per-departure price list: per-person prices (adult, child, senior) or a per-person
        // price for a shared room (single, double, triple, quad).
        Schema::create('tour_departure_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_departure_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['tour_departure_id', 'code']);
        });

        Schema::table('tour_content_items', function (Blueprint $table) {
            $table->string('icon', 16)->nullable()->after('type');
        });
        Schema::table('tour_itinerary_items', function (Blueprint $table) {
            $table->boolean('is_optional')->default(false)->after('entrance_included');
            $table->boolean('own_expense')->default(false)->after('is_optional');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('emergency_contact_name')->nullable()->after('address');
            $table->string('emergency_contact_phone', 50)->nullable()->after('emergency_contact_name');
            $table->text('roommate_request')->nullable()->after('special_requests');
            $table->string('currency', 3)->nullable()->after('source');
            $table->decimal('total_amount', 10, 2)->nullable()->after('currency');
            $table->decimal('deposit_amount', 10, 2)->nullable()->after('total_amount');
            $table->decimal('amount_paid', 10, 2)->default(0)->after('deposit_amount');
            $table->string('payment_status', 20)->default('unpaid')->index()->after('amount_paid');
            $table->string('payment_proof')->nullable()->after('payment_status');
            $table->timestamp('policies_accepted_at')->nullable()->after('payment_proof');
            $table->timestamp('confirmation_sent_at')->nullable()->after('policies_accepted_at');
            $table->timestamp('seat_notice_sent_at')->nullable()->after('confirmation_sent_at');
        });
        Schema::table('booking_passengers', function (Blueprint $table) {
            $table->string('price_code', 20)->nullable()->after('category');
            $table->decimal('price', 10, 2)->nullable()->after('price_code');
            $table->unsignedSmallInteger('room_number')->nullable()->after('price');
            $table->string('seat_number', 10)->nullable()->after('room_number');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('tour_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('email')->nullable()->after('image');
            $table->date('travel_date')->nullable()->after('email');
            $table->string('status', 20)->default('approved')->index()->after('rating');
        });
        // Every review that already existed was entered by an admin, so it counts as approved.
        DB::table('reviews')->update(['status' => 'approved']);
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tour_id');
            $table->dropColumn(['email', 'travel_date', 'status']);
        });
        Schema::table('booking_passengers', fn (Blueprint $table) => $table->dropColumn(['price_code', 'price', 'room_number', 'seat_number']));
        Schema::table('bookings', fn (Blueprint $table) => $table->dropColumn([
            'emergency_contact_name', 'emergency_contact_phone', 'roommate_request', 'currency', 'total_amount',
            'deposit_amount', 'amount_paid', 'payment_status', 'payment_proof', 'policies_accepted_at',
            'confirmation_sent_at', 'seat_notice_sent_at',
        ]));
        Schema::table('tour_itinerary_items', fn (Blueprint $table) => $table->dropColumn(['is_optional', 'own_expense']));
        Schema::table('tour_content_items', fn (Blueprint $table) => $table->dropColumn('icon'));
        Schema::dropIfExists('tour_departure_prices');
        Schema::table('tour_translations', fn (Blueprint $table) => $table->dropColumn('deposit_policy'));
        Schema::table('tours', fn (Blueprint $table) => $table->dropColumn('deposit_per_person'));
    }
};
