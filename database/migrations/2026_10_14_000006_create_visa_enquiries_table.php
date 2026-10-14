<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Visa enquiries get their own table and admin list instead of being saved as trip
 * bookings, so they never touch departures or seat counts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visa_enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('full_name');
            $table->string('email');
            $table->string('phone');
            $table->string('country');
            $table->string('passport_country')->nullable();
            $table->json('assistance')->nullable();
            $table->unsignedSmallInteger('applicants')->default(1);
            $table->date('travel_date')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('new')->index();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
        });

        foreach (['list', 'edit', 'delete'] as $verb) {
            $id = DB::table('permissions')->insertGetId(['name' => "$verb-visa-enquiries", 'guard_name' => 'web', 'group_name' => 'Tours & CRM', 'created_at' => now(), 'updated_at' => now()]);
            // Whoever could already handle bookings can handle visa enquiries.
            $roles = DB::table('role_has_permissions')->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                ->where('permissions.name', "$verb-bookings")->pluck('role_has_permissions.role_id');
            foreach ($roles as $roleId) {
                DB::table('role_has_permissions')->insert(['permission_id' => $id, 'role_id' => $roleId]);
            }
        }

        // Move visa requests that were saved as bookings.
        foreach (DB::table('bookings')->where('type', 'visa')->get() as $b) {
            preg_match('/Destination:\s*(.+)/', (string) $b->comments, $dest);
            preg_match('/Nationality:\s*(.+)/', (string) $b->comments, $nat);
            DB::table('visa_enquiries')->insert([
                'reference' => 'VE-'.strtoupper(Str::random(8)),
                'full_name' => trim($b->first_name.' '.($b->last_name === '-' ? '' : $b->last_name)),
                'email' => $b->email, 'phone' => $b->phone,
                'country' => trim($dest[1] ?? '') ?: 'Not given', 'passport_country' => trim($nat[1] ?? '') ?: null,
                'applicants' => max(1, (int) $b->participant_count), 'message' => $b->comments,
                'status' => 'new', 'created_at' => $b->created_at, 'updated_at' => $b->updated_at,
            ]);
            DB::table('bookings')->where('id', $b->id)->delete();
        }
        app()['cache']->forget('spatie.permission.cache');
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('name', ['list-visa-enquiries', 'edit-visa-enquiries', 'delete-visa-enquiries'])->delete();
        Schema::dropIfExists('visa_enquiries');
    }
};
