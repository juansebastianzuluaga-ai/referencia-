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
        Schema::table('solicitud_referencia_adjuntos', function (Blueprint $table) {
            $table->foreignId('solicitud_evolucion_id')
                ->nullable()
                ->after('solicitud_referencia_id')
                ->constrained('solicitud_evoluciones')
                ->nullOnDelete()
                ->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitud_referencia_adjuntos', function (Blueprint $table) {
            $table->dropForeign(['solicitud_evolucion_id']);
            $table->dropColumn('solicitud_evolucion_id');
        });
    }
};
