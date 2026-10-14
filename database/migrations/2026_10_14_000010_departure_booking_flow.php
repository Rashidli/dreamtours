<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Scheduled-trip booking flow:
 *  - departures count their seats from live bookings plus seats held offline (held_seats),
 *    and can offer their own pickup points;
 *  - tours carry per-passenger meal choices and an optional waiver;
 *  - bookings record each consent separately, the waiver as agreed, staff notes and a
 *    one-time submission token against double submits.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tour_departures', function (Blueprint $table) {
            $table->unsignedInteger('held_seats')->default(0)->after('reserved_seats');
        });
        // Seats reserved so far that no booking accounts for were held by staff; keep them held.
        foreach (DB::table('tour_departures')->get() as $d) {
            $booked = (int) DB::table('bookings')->where('tour_departure_id', $d->id)->where('status', '!=', 'cancelled')->sum('participant_count');
            DB::table('tour_departures')->where('id', $d->id)->update(['held_seats' => max(0, $d->reserved_seats - $booked), 'reserved_seats' => max($d->reserved_seats, $booked)]);
        }

        Schema::create('tour_departure_pickup_point', function (Blueprint $table) {
            $table->foreignId('tour_departure_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pickup_point_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->primary(['tour_departure_id', 'pickup_point_id']);
        });

        Schema::table('tour_translations', function (Blueprint $table) {
            $table->text('meal_options')->nullable();
            $table->string('waiver_title')->nullable();
            $table->longText('waiver')->nullable();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('terms_accepted_at')->nullable()->after('policies_accepted_at');
            $table->timestamp('privacy_accepted_at')->nullable()->after('terms_accepted_at');
            $table->boolean('marketing_consent')->default(false)->after('privacy_accepted_at');
            $table->timestamp('waiver_accepted_at')->nullable()->after('marketing_consent');
            $table->longText('waiver_text')->nullable()->after('waiver_accepted_at');
            $table->text('internal_notes')->nullable()->after('comments');
            $table->string('submission_token', 64)->nullable()->unique()->after('reference');
            $table->foreignId('updated_by')->nullable()->after('seat_notice_sent_at')->constrained('users')->nullOnDelete();
        });
        // Bookings made so far accepted the policies in one checkbox.
        DB::table('bookings')->whereNotNull('policies_accepted_at')->update(['terms_accepted_at' => DB::raw('policies_accepted_at')]);
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn(['terms_accepted_at', 'privacy_accepted_at', 'marketing_consent', 'waiver_accepted_at', 'waiver_text', 'internal_notes', 'submission_token']);
        });
        Schema::table('tour_translations', fn (Blueprint $table) => $table->dropColumn(['meal_options', 'waiver_title', 'waiver']));
        Schema::dropIfExists('tour_departure_pickup_point');
        Schema::table('tour_departures', fn (Blueprint $table) => $table->dropColumn('held_seats'));
    }
};
