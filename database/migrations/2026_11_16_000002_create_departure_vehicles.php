<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CRM §5, §9: the buses of one departure, each with its own copy of a seating plan, blocked
 * seats and publish state. A passenger's seat is (departure_vehicle_id, seat_number) and the
 * unique index makes it impossible for two passengers to hold the same seat (AT-06).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departure_vehicles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tour_departure_id')->constrained()->cascadeOnDelete();
            $t->foreignId('vehicle_layout_id')->nullable()->constrained()->nullOnDelete();
            $t->string('label', 50);
            $t->string('note')->nullable(); // operator, plate, meeting point of the bus
            $t->json('layout');
            $t->json('blocked')->nullable();
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->timestamp('seats_changed_at')->nullable();
            $t->timestamp('published_at')->nullable();
            $t->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        Schema::table('booking_passengers', function (Blueprint $t) {
            $t->foreignId('departure_vehicle_id')->nullable()->after('room_number')->constrained()->nullOnDelete();
            // What the guest was last told ("Bus 1 · 12"), so publishing only emails real changes.
            $t->string('seat_notified', 70)->nullable()->after('seat_number');
            $t->unique(['departure_vehicle_id', 'seat_number']);
        });
        // A leader or driver can belong to one bus of the date.
        Schema::table('departure_staff', function (Blueprint $t) {
            $t->foreignId('departure_vehicle_id')->nullable()->after('tour_departure_id')->constrained()->nullOnDelete();
        });

        $now = now();
        DB::table('permissions')->insertOrIgnore(['name' => 'manage-seating', 'guard_name' => 'web', 'group_name' => 'Tours & CRM', 'created_at' => $now, 'updated_at' => $now]);
        if (($admin = DB::table('roles')->where(['name' => 'admin', 'guard_name' => 'web'])->value('id')) && ($permission = DB::table('permissions')->where('name', 'manage-seating')->value('id'))) {
            DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permission, 'role_id' => $admin]);
        }
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table('permissions')->where('name', 'manage-seating')->delete();
        Schema::table('departure_staff', fn (Blueprint $t) => $t->dropConstrainedForeignId('departure_vehicle_id'));
        Schema::table('booking_passengers', function (Blueprint $t) {
            $t->dropUnique(['departure_vehicle_id', 'seat_number']);
            $t->dropConstrainedForeignId('departure_vehicle_id');
            $t->dropColumn('seat_notified');
        });
        Schema::dropIfExists('departure_vehicles');
    }
};
