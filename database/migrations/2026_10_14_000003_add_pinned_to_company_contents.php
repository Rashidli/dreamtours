<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Team members flagged "pinned" (the founder / CEO) always lead the team list, whatever their order. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_contents', function (Blueprint $table) {
            $table->boolean('pinned')->default(false)->after('is_active');
        });

        // The founder is listed as a team member; keep her first as DreamTour asked.
        $founder = DB::table('company_contents')->join('company_content_translations as t', 't.company_content_id', '=', 'company_contents.id')
            ->where('company_contents.type', 'team')->where('t.title', 'like', 'Jen Banez%')->value('company_contents.id');
        if ($founder) {
            DB::table('company_contents')->where('id', $founder)->update(['pinned' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('company_contents', fn (Blueprint $table) => $table->dropColumn('pinned'));
    }
};
