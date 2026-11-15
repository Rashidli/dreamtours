<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bookings remember when the "booking confirmed" email went out, so switching the
 * status back and forth never sends it twice. New booking and enquiry alerts go to
 * the "Notification email" setting (comma-separated for several people).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', fn (Blueprint $table) => $table->timestamp('confirmed_notice_sent_at')->nullable()->after('confirmation_sent_at'));
        DB::table('settings')->insertOrIgnore(['key' => 'notification_email', 'value' => 'jenn@dreamtourpa.com', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('bookings', fn (Blueprint $table) => $table->dropColumn('confirmed_notice_sent_at'));
        DB::table('settings')->where('key', 'notification_email')->delete();
    }
};
