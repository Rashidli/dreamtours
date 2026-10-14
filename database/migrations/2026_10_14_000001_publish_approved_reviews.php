<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Approving a review used to change only its status, while the website also required
 * is_active ("Featured"), so approved customer reviews never appeared. is_active now
 * means "published"; reviews that were approved under the old flow are published.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('reviews')->where('status', 'approved')->where('is_active', false)->whereNull('deleted_at')->update(['is_active' => true]);
    }

    public function down(): void
    {
        // Not reversible: which reviews were deliberately unfeatured is not recorded.
    }
};
