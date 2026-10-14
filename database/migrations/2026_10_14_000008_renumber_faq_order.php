<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Several FAQs shared order 0, so "Order" could not move one ahead; number each group 1, 2, 3 … in its current order. */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('faqs')->orderBy('group')->orderBy('order')->orderBy('id')->get()->groupBy('group') as $faqs) {
            foreach ($faqs->values() as $i => $faq) {
                DB::table('faqs')->where('id', $faq->id)->update(['order' => $i + 1]);
            }
        }
    }

    public function down(): void
    {
    }
};
