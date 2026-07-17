<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['info', 'success', 'warning', 'error'];

        return [
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement($types),
            'title' => $this->faker->sentence(4),
            'message' => $this->faker->paragraph(),
            'link' => $this->faker->optional()->url(),
            'read_at' => $this->faker->optional(0.3) ? now() : null,
        ];
    }
}
