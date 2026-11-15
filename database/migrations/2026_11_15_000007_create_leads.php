<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lead pipeline (CRM requirements §3). Every website request becomes a lead with
 * its source and campaign; staff assign it, log calls and notes, set reminders,
 * move it through editable stages and convert it to a booking. Stages with a key
 * are used by automations (renaming them is safe); others are custom.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_stages', function (Blueprint $table) {
            $table->id();
            $table->string('key', 30)->nullable()->unique();   // new | assigned | contacted | awaiting_deposit | confirmed | lost | spam
            $table->string('name', 60);
            $table->string('color', 20)->default('secondary');
            $table->string('kind', 10)->default('open');       // open | won | lost | spam
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('lead_stage_id')->constrained('lead_stages');
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 20)->default('enquiry');      // booking | contact | callback | private_tour | tour | cruise | visa | manual …
            $table->foreignId('request_booking_id')->nullable()->constrained('bookings')->nullOnDelete(); // the original website request
            $table->foreignId('visa_enquiry_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();                 // the booking it became
            $table->foreignId('tour_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tour_departure_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pickup_point_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('guests')->nullable();
            $table->string('first_name', 100);
            $table->string('last_name', 100)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('whatsapp', 50)->nullable();
            $table->text('message')->nullable();
            $table->string('source', 40)->default('website');    // website | phone | walk_in | email | whatsapp | facebook | referral | other
            $table->string('utm_source', 120)->nullable();
            $table->string('utm_medium', 120)->nullable();
            $table->string('utm_campaign', 150)->nullable();
            $table->string('utm_term', 150)->nullable();
            $table->string('utm_content', 150)->nullable();
            $table->string('landing_page', 500)->nullable();
            $table->string('referrer', 500)->nullable();
            $table->string('lost_reason')->nullable();
            $table->timestamp('next_follow_up_at')->nullable()->index();
            $table->string('follow_up_note')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->unsignedSmallInteger('contact_attempts')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['lead_stage_id', 'created_at']);
        });

        Schema::create('lead_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 12)->default('note');         // note | call | email | whatsapp | sms | meeting
            $table->string('outcome', 20)->nullable();           // reached | no_answer | left_message | wrong_number
            $table->text('body')->nullable();
            $table->timestamps();
        });

        Schema::create('lead_tag', function (Blueprint $table) {
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['lead_id', 'customer_tag_id']);
        });

        $now = now();
        foreach ([
            ['new', 'New', 'primary', 'open'], ['assigned', 'Assigned', 'info', 'open'], ['contacted', 'Contacted', 'warning', 'open'],
            ['awaiting_deposit', 'Awaiting deposit', 'secondary', 'won'], ['confirmed', 'Booking confirmed', 'success', 'won'],
            ['lost', 'Lost', 'danger', 'lost'], ['spam', 'Spam', 'dark', 'spam'],
        ] as $i => [$key, $name, $color, $kind]) {
            DB::table('lead_stages')->insert(['key' => $key, 'name' => $name, 'color' => $color, 'kind' => $kind, 'sort_order' => $i + 1, 'created_at' => $now, 'updated_at' => $now]);
        }

        $permissions = ['list-leads', 'create-leads', 'edit-leads', 'delete-leads', 'manage-lead-stages'];
        foreach ($permissions as $name) {
            DB::table('permissions')->insertOrIgnore(['name' => $name, 'guard_name' => 'web', 'group_name' => 'Tours & CRM', 'created_at' => $now, 'updated_at' => $now]);
        }
        if ($admin = DB::table('roles')->where(['name' => 'admin', 'guard_name' => 'web'])->value('id')) {
            foreach (DB::table('permissions')->whereIn('name', $permissions)->pluck('id') as $permissionId) {
                DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $admin]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_tag');
        Schema::dropIfExists('lead_notes');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('lead_stages');
        DB::table('permissions')->whereIn('name', ['list-leads', 'create-leads', 'edit-leads', 'delete-leads', 'manage-lead-stages'])->delete();
    }
};
