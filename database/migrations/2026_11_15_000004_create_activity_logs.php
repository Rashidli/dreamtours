<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only activity history (CRM requirements §22): who did what to which
 * record, the old and new values, when, from where and why. Actor and record
 * names are copied in, so entries stay readable after users or records go.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('actor_type', 16);              // user | system | visitor
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name');
            $table->string('actor_role')->nullable();
            $table->string('action', 64);                  // created | updated | deleted | restored | role_changed | email_queued …
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label')->nullable();
            $table->json('changes')->nullable();           // {field: [old, new]} or event details
            $table->text('reason')->nullable();
            $table->string('origin', 16);                  // admin | website | console | queue
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['actor_type', 'actor_id']);
            $table->index('action');
            $table->index('created_at');
        });

        DB::table('permissions')->insertOrIgnore(['name' => 'list-activity-log', 'guard_name' => 'web', 'group_name' => 'Access control', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        DB::table('permissions')->where('name', 'list-activity-log')->delete();
    }
};
