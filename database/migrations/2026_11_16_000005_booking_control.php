<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CRM §6, §8: staff create bookings directly, move or cancel one passenger (split into a booking
 * of their own, linked back to the original), and overbook only with an authorised override.
 */
return new class extends Migration
{
    private const GRANTS = ['admin' => ['create-bookings', 'override-capacity'], 'Sales Manager' => ['create-bookings']];

    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $t) {
            $t->foreignId('split_from_booking_id')->nullable()->after('customer_id')->constrained('bookings')->nullOnDelete();
        });
        $now = now();
        foreach (['create-bookings', 'override-capacity'] as $name) {
            DB::table('permissions')->insertOrIgnore(['name' => $name, 'guard_name' => 'web', 'group_name' => 'Tours & CRM', 'created_at' => $now, 'updated_at' => $now]);
        }
        foreach (self::GRANTS as $role => $permissions) {
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
        DB::table('permissions')->whereIn('name', ['create-bookings', 'override-capacity'])->delete();
        Schema::table('bookings', fn (Blueprint $t) => $t->dropConstrainedForeignId('split_from_booking_id'));
    }
};
