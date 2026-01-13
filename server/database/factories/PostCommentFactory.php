<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserPost;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostCommentFactory extends Factory{// can create comments even if users and posts don't exist(creates fake users and fake posts)

    public function definition(): array{
        return [
            'content' => fake()->sentence(),
        ];
    }
}
