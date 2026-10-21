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
            $table->string('institucion_remitente', 150)->nullable()->after('especialidad_requerida');
            $table->string('municipio_remitente', 120)->nullable()->after('institucion_remitente');
            $table->boolean('requiere_aislamiento')->nullable()->after('municipio_remitente');
            $table->string('tipo_aislamiento', 30)->nullable()->after('requiere_aislamiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitudes_referencia', function (Blueprint $table) {
            $table->dropColumn(['institucion_remitente', 'municipio_remitente', 'requiere_aislamiento', 'tipo_aislamiento']);
        });
    }
};
