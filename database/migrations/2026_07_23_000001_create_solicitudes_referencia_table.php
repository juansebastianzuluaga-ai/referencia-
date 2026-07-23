<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_referencia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete();

            // Datos del paciente
            $table->date('fecha');
            $table->time('hora');
            $table->string('primer_nombre', 80);
            $table->string('segundo_nombre', 80)->nullable();
            $table->string('primer_apellido', 80);
            $table->string('segundo_apellido', 80)->nullable();
            $table->enum('genero', ['M', 'F']);
            $table->unsignedSmallInteger('edad');
            $table->string('tipo_documento', 5);
            $table->string('numero_documento', 30);
            $table->string('eps', 120);
            $table->string('diagnostico', 400);
            $table->string('municipio_capita', 120);

            // Datos de la remisión
            $table->string('especialidad_requerida', 120);
            $table->string('servicio_ubicacion_actual', 60);
            $table->string('servicio_remision', 60)->nullable();
            $table->text('resumen_historia_clinica');
            $table->string('via_contacto', 20)->nullable(); // EMAIL, TELEFONICA, N/A
            $table->boolean('gestante')->nullable();
            $table->string('condicion_especial', 250)->nullable();
            $table->text('observaciones')->nullable();

            // Estado (gestionado por personal interno)
            $table->enum('estado', ['pendiente', 'aceptado', 'negado'])->default('pendiente');
            $table->string('codigo_aceptacion', 20)->nullable()->unique();
            $table->time('hora_respuesta')->nullable();
            $table->string('motivo_negacion', 250)->nullable();
            $table->string('motivo_no_ingreso', 250)->nullable();
            $table->unsignedInteger('numero_ingreso')->nullable();
            $table->string('nombre_quien_responde', 200)->nullable();
            $table->text('observaciones_respuesta')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_referencia');
    }
};
