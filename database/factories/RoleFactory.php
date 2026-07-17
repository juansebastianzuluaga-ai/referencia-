<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->slug(2);

        return [
            'name' => $name,
            'guard_name' => 'web',
            'display_name' => str($name)->replace('-', ' ')->title()->toString(),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
