<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Visa support pricing: a country can be typed even when it is not a tour destination,
 * and government / third-party fees are kept apart from DreamTour's own service fee.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visa_services', function (Blueprint $table) {
            $table->string('country')->nullable()->after('destination_id');
            $table->decimal('third_party_fee', 10, 2)->nullable()->after('currency');
            $table->string('third_party_fee_note')->nullable()->after('third_party_fee');
        });
    }

    public function down(): void
    {
        Schema::table('visa_services', fn (Blueprint $table) => $table->dropColumn(['country', 'third_party_fee', 'third_party_fee_note']));
    }
};
