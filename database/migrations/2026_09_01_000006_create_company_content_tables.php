<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration{public function up():void{
 Schema::create('company_contents',function(Blueprint $t){$t->id();$t->string('type')->index();$t->string('image')->nullable();$t->string('url')->nullable();$t->string('value')->nullable();$t->boolean('is_active')->default(true)->index();$t->unsignedInteger('sort_order')->default(0);$t->timestamps();$t->softDeletes();});
 Schema::create('company_content_translations',function(Blueprint $t){$t->id();$t->foreignId('company_content_id')->constrained()->cascadeOnDelete();$t->string('locale')->index();$t->string('title');$t->string('subtitle')->nullable();$t->text('description')->nullable();$t->unique(['company_content_id','locale'],'company_content_locale_unique');});
 }public function down():void{Schema::dropIfExists('company_content_translations');Schema::dropIfExists('company_contents');}};
