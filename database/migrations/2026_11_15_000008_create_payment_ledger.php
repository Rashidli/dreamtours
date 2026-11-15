<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Payment ledger (CRM requirements §7). Payments and refunds are separate rows
 * (pending → confirmed / rejected; confirmed rows are voided, never deleted);
 * discounts and complimentary seats are price adjustments, not money received.
 * A booking's paid / outstanding columns are computed from these rows by
 * App\Support\BookingMoney and never typed by hand. Existing bookings are brought
 * in by `php artisan crm:sync-payments`, not here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_payments', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 20)->nullable()->unique(); // R-2026-00001 / RF-2026-00001, given when confirmed
            $table->foreignId('booking_id')->constrained();
            $table->string('kind', 10)->default('payment');            // payment | refund
            $table->foreignId('refund_of_id')->nullable()->constrained('booking_payments');
            $table->decimal('amount', 10, 2);                            // always positive; kind says the direction
            $table->string('currency', 3)->default('CAD');
            $table->string('method', 20);                                // etransfer | cash | bank_transfer | bank_draft | cheque | other
            $table->string('reference', 120)->nullable();
            $table->date('paid_on');
            $table->string('status', 12)->default('pending');           // pending | confirmed | rejected | voided
            $table->string('proof_path')->nullable();
            $table->text('note')->nullable();
            $table->string('reason')->nullable();                        // refund reason, or why it was rejected / voided
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->index(['booking_id', 'status']);
            $table->index(['status', 'paid_on']);
        });

        Schema::create('booking_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained();
            $table->string('kind', 15);                                  // discount | complimentary
            $table->decimal('amount', 10, 2);
            $table->string('reason');
            $table->foreignId('booking_passenger_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 10)->default('active');            // active | voided
            $table->string('void_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();
            $table->index(['booking_id', 'status']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            // amount_paid (existing) becomes "net collected" = confirmed payments − confirmed refunds.
            $table->decimal('discount_amount', 10, 2)->default(0)->after('total_amount');
            $table->decimal('refunded_amount', 10, 2)->default(0)->after('amount_paid');
            $table->decimal('pending_amount', 10, 2)->default(0)->after('refunded_amount');
            $table->decimal('outstanding_amount', 10, 2)->default(0)->after('pending_amount')->index();
            $table->date('balance_due_date')->nullable()->after('outstanding_amount')->index();
            $table->string('payment_terms_note')->nullable()->after('balance_due_date');
        });

        Schema::table('tours', fn (Blueprint $table) => $table->unsignedSmallInteger('balance_due_days')->nullable()->after('deposit_per_person'));

        $now = now();
        // 100% keeps today's behaviour (tours without a per-person deposit ask for the full price); §7 suggests 50.
        DB::table('settings')->insertOrIgnore([
            ['key' => 'default_deposit_percent', 'value' => '100', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'balance_due_days', 'value' => '7', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $permissions = ['list-payments', 'record-payments', 'confirm-payments', 'refund-payments', 'apply-discounts'];
        foreach ($permissions as $name) {
            DB::table('permissions')->insertOrIgnore(['name' => $name, 'guard_name' => 'web', 'group_name' => 'Tours & CRM', 'created_at' => $now, 'updated_at' => $now]);
        }
        if ($admin = DB::table('roles')->where(['name' => 'admin', 'guard_name' => 'web'])->value('id')) {
            foreach (DB::table('permissions')->whereIn('name', $permissions)->pluck('id') as $permissionId) {
                DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $admin]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('tours', fn (Blueprint $table) => $table->dropColumn('balance_due_days'));
        Schema::table('bookings', fn (Blueprint $table) => $table->dropColumn(['discount_amount', 'refunded_amount', 'pending_amount', 'outstanding_amount', 'balance_due_date', 'payment_terms_note']));
        Schema::dropIfExists('booking_adjustments');
        Schema::dropIfExists('booking_payments');
        DB::table('settings')->whereIn('key', ['default_deposit_percent', 'balance_due_days'])->delete();
        DB::table('permissions')->whereIn('name', ['list-payments', 'record-payments', 'confirm-payments', 'refund-payments', 'apply-discounts'])->delete();
    }
};
