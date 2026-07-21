<?php

namespace Database\Factories;

use App\Models\ExternalClinic;
use App\Models\IdentificationType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<ExternalClinic>
 */
class ExternalClinicFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nit' => fake()->unique()->numerify('##########-#'),
            'business_name' => fake()->company().' S.A.S.',
            'trade_name' => fake()->optional()->company(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('(#) ### ####'),
            'mobile' => fake()->optional()->numerify('### ### ####'),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'department' => fake()->randomElement([
                'Antioquia',
                'Atlántico',
                'Bogotá D.C.',
                'Bolívar',
                'Boyacá',
                'Caldas',
                'Caquetá',
                'Cauca',
                'Cesar',
                'Córdoba',
                'Cundinamarca',
                'Huila',
                'La Guajira',
                'Magdalena',
                'Meta',
                'Nariño',
                'Norte de Santander',
                'Quindío',
                'Risaralda',
                'Santander',
                'Sucre',
                'Tolima',
                'Valle del Cauca',
            ]),
            'legal_rep_name' => fake()->name(),
            'legal_rep_id_type_id' => IdentificationType::factory(),
            'legal_rep_id_number' => fake()->numerify('##########'),
            'password' => Hash::make('password'),
            'status' => 'pending',
            'rejection_reason' => null,
            'approved_by' => null,
            'approved_at' => null,
            'rejected_by' => null,
            'rejected_at' => null,
            'must_change_password' => false,
            'email_verified_at' => null,
            'last_login_at' => null,
            'failed_login_attempts' => 0,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => fake()->sentence(),
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'approved_at' => now()->subDays(2),
            'email_verified_at' => now()->subDays(2),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
