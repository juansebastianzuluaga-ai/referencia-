<?php

namespace Tests\Feature;

use App\Models\IdentificationType;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_a_user_with_roles(): void
    {
        $this->seed(DatabaseSeeder::class);

        $superAdmin = User::query()->where('user_name', 'superadmin')->firstOrFail();
        $identificationType = IdentificationType::query()->where('code', 'CC')->firstOrFail();

        $response = $this
            ->actingAs($superAdmin)
            ->postJson('/api/users', [
                'identification_type_id' => $identificationType->id,
                'identification_number' => '123456789',
                'user_name' => 'medico.demo',
                'first_name' => 'Medico',
                'middle_name' => null,
                'last_name' => 'Demo',
                'sur_name' => null,
                'email' => 'medico.demo@example.com',
                'job_title' => 'Medico general',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'must_change_password' => true,
                'must_update_profile' => true,
                'roles' => ['medico'],
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.user_name', 'medico.demo')
            ->assertJsonPath('data.must_change_password', true)
            ->assertJsonPath('data.must_update_profile', true);

        $this->assertDatabaseHas('users', [
            'user_name' => 'medico.demo',
            'identification_number' => '123456789',
        ]);

        $this->assertTrue(
            User::query()->where('user_name', 'medico.demo')->firstOrFail()->roles()->where('name', 'medico')->exists()
        );
    }

    public function test_role_seed_creates_only_initial_clinic_roles(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertEqualsCanonicalizing(
            ['admin', 'medico', 'super-admin'],
            Role::query()->pluck('name')->all()
        );
    }

    public function test_super_admin_can_filter_paginated_users_for_tables(): void
    {
        $this->seed(DatabaseSeeder::class);

        $superAdmin = User::query()->where('user_name', 'superadmin')->firstOrFail();
        $identificationType = IdentificationType::query()->where('code', 'CC')->firstOrFail();
        $medicoRole = Role::query()->where('name', 'medico')->firstOrFail();
        $adminRole = Role::query()->where('name', 'admin')->firstOrFail();

        $medico = User::factory()->create([
            'identification_type_id' => $identificationType->id,
            'identification_number' => '99887766',
            'user_name' => 'cardio.demo',
            'first_name' => 'Carolina',
            'last_name' => 'Cardio',
            'email' => 'cardio.demo@example.com',
            'job_title' => 'Medico cardiologo',
            'is_active' => true,
        ]);
        $medico->assignRole($medicoRole);

        $admin = User::factory()->create([
            'identification_type_id' => $identificationType->id,
            'identification_number' => '11223344',
            'user_name' => 'admin.demo',
            'first_name' => 'Andres',
            'last_name' => 'Admin',
            'email' => 'admin.demo@example.com',
            'job_title' => 'Administrador',
            'is_active' => true,
        ]);
        $admin->assignRole($adminRole);

        $response = $this
            ->actingAs($superAdmin)
            ->postJson('/api/users/get-all', [
                'general' => 'cardio',
                'is_active' => true,
                'roles' => [$medicoRole->id],
                'per_page' => 10,
                'sort_by' => 'user_name',
                'sort_direction' => 'asc',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.data.0.user_name', 'cardio.demo')
            ->assertJsonCount(1, 'data.data');
    }

    public function test_super_admin_can_use_get_all_endpoints_for_catalog_tables(): void
    {
        $this->seed(DatabaseSeeder::class);

        $superAdmin = User::query()->where('user_name', 'superadmin')->firstOrFail();

        $this
            ->actingAs($superAdmin)
            ->postJson('/api/roles/get-all', [
                'general' => 'medico',
                'per_page' => 10,
                'sort_by' => 'name',
                'sort_direction' => 'asc',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.data.0.name', 'medico');

        $this
            ->actingAs($superAdmin)
            ->postJson('/api/permissions/get-all', [
                'general' => 'users.create',
                'per_page' => 10,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.data.0.name', 'users.create');

        $this
            ->actingAs($superAdmin)
            ->postJson('/api/identification-types/get-all', [
                'general' => 'Pasaporte',
                'per_page' => 10,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.data.0.code', 'PAS');
    }
}
