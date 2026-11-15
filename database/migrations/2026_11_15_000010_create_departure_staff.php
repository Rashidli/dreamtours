<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CRM §5, §12, §16: who works each departure (leader, driver, guide, coordinator).
 * The assignment, not the role, is what opens a trip to its leader; deleting it revokes access.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departure_staff', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tour_departure_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('role', 20);
            $t->string('note')->nullable();
            $t->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['tour_departure_id', 'user_id', 'role']);
            $t->index(['user_id', 'tour_departure_id']);
        });
        // Staff phone numbers, shown to the rest of a trip's team.
        Schema::table('users', function (Blueprint $t) {
            $t->string('phone', 50)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('phone'));
        Schema::dropIfExists('departure_staff');
    }
};
