<?php

namespace Tests\Feature\ExternalClinic;

use App\Models\ExternalClinic;
use App\Models\IdentificationType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function createAdminWithPermission(string $permission): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permission);

        return $user;
    }

    public function test_admin_can_approve_pending_clinic(): void
    {
        $identificationType = IdentificationType::factory()->create();
        $clinic = ExternalClinic::factory()->pending()->create([
            'legal_rep_id_type_id' => $identificationType->id,
        ]);
        $admin = $this->createAdminWithPermission('external-clinics.approve');

        $this->actingAs($admin)
            ->postJson("/api/admin/external-clinics/{$clinic->id}/approve", [
                'change_reason' => 'Documentación validada',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('external_clinic_requests', [
            'external_clinic_id' => $clinic->id,
            'new_status' => 'active',
        ]);
    }

    public function test_admin_can_reject_pending_clinic(): void
    {
        $identificationType = IdentificationType::factory()->create();
        $clinic = ExternalClinic::factory()->pending()->create([
            'legal_rep_id_type_id' => $identificationType->id,
        ]);
        $admin = $this->createAdminWithPermission('external-clinics.reject');

        $this->actingAs($admin)
            ->postJson("/api/admin/external-clinics/{$clinic->id}/reject", [
                'rejection_reason' => 'Documentación incompleta. Falta RUT actualizado.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        $this->assertDatabaseHas('external_clinics', [
            'id' => $clinic->id,
            'status' => 'rejected',
        ]);
    }

    public function test_non_admin_cannot_approve_clinic(): void
    {
        $identificationType = IdentificationType::factory()->create();
        $clinic = ExternalClinic::factory()->pending()->create([
            'legal_rep_id_type_id' => $identificationType->id,
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/admin/external-clinics/{$clinic->id}/approve")
            ->assertForbidden();
    }

    public function test_cannot_approve_already_approved_clinic(): void
    {
        $identificationType = IdentificationType::factory()->create();
        $clinic = ExternalClinic::factory()->active()->create([
            'legal_rep_id_type_id' => $identificationType->id,
        ]);
        $admin = $this->createAdminWithPermission('external-clinics.approve');

        $this->actingAs($admin)
            ->postJson("/api/admin/external-clinics/{$clinic->id}/approve")
            ->assertUnprocessable();
    }

    public function test_admin_can_toggle_active_status(): void
    {
        $identificationType = IdentificationType::factory()->create();
        $clinic = ExternalClinic::factory()->active()->create([
            'legal_rep_id_type_id' => $identificationType->id,
        ]);
        $admin = $this->createAdminWithPermission('external-clinics.manage');

        $this->actingAs($admin)
            ->patchJson("/api/admin/external-clinics/{$clinic->id}/toggle-status")
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');
    }
}
