<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserCopilotHistoryFactory extends Factory{
    public function definition(): array
    {
        return [
            'question' => fake()->sentence(),
            'ai_model' => 'gpt-4-mini',
            'ai_description' => fake()->paragraph(),
            'user_id' => User::factory()->create()->id,
            'resposne' => fake()->paragraph()// for now
        ];
    }
}
