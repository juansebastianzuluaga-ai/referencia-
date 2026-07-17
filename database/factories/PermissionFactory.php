<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->slug(2).'.'.fake()->randomElement(['view', 'create', 'update', 'delete']);

        return [
            'name' => $name,
            'guard_name' => 'web',
            'display_name' => str($name)->replace(['.', '-'], ' ')->title()->toString(),
            'description' => fake()->sentence(),
        ];
    }
}
