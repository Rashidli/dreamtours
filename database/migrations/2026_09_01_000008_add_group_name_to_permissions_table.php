<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration{public function up():void{Schema::table('permissions',fn(Blueprint $table)=>$table->string('group_name')->nullable()->index()->after('guard_name'));}public function down():void{Schema::table('permissions',fn(Blueprint $table)=>$table->dropColumn('group_name'));}};
