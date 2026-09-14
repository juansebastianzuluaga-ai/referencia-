<?php

namespace Tests\Feature;

use App\Models\Clinica;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ClinicaRegistroTest extends TestCase
{
    use RefreshDatabase;

    private function payload(string $nit): array
    {
        return [
            'nit' => $nit,
            'razon_social' => 'Clínica Prueba',
            'nombre' => 'Clínica Prueba',
            'email' => 'contacto@clinicaprueba.com',
            'email_confirmacion' => 'contacto@clinicaprueba.com',
            'telefono' => '3001234567',
            'ciudad' => 'Cali',
            'departamento' => 'Valle del Cauca',
            'direccion' => 'Calle 1 #2-3',
            'representante_legal' => 'Juan Pérez',
            'cedula_representante' => '12345678',
        ];
    }

    public function test_registro_acepta_nit_con_puntos_y_guion(): void
    {
        $this->seed(DatabaseSeeder::class);
        Mail::fake();

        $this->postJson('/api/externo/registro', $this->payload('900.123.456-7'))
            ->assertCreated();

        $this->assertDatabaseHas('clinicas', ['nit' => '9001234567']);
    }

    public function test_registro_rechaza_nit_duplicado_en_cualquier_formato(): void
    {
        $this->seed(DatabaseSeeder::class);
        Mail::fake();
        Clinica::create($this->payload('900.123.456-7'));

        $this->postJson('/api/externo/registro', $this->payload('900123456-7'))
            ->assertUnprocessable();
    }

    public function test_registro_rechaza_nit_con_longitud_invalida(): void
    {
        $this->seed(DatabaseSeeder::class);
        Mail::fake();

        $this->postJson('/api/externo/registro', $this->payload('1234'))
            ->assertUnprocessable();
    }
}
