<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration{public function up():void{Schema::table('words',fn(Blueprint $t)=>$t->text('source')->nullable()->after('key'));}public function down():void{Schema::table('words',fn(Blueprint $t)=>$t->dropColumn('source'));}};
