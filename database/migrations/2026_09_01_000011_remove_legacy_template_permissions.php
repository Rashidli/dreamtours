<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * The blogs, portfolios, products, services, sliders, singles, tags and images admin
 * sections came from another project's template and have been removed, so the
 * permissions guarding them no longer point at anything.
 */
return new class extends Migration
{
    private const RESOURCES = ['services', 'blogs', 'portfolios', 'tags', 'sliders', 'singles', 'images'];

    public function up(): void
    {
        $ids = DB::table('permissions')->whereIn('name', $this->names())->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Recreated unassigned; the role membership was template noise and is not restored.
        foreach ($this->names() as $name) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name, 'guard_name' => 'web'],
                ['group_name' => 'Digər', 'created_at' => now(), 'updated_at' => now()],
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @return list<string> exact permission names, so visa-services is never matched */
    private function names(): array
    {
        $names = [];

        foreach (self::RESOURCES as $resource) {
            foreach (['list', 'create', 'edit', 'delete'] as $verb) {
                $names[] = "$verb-$resource";
            }
        }

        return $names;
    }
};
