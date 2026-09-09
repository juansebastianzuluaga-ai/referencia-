<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes_referencia', function (Blueprint $table) {
            // Hora y lugar en que se espera la llegada del paciente, capturados
            // al aceptar la solicitud (antes solo se guardaba hora_respuesta,
            // que es cuándo el personal respondió, no cuándo llega el paciente).
            $table->time('hora_llegada')->nullable()->after('hora_respuesta');
            $table->string('lugar_llegada', 150)->nullable()->after('hora_llegada');
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes_referencia', function (Blueprint $table) {
            $table->dropColumn(['hora_llegada', 'lugar_llegada']);
        });
    }
};
