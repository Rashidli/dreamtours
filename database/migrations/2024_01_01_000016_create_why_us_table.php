<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('why_us', function (Blueprint $table) {
            $table->id();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('why_us_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('why_us_id')->constrained('why_us')->onDelete('cascade');
            $table->string('locale')->index();
            $table->string('title');
            $table->text('description')->nullable();

            $table->unique(['why_us_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('why_us_translations');
        Schema::dropIfExists('why_us');
    }
};
