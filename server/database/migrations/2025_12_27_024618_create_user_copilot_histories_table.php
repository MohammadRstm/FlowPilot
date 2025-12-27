<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration{
    public function up(): void{
        Schema::create('user_copilot_histories', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->json('response');
            $table->string('question');
            $table->text('ai_description');
            $table->string('ai_model');
            $table->timestamps();
        });
    }

    public function down(): void{
        Schema::dropIfExists('user_copilot_histories');
    }
};
