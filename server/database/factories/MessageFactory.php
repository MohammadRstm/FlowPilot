<?php

namespace Database\Factories;

use App\Models\UserCopilotHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'history_id' => UserCopilotHistory::factory(),
            'user_message' => fake()->sentence(),
            'ai_response' => [
                'blocks' => [
                    [
                        'type' => 'paragraph',
                        'data' => ['text' => fake()->paragraph()],
                    ],
                ],
            ],
            'ai_model' => fake()->randomElement(['gpt-4', 'gpt-4-mini', 'gpt-3.5-turbo']),
        ];
    }
}
