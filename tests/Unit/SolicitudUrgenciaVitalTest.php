<?php

namespace Tests\Unit;

use App\Models\SolicitudReferencia;
use Tests\TestCase;

class SolicitudUrgenciaVitalTest extends TestCase
{
    public function test_urgency_defaults_to_not_vital(): void
    {
        $solicitud = new SolicitudReferencia;

        $this->assertFalse($solicitud->urgencia_vital);
    }

    public function test_urgency_value_is_cast_to_boolean(): void
    {
        $solicitud = new SolicitudReferencia;
        $solicitud->fill(['urgencia_vital' => '1']);

        $this->assertTrue($solicitud->urgencia_vital);
    }
}
