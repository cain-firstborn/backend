<?php

namespace Database\Factories;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Admin>
 */
class AdminFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'     => $this->faker->name(),
            'email'    => Str::replaceMatches('/\d+/', '', $this->faker->unique()->safeEmail()),
            'password' => $this->faker->password(),
        ];
    }

    /**
     * Indicate that the model's should be of type support.
     */
    public function support(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'name'  => 'Support',
                'email' => config('mail.from.address'),
            ];
        });
    }
}
