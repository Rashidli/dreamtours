<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CRM §15: in-app alerts for staff. One row per event (dedupe_key is unique, so the same event
 * never alerts twice); who sees it follows the permission needed to act on it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_alerts', function (Blueprint $t) {
            $t->id();
            $t->string('type', 40)->index();
            $t->string('dedupe_key', 150)->unique();
            $t->string('title');
            $t->string('body')->nullable();
            $t->string('url', 500)->nullable();
            $t->nullableMorphs('subject');
            $t->string('permission', 60);
            $t->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('completed_at')->nullable()->index();
            $t->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('completed_note')->nullable(); // e.g. "Payment confirmed" when the system closes it
            $t->timestamps();
        });

        $now = now();
        // Thresholds (CRM §15 "permit thresholds … to be configured"); editable in Settings › Staff alerts.
        DB::table('settings')->insertOrIgnore([
            ['key' => 'alert_almost_full_seats', 'value' => '5', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'alert_balance_due_days', 'value' => '3', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'alert_upcoming_tour_days', 'value' => '2', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'alerts_off', 'value' => '', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', ['alert_almost_full_seats', 'alert_balance_due_days', 'alert_upcoming_tour_days', 'alerts_off'])->delete();
        Schema::dropIfExists('staff_alerts');
    }
};
