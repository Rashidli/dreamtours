<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Customer import (§4, §25: mapping, preview, duplicate check, error report before
 * committing) and reviewed merging of duplicates (§3, §4). A merged customer is
 * archived with merged_into_id, so their history stays reachable; pairs staff mark
 * "not a duplicate" stop being suggested.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('filename');
            $table->string('path');                         // private storage, removed after import
            $table->json('columns')->nullable();            // header row
            $table->json('mapping')->nullable();            // column index => customer field
            $table->json('options')->nullable();            // duplicates mode, status, tag
            $table->json('summary')->nullable();            // created / updated / skipped / failed
            $table->string('status', 20)->default('uploaded'); // uploaded | imported
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });

        Schema::table('customers', fn (Blueprint $table) => $table->foreignId('merged_into_id')->nullable()->after('assigned_user_id')->constrained('customers')->nullOnDelete());

        Schema::create('customer_duplicate_dismissals', function (Blueprint $table) {
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();       // lower id
            $table->foreignId('other_customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->primary(['customer_id', 'other_customer_id']);
        });

        $now = now();
        foreach (['import-customers', 'merge-customers'] as $name) {
            DB::table('permissions')->insertOrIgnore(['name' => $name, 'guard_name' => 'web', 'group_name' => 'Tours & CRM', 'created_at' => $now, 'updated_at' => $now]);
        }
        if ($admin = DB::table('roles')->where(['name' => 'admin', 'guard_name' => 'web'])->value('id')) {
            foreach (DB::table('permissions')->whereIn('name', ['import-customers', 'merge-customers'])->pluck('id') as $permissionId) {
                DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $admin]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_duplicate_dismissals');
        Schema::table('customers', fn (Blueprint $table) => $table->dropConstrainedForeignId('merged_into_id'));
        Schema::dropIfExists('customer_imports');
        DB::table('permissions')->whereIn('name', ['import-customers', 'merge-customers'])->delete();
    }
};
