<?php

namespace Database\Factories;

use App\Models\IdentificationType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IdentificationType>
 */
class IdentificationTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('??#')),
            'name' => fake()->unique()->words(3, true),
            'is_active' => true,
        ];
    }
}
