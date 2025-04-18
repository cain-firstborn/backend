<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
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
            'email'      => Str::replaceMatches('/\d+/', '', $this->faker->unique()->safeEmail()),
            'created_at' => now()->subDays(rand(1, 30)),
        ];
    }
}
