<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Http\Models\User;
use App\Models\User as ModelsUser;

class UserFactory extends Factory{
 
    protected static ?string $password;
    protected $model = ModelsUser::class;

    public function definition(): array{

        return [
            'user_role_id' => env('USER_ROLE_ID'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => fake()->dateTimeBetween('-1 year' , 'now'),
            'password' => static::$password ??= Hash::make('password'),
            'photo_url' => fake()->imageUrl(),
        ];
    }

    public function unverified(): static{

        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
