<?php

use App\Models\VehicleLayout;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CRM §9: reusable seating layouts ("8-seat minivan", "56-seat coach"), drawn as a grid of
 * seats, aisle, door, driver and leader seats. A departure copies a layout when a bus is added,
 * so editing a template later never moves anybody's seat.
 */
return new class extends Migration
{
    private const PERMISSIONS = ['list-vehicle-layouts', 'create-vehicle-layouts', 'edit-vehicle-layouts', 'delete-vehicle-layouts'];

    public function up(): void
    {
        Schema::create('vehicle_layouts', function (Blueprint $t) {
            $t->id();
            $t->string('name', 100);
            $t->foreignId('transportation_id')->nullable()->constrained()->nullOnDelete();
            $t->json('layout');
            $t->unsignedSmallInteger('seat_count')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });

        $now = now();
        // Starting templates; staff adjust them (or draw their own) in Vehicles & seating.
        foreach (['Minivan' => VehicleLayout::generate(2, 1, 1, true), 'Sprinter' => VehicleLayout::generate(4, 1, 2, true), 'Coach' => VehicleLayout::generate(14, 2, 2)] as $kind => $layout) {
            DB::table('vehicle_layouts')->insert(['name' => $kind.' ('.VehicleLayout::countSeats($layout).' seats)', 'layout' => json_encode($layout), 'seat_count' => VehicleLayout::countSeats($layout), 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        }

        foreach (self::PERMISSIONS as $name) {
            DB::table('permissions')->insertOrIgnore(['name' => $name, 'guard_name' => 'web', 'group_name' => 'Tours & CRM', 'created_at' => $now, 'updated_at' => $now]);
        }
        if ($admin = DB::table('roles')->where(['name' => 'admin', 'guard_name' => 'web'])->value('id')) {
            foreach (DB::table('permissions')->whereIn('name', self::PERMISSIONS)->pluck('id') as $permissionId) {
                DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $admin]);
            }
        }
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('name', self::PERMISSIONS)->delete();
        Schema::dropIfExists('vehicle_layouts');
    }
};
