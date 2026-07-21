<?php

namespace Tests\Feature\ExternalClinic;

use App\Models\ExternalClinic;
use App\Models\IdentificationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_clinic_can_register_with_valid_data(): void
    {
        $identificationType = IdentificationType::factory()->create();

        $response = $this->postJson('/api/external-clinics/register', [
            'nit' => '901234567-8',
            'business_name' => 'Clínica Demo S.A.S.',
            'trade_name' => 'Clínica Demo',
            'email' => 'demo@clinica.com',
            'phone' => '(601) 123 4567',
            'mobile' => '300 123 4567',
            'address' => 'Calle 123 # 45-67',
            'city' => 'Bogotá',
            'department' => 'Bogotá D.C.',
            'legal_rep_name' => 'Juan Pérez',
            'legal_rep_id_type_id' => $identificationType->id,
            'legal_rep_id_number' => '1234567890',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms_accepted' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.nit', '901234567-8')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('external_clinics', [
            'nit' => '901234567-8',
            'email' => 'demo@clinica.com',
            'status' => 'pending',
        ]);
    }

    public function test_cannot_register_with_duplicate_nit(): void
    {
        $identificationType = IdentificationType::factory()->create();
        ExternalClinic::factory()->create([
            'nit' => '901234567-8',
            'legal_rep_id_type_id' => $identificationType->id,
        ]);

        $this->postJson('/api/external-clinics/register', [
            'nit' => '901234567-8',
            'business_name' => 'Otra Clínica',
            'email' => 'otra@clinica.com',
            'phone' => '(601) 123 4567',
            'address' => 'Calle 123',
            'city' => 'Bogotá',
            'department' => 'Bogotá D.C.',
            'legal_rep_name' => 'Ana López',
            'legal_rep_id_type_id' => $identificationType->id,
            'legal_rep_id_number' => '9876543210',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms_accepted' => true,
        ])->assertUnprocessable();
    }

    public function test_cannot_register_with_duplicate_email(): void
    {
        $identificationType = IdentificationType::factory()->create();
        ExternalClinic::factory()->create([
            'email' => 'demo@clinica.com',
            'legal_rep_id_type_id' => $identificationType->id,
        ]);

        $this->postJson('/api/external-clinics/register', [
            'nit' => '111111111-1',
            'business_name' => 'Otra Clínica',
            'email' => 'demo@clinica.com',
            'phone' => '(601) 123 4567',
            'address' => 'Calle 123',
            'city' => 'Bogotá',
            'department' => 'Bogotá D.C.',
            'legal_rep_name' => 'Ana López',
            'legal_rep_id_type_id' => $identificationType->id,
            'legal_rep_id_number' => '9876543210',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms_accepted' => true,
        ])->assertUnprocessable();
    }

    public function test_password_must_be_confirmed(): void
    {
        $identificationType = IdentificationType::factory()->create();

        $this->postJson('/api/external-clinics/register', [
            'nit' => '111111111-1',
            'business_name' => 'Clínica Demo',
            'email' => 'demo@clinica.com',
            'phone' => '(601) 123 4567',
            'address' => 'Calle 123',
            'city' => 'Bogotá',
            'department' => 'Bogotá D.C.',
            'legal_rep_name' => 'Ana López',
            'legal_rep_id_type_id' => $identificationType->id,
            'legal_rep_id_number' => '9876543210',
            'password' => 'Password123!',
            'password_confirmation' => 'DifferentPassword123!',
            'terms_accepted' => true,
        ])->assertUnprocessable();
    }
}
