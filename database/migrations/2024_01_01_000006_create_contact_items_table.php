<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_items', function (Blueprint $table) {
            $table->id();
            $table->string('icon')->nullable();
            $table->string('link')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contact_item_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_item_id')->constrained()->onDelete('cascade');
            $table->string('locale')->index();
            $table->string('title');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['contact_item_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_item_translations');
        Schema::dropIfExists('contact_items');
    }
};
