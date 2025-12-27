<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UserRoleFactory extends Factory{

    public function definition(): array
    {
        return [
            'name' => fake()->sentence()
        ];
    }
}
