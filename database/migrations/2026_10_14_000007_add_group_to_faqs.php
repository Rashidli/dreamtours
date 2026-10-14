<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** FAQs split into Trip FAQs and Visa Support FAQs, each shown only in its own area. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->string('group')->default('trip')->after('id')->index();
        });
        // Every FAQ entered so far answers a visa support question.
        DB::table('faqs')->update(['group' => 'visa']);
    }

    public function down(): void
    {
        Schema::table('faqs', fn (Blueprint $table) => $table->dropColumn('group'));
    }
};
