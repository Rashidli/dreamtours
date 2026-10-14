<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Staff-managed filters for the One Day and Multi Day listings: groups (e.g. "Theme")
 * with options (e.g. "Nature", "Food & wine") that are assigned to tours.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_filters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('listing')->default('both'); // one-day, multi-day or both
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('tour_filter_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_filter_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('tour_filter_option_tour', function (Blueprint $table) {
            $table->foreignId('tour_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tour_filter_option_id')->constrained()->cascadeOnDelete();
            $table->primary(['tour_id', 'tour_filter_option_id']);
        });

        foreach (['list', 'create', 'edit', 'delete'] as $verb) {
            $id = DB::table('permissions')->insertGetId(['name' => "$verb-tour-filters", 'guard_name' => 'web', 'group_name' => 'Tours & CRM', 'created_at' => now(), 'updated_at' => now()]);
            $roles = DB::table('role_has_permissions')->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                ->where('permissions.name', "$verb-tour-categories")->pluck('role_has_permissions.role_id');
            foreach ($roles as $roleId) {
                DB::table('role_has_permissions')->insert(['permission_id' => $id, 'role_id' => $roleId]);
            }
        }
        app()['cache']->forget('spatie.permission.cache');
    }

    public function down(): void
    {
        DB::table('permissions')->where('name', 'like', '%-tour-filters')->delete();
        Schema::dropIfExists('tour_filter_option_tour');
        Schema::dropIfExists('tour_filter_options');
        Schema::dropIfExists('tour_filters');
    }
};
