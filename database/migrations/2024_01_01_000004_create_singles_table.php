<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('singles', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('single_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('single_id')->constrained()->onDelete('cascade');
            $table->string('locale')->index();
            $table->string('title');
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->text('seo_keywords')->nullable();
            $table->string('slug');

            $table->unique(['single_id', 'locale']);
            $table->unique(['slug', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('single_translations');
        Schema::dropIfExists('singles');
    }
};
