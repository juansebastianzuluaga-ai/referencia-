<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\IdentificationType;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('audit.console', true);
    }

    public function test_auditable_models_record_eloquent_changes(): void
    {
        $identificationType = IdentificationType::factory()->create([
            'code' => 'RC',
            'name' => 'Registro civil',
        ]);

        $identificationType->update(['name' => 'Registro civil actualizado']);

        $this->assertDatabaseHas('audits', [
            'event' => 'updated',
            'auditable_type' => IdentificationType::class,
            'auditable_id' => $identificationType->id,
        ]);
    }

    public function test_audit_logger_records_custom_model_events(): void
    {
        $identificationType = IdentificationType::factory()->create();

        app(AuditLogger::class)->log(
            auditable: $identificationType,
            event: 'identification-type.exported',
            oldValues: [],
            newValues: ['format' => 'xlsx'],
            tags: ['catalogs', 'exports'],
        );

        $this->assertDatabaseHas('audits', [
            'event' => 'identification-type.exported',
            'auditable_type' => IdentificationType::class,
            'auditable_id' => $identificationType->id,
            'tags' => 'catalogs,exports',
        ]);
    }

    public function test_activity_logger_records_events_without_auditable_model(): void
    {
        $user = User::factory()->create();

        $activityLog = app(ActivityLogger::class)->log(
            event: 'report.exported',
            module: 'reports',
            description: 'Exportacion de reporte de usuarios',
            properties: ['format' => 'xlsx'],
            tags: ['reports', 'exports'],
            user: $user,
        );

        $this->assertInstanceOf(ActivityLog::class, $activityLog);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'event' => 'report.exported',
            'module' => 'reports',
            'tags' => 'reports,exports',
        ]);
    }
}
