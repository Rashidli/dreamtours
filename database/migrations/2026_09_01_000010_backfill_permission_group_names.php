<?php

use App\Support\PermissionGroups;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')->select('id', 'name')->orderBy('id')->chunk(200, function ($permissions) {
            foreach ($permissions as $permission) {
                DB::table('permissions')
                    ->where('id', $permission->id)
                    ->update(['group_name' => PermissionGroups::sectionFor($permission->name)]);
            }
        });
    }

    public function down(): void
    {
        DB::table('permissions')->update(['group_name' => null]);
    }
};
