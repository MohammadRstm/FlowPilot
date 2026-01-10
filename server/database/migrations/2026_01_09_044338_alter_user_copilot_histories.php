<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration{

    public function up(): void{
        Schema::table('user_copilot_histories', function (Blueprint $table) {
            $table->dropColumn(['response', 'question']);
        });
    }

    public function down(): void{
        Schema::table('users', function (Blueprint $table) {
            $table->string('response')->nullable();
            $table->text('question')->nullable();
        });
    }
};
