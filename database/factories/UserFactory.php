<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username' => 'johndoe',
            'password' => Hash::make('Secret123!'),
            'email' => 'johndoe@gmail.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ];
    }

    public function withToken(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'token' => Str::ulid()
        ]);
    }

    public function unhashedPassword(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'password' => 'Secret123!'
        ]);
    }

    public function random(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'username' => fake()->userName(),
            'password' => Hash::make('Secret123!'),
            'email' => fake()->email(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
        ]);
    }
}
