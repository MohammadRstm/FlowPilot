<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration{

    public function up(): void{
        Schema::create('user_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->bigInteger('likes');
            $table->bigInteger('imports');
            $table->bigInteger('user_id');
            $table->string('json_content');
            $table->string('description');
            $table->string('photo_url');
            $table->timestamps();
        });
    }

    public function down(): void{
        Schema::dropIfExists('user_posts');
    }
};
