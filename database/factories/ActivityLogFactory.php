<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'event' => fake()->slug(2).'.'.fake()->randomElement(['viewed', 'created', 'exported']),
            'module' => fake()->word(),
            'description' => fake()->sentence(),
            'properties' => ['context' => fake()->word()],
            'url' => fake()->url(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'tags' => implode(',', fake()->words(2)),
        ];
    }
}
