<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CRM §11, AT-08: attendance on each passenger (current state) plus every mark as an event,
 * so the exact time, the person and any undo stay visible.
 */
return new class extends Migration
{
    private const PERMISSIONS = ['check-in-passengers', 'undo-check-in'];

    public function up(): void
    {
        Schema::table('booking_passengers', function (Blueprint $t) {
            $t->string('attendance_status', 20)->nullable()->after('notes')->index(); // null = expected
            $t->timestamp('attendance_at')->nullable()->after('attendance_status');
            $t->foreignId('attendance_by')->nullable()->after('attendance_at')->constrained('users')->nullOnDelete();
            $t->string('attendance_note')->nullable()->after('attendance_by');
        });
        Schema::create('passenger_attendance_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('booking_passenger_id')->constrained()->cascadeOnDelete();
            $t->foreignId('tour_departure_id')->nullable()->constrained()->nullOnDelete();
            $t->string('status', 20)->nullable(); // null = back to expected
            $t->string('previous_status', 20)->nullable();
            $t->string('note')->nullable();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['tour_departure_id', 'created_at']);
        });

        $now = now();
        foreach (self::PERMISSIONS as $name) {
            DB::table('permissions')->insertOrIgnore(['name' => $name, 'guard_name' => 'web', 'group_name' => 'Tours & CRM', 'created_at' => $now, 'updated_at' => $now]);
        }
        // Admins get both; leaders check people in (correcting others' marks needs undo-check-in).
        foreach (['admin' => self::PERMISSIONS, 'Tour Leader' => ['check-in-passengers']] as $role => $permissions) {
            if ($roleId = DB::table('roles')->where(['name' => $role, 'guard_name' => 'web'])->value('id')) {
                foreach (DB::table('permissions')->whereIn('name', $permissions)->pluck('id') as $permissionId) {
                    DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $roleId]);
                }
            }
        }
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('name', self::PERMISSIONS)->delete();
        Schema::dropIfExists('passenger_attendance_events');
        Schema::table('booking_passengers', function (Blueprint $t) {
            $t->dropConstrainedForeignId('attendance_by');
            $t->dropColumn(['attendance_status', 'attendance_at', 'attendance_note']);
        });
    }
};
