<?php

namespace Database\Factories;

use App\Models\SignUp;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SignUp>
 */
class SignUpFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'    => User::factory(),
            'created_at' => now()->subDays(rand(1, 30)),
        ];
    }
}
