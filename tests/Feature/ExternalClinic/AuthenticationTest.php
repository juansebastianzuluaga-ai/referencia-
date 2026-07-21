<?php

namespace Tests\Feature\ExternalClinic;

use App\Models\ExternalClinic;
use App\Models\IdentificationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_clinic_can_login(): void
    {
        $identificationType = IdentificationType::factory()->create();
        $clinic = ExternalClinic::factory()->active()->create([
            'nit' => '901234567-8',
            'password' => Hash::make('Password123!'),
            'legal_rep_id_type_id' => $identificationType->id,
        ]);

        $response = $this->postJson('/api/external-clinics/login', [
            'nit' => '901234567-8',
            'password' => 'Password123!',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.nit', '901234567-8')
            ->assertJsonPath('data.status', 'active');

        $this->assertAuthenticatedAs($clinic, 'external_clinic');
    }

    public function test_pending_clinic_cannot_login(): void
    {
        $identificationType = IdentificationType::factory()->create();
        ExternalClinic::factory()->pending()->create([
            'nit' => '901234567-8',
            'password' => Hash::make('Password123!'),
            'legal_rep_id_type_id' => $identificationType->id,
        ]);

        $this->postJson('/api/external-clinics/login', [
            'nit' => '901234567-8',
            'password' => 'Password123!',
        ])->assertUnauthorized();

        $this->assertGuest('external_clinic');
    }

    public function test_rejected_clinic_cannot_login(): void
    {
        $identificationType = IdentificationType::factory()->create();
        ExternalClinic::factory()->rejected()->create([
            'nit' => '901234567-8',
            'password' => Hash::make('Password123!'),
            'legal_rep_id_type_id' => $identificationType->id,
        ]);

        $this->postJson('/api/external-clinics/login', [
            'nit' => '901234567-8',
            'password' => 'Password123!',
        ])->assertUnauthorized();
    }

    public function test_invalid_credentials_do_not_authenticate(): void
    {
        $identificationType = IdentificationType::factory()->create();
        ExternalClinic::factory()->active()->create([
            'nit' => '901234567-8',
            'password' => Hash::make('Password123!'),
            'legal_rep_id_type_id' => $identificationType->id,
        ]);

        $this->postJson('/api/external-clinics/login', [
            'nit' => '901234567-8',
            'password' => 'wrong-password',
        ])->assertUnauthorized();

        $this->assertGuest('external_clinic');
    }

    public function test_clinic_can_logout(): void
    {
        $identificationType = IdentificationType::factory()->create();
        $clinic = ExternalClinic::factory()->active()->create([
            'legal_rep_id_type_id' => $identificationType->id,
        ]);

        $this->actingAs($clinic, 'external_clinic')
            ->postJson('/api/external-clinics/logout')
            ->assertOk();

        $this->assertGuest('external_clinic');
    }
}
