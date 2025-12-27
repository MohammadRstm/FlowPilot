<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FollowerFactory extends Factory{

    public function definition(): array
    {
        return [
            'follower_id' => User::factory()->create()->id,
            'followed_id' => User::factory()->create()->id,
        ];
    }
}
