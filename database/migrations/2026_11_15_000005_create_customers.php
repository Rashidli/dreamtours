<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Permanent customer directory (CRM requirements §4). One person can have many
 * bookings and requests; bookings and visa enquiries point at their customer.
 * A customer has one lifecycle status and any number of tags; statuses and tags
 * are archived rather than deleted so history keeps its labels. Existing
 * bookings are linked by `php artisan crm:link-customers`, not here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color', 20)->default('secondary');
            $table->boolean('blocks_marketing')->default(false); // "Do Not Contact"
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('color', 20)->default('info');
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 100);
            $table->string('last_name', 100)->nullable();
            $table->string('preferred_name', 100)->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone', 50)->nullable();
            $table->string('phone_digits', 20)->nullable()->index(); // last 10 digits, for matching
            $table->string('whatsapp', 50)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('language', 30)->nullable();
            $table->foreignId('customer_status_id')->nullable()->constrained('customer_statuses')->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 50)->nullable();          // website | manual | import | referral …
            $table->boolean('marketing_opt_in')->default(false);
            $table->timestamp('marketing_opt_in_at')->nullable();
            $table->string('marketing_opt_in_source')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_customer_tag', function (Blueprint $table) {
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['customer_id', 'customer_tag_id']);
        });

        Schema::table('bookings', fn (Blueprint $table) => $table->foreignId('customer_id')->nullable()->after('id')->constrained()->nullOnDelete());
        Schema::table('visa_enquiries', fn (Blueprint $table) => $table->foreignId('customer_id')->nullable()->after('id')->constrained()->nullOnDelete());

        $now = now();
        foreach ([['New', 'primary', false], ['Loyal / VIP', 'warning', false], ['Repeat Guest', 'success', false], ['Needs Follow-up', 'info', false], ['Service Issue', 'danger', false], ['Inactive', 'secondary', false], ['Do Not Contact', 'dark', true]] as $i => [$name, $color, $blocks]) {
            DB::table('customer_statuses')->insert(['name' => $name, 'color' => $color, 'blocks_marketing' => $blocks, 'sort_order' => $i + 1, 'created_at' => $now, 'updated_at' => $now]);
        }

        $permissions = ['list-customers', 'create-customers', 'edit-customers', 'delete-customers', 'export-customers', 'view-customer-birthdays', 'manage-customer-labels'];
        foreach ($permissions as $name) {
            DB::table('permissions')->insertOrIgnore(['name' => $name, 'guard_name' => 'web', 'group_name' => 'Tours & CRM', 'created_at' => $now, 'updated_at' => $now]);
        }
        // Administrators manage customers (§16); Super Admin has everything already.
        if ($admin = DB::table('roles')->where(['name' => 'admin', 'guard_name' => 'web'])->value('id')) {
            foreach (DB::table('permissions')->whereIn('name', $permissions)->pluck('id') as $permissionId) {
                DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $admin]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('visa_enquiries', fn (Blueprint $table) => $table->dropConstrainedForeignId('customer_id'));
        Schema::table('bookings', fn (Blueprint $table) => $table->dropConstrainedForeignId('customer_id'));
        Schema::dropIfExists('customer_customer_tag');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('customer_tags');
        Schema::dropIfExists('customer_statuses');
        DB::table('permissions')->whereIn('name', ['list-customers', 'create-customers', 'edit-customers', 'delete-customers', 'export-customers', 'view-customer-birthdays', 'manage-customer-labels'])->delete();
    }
};
