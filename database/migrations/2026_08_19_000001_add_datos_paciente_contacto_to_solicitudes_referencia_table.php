<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('solicitudes_referencia', function (Blueprint $table) {
            // Límites iguales a los del formulario real de Gomedisys
            // (#addressPatient maxlength=25, #telecomPatient maxlength=15,
            // solo numérico) — así lo que se guarda aquí siempre cabe allá.
            $table->string('direccion_paciente', 25)->nullable()->after('municipio_capita');
            $table->string('telefono_paciente', 15)->nullable()->after('direccion_paciente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitudes_referencia', function (Blueprint $table) {
            $table->dropColumn(['direccion_paciente', 'telefono_paciente']);
        });
    }
};
