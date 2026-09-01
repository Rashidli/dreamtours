<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('words', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('word_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('word_id')->constrained()->onDelete('cascade');
            $table->string('locale')->index();
            $table->string('title');
            $table->timestamps();

            $table->unique(['word_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('word_translations');
        Schema::dropIfExists('words');
    }
};
