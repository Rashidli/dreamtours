<?php

use App\Support\StaffRoles;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** CRM §16: default staff roles and the permissions for finance visibility and trip assignments. */
return new class extends Migration
{
    public function up(): void
    {
        StaffRoles::install();
    }

    public function down(): void
    {
        // Roles may have users by now, so they stay; only the permissions added here are removed.
        DB::table('permissions')->whereIn('name', StaffRoles::PERMISSIONS)->delete();
    }
};
