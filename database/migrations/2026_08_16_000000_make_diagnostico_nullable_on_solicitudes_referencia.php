<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El campo `diagnostico` (singular) quedó obsoleto tras introducir la
     * relación `diagnosticos_solicitud` para soportar múltiples diagnósticos
     * por solicitud, pero seguía siendo NOT NULL sin valor por defecto,
     * bloqueando cualquier envío nuevo con un error 500.
     */
    public function up(): void
    {
        Schema::table('solicitudes_referencia', function (Blueprint $table) {
            $table->string('diagnostico', 400)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes_referencia', function (Blueprint $table) {
            $table->string('diagnostico', 400)->nullable(false)->change();
        });
    }
};
