<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** CRM §25: permission-controlled exports of the lead and booking lists. */
return new class extends Migration
{
    private const GRANTS = ['admin' => ['export-leads', 'export-bookings'], 'Accountant' => ['export-bookings']];

    public function up(): void
    {
        $now = now();
        foreach (['export-leads', 'export-bookings'] as $name) {
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
        DB::table('permissions')->whereIn('name', ['export-leads', 'export-bookings'])->delete();
    }
};
