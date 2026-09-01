<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('navigation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('navigation_items')->nullOnDelete();
            $table->string('location')->default('header')->index();
            $table->string('url');
            $table->string('target')->default('_self');
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('navigation_item_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('navigation_item_id')->constrained()->cascadeOnDelete();
            $table->string('locale')->index();
            $table->string('title');
            $table->unique(['navigation_item_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('navigation_item_translations');
        Schema::dropIfExists('navigation_items');
    }
};
