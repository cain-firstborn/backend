<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
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
            'name'       => $this->faker->name(),
            'message'    => $this->faker->realTextBetween(50, 255),
            'created_at' => now()->subDays(rand(1, 30)),
        ];
    }
}
