<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration{

    public function up(): void{
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('history_id');
            $table->bigInteger("ai_model");
            $table->text('user_message');
            $table->json('ai_response');

            $table->timestamps();
        });
    }

    public function down(): void{
        Schema::dropIfExists('messages');
    }
};
