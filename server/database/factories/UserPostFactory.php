<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserPostFactory extends Factory{// only create posts if you have some users in db

    public function definition(): array{
        return [
            'user_id' => User::factory(),
            'title' => fake()->title(),
            'description' => fake()->sentence(),
            'photo_url' => fake()->imageUrl(),
            'likes' => fake()->numberBetween(30 , 200),
            'imports' => fake()->numberBetween(2 , 10),
            'json_content' => [
                'blocks' => [
                    [
                        'type' => 'header',
                        'data' => [
                            'text' => fake()->sentence(),
                            'level' => 2,
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'data' => [
                            'text' => fake()->paragraph(),
                        ],
                    ],
                ],
            ],
        ];
    }
}
