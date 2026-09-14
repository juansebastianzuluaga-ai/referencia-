<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('admin');

        return $user;
    }

    private function superAdminUser(): User
    {
        return User::query()->where('user_name', 'superadmin')->firstOrFail();
    }

    public function test_admin_cannot_access_settings(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->actingAs($this->adminUser())
            ->getJson('/api/settings')
            ->assertForbidden();
    }

    public function test_admin_cannot_list_roles_or_permissions(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = $this->adminUser();

        $this->actingAs($admin)->postJson('/api/roles/get-all')->assertForbidden();
        $this->actingAs($admin)->postJson('/api/permissions/get-all')->assertForbidden();
    }

    public function test_admin_still_manages_users_and_catalogs(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = $this->adminUser();

        $this->actingAs($admin)->postJson('/api/users/get-all')->assertOk();
        $this->actingAs($admin)->postJson('/api/identification-types/get-all')->assertOk();
    }

    public function test_super_admin_keeps_full_access(): void
    {
        $this->seed(DatabaseSeeder::class);
        $superAdmin = $this->superAdminUser();

        $this->actingAs($superAdmin)->getJson('/api/settings')->assertOk();
        $this->actingAs($superAdmin)->postJson('/api/roles/get-all')->assertOk();
    }
}
