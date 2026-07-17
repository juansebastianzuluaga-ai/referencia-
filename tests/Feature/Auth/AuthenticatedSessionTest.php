<?php

namespace Tests\Feature\Auth;

use App\Models\IdentificationType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticatedSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_login_with_username(): void
    {
        $identificationType = IdentificationType::factory()->create();
        $user = User::factory()->create([
            'identification_type_id' => $identificationType->id,
            'user_name' => 'doctor.demo',
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'doctor.demo',
            'password' => 'Password123!',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.user_name', 'doctor.demo');

        $this->assertAuthenticatedAs($user);
    }

    public function test_users_cannot_login_with_invalid_password(): void
    {
        $identificationType = IdentificationType::factory()->create();
        User::factory()->create([
            'identification_type_id' => $identificationType->id,
            'user_name' => 'doctor.demo',
            'password' => Hash::make('Password123!'),
        ]);

        $this->postJson('/api/login', [
            'username' => 'doctor.demo',
            'password' => 'wrong-password',
        ])->assertUnprocessable();

        $this->assertGuest();
    }

    public function test_authenticated_users_can_fetch_their_profile(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.must_change_password', false)
            ->assertJsonPath('data.must_update_profile', false);
    }
}
