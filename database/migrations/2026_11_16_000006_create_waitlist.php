<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CRM §8: a waitlist per departure, in order (position), with what happened to each entry:
 * waiting → offered (a seat was offered) → booked (linked to the booking) or removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waitlist_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tour_departure_id')->constrained()->cascadeOnDelete();
            $t->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $t->string('first_name', 100);
            $t->string('last_name', 100);
            $t->string('email')->nullable();
            $t->string('phone', 30)->nullable();
            $t->unsignedSmallInteger('guests')->default(1);
            $t->string('note', 500)->nullable();
            $t->string('source', 20)->default('website'); // website | staff
            $t->unsignedInteger('position')->default(0);
            $t->string('status', 20)->default('waiting')->index();
            $t->timestamp('offered_at')->nullable();
            $t->foreignId('offered_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $t->string('removed_reason')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['tour_departure_id', 'status', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waitlist_entries');
    }
};
