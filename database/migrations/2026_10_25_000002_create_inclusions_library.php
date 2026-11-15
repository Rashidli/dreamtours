<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Shared "What's included / not included" items: written once in Tours & CRM →
 * Inclusions and ticked on each tour, so editing one updates every tour using it.
 * Existing per-tour included/excluded lines move into the library; identical lines
 * (same type, icon, title and description) become one shared item.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inclusions', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20); // included | excluded
            $table->string('icon', 16)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('inclusion_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inclusion_id')->constrained()->cascadeOnDelete();
            $table->string('locale')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unique(['inclusion_id', 'locale']);
        });

        Schema::create('tour_inclusion', function (Blueprint $table) {
            $table->foreignId('tour_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inclusion_id')->constrained()->cascadeOnDelete();
            $table->primary(['tour_id', 'inclusion_id']);
        });

        $rows = DB::table('tour_content_items')
            ->join('tour_content_item_translations as t', fn ($j) => $j->on('t.tour_content_item_id', '=', 'tour_content_items.id')->where('t.locale', 'en'))
            ->whereIn('tour_content_items.type', ['included', 'excluded'])
            ->orderBy('tour_content_items.type')->orderBy('tour_content_items.tour_id')->orderBy('tour_content_items.sort_order')->orderBy('tour_content_items.id')
            ->get(['tour_content_items.id', 'tour_content_items.tour_id', 'tour_content_items.type', 'tour_content_items.icon', 't.title', 't.description']);

        $library = [];
        $order = 0;
        foreach ($rows as $row) {
            $fingerprint = implode("\x1f", [$row->type, $row->icon, trim($row->title), trim((string) $row->description)]);
            if (!isset($library[$fingerprint])) {
                $library[$fingerprint] = DB::table('inclusions')->insertGetId([
                    'type' => $row->type, 'icon' => $row->icon, 'is_active' => true, 'sort_order' => ++$order,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                DB::table('inclusion_translations')->insert([
                    'inclusion_id' => $library[$fingerprint], 'locale' => 'en',
                    'title' => trim($row->title), 'description' => trim((string) $row->description) ?: null,
                ]);
            }
            DB::table('tour_inclusion')->insertOrIgnore(['tour_id' => $row->tour_id, 'inclusion_id' => $library[$fingerprint]]);
        }

        $moved = $rows->pluck('id');
        DB::table('tour_content_item_translations')->whereIn('tour_content_item_id', $moved)->delete();
        DB::table('tour_content_items')->whereIn('id', $moved)->delete();

        foreach (['list', 'create', 'edit', 'delete'] as $verb) {
            $id = DB::table('permissions')->insertGetId(['name' => "$verb-inclusions", 'guard_name' => 'web', 'group_name' => 'Tours & CRM', 'created_at' => now(), 'updated_at' => now()]);
            $roles = DB::table('role_has_permissions')->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                ->where('permissions.name', "$verb-tour-content")->pluck('role_has_permissions.role_id');
            foreach ($roles as $roleId) {
                DB::table('role_has_permissions')->insert(['permission_id' => $id, 'role_id' => $roleId]);
            }
        }
        app()['cache']->forget('spatie.permission.cache');
    }

    public function down(): void
    {
        // Put the shared items back on each tour as its own lines.
        $links = DB::table('tour_inclusion')
            ->join('inclusions', 'inclusions.id', '=', 'tour_inclusion.inclusion_id')
            ->join('inclusion_translations as t', fn ($j) => $j->on('t.inclusion_id', '=', 'inclusions.id')->where('t.locale', 'en'))
            ->orderBy('inclusions.sort_order')
            ->get(['tour_inclusion.tour_id', 'inclusions.type', 'inclusions.icon', 'inclusions.sort_order', 't.title', 't.description']);
        foreach ($links as $link) {
            $id = DB::table('tour_content_items')->insertGetId(['tour_id' => $link->tour_id, 'type' => $link->type, 'icon' => $link->icon, 'sort_order' => $link->sort_order, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('tour_content_item_translations')->insert(['tour_content_item_id' => $id, 'locale' => 'en', 'title' => $link->title, 'description' => $link->description]);
        }
        DB::table('permissions')->whereIn('name', ['list-inclusions', 'create-inclusions', 'edit-inclusions', 'delete-inclusions'])->delete();
        app()['cache']->forget('spatie.permission.cache');
        Schema::dropIfExists('tour_inclusion');
        Schema::dropIfExists('inclusion_translations');
        Schema::dropIfExists('inclusions');
    }
};
