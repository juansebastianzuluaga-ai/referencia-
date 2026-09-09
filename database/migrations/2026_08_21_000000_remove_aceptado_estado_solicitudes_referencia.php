<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * "Aceptar" y "Marcar en espera" se combinaron en un solo paso hace
     * tiempo — desde entonces ninguna solicitud nueva queda en estado
     * "aceptado". Las que ya estaban ahí desde antes de ese cambio se
     * pasan a "en espera" (el paso que les faltaba) antes de quitar el
     * estado del todo.
     */
    public function up(): void
    {
        DB::table('solicitudes_referencia')
            ->where('estado', 'aceptado')
            ->update(['estado' => 'en_espera']);

        DB::statement("ALTER TABLE solicitudes_referencia MODIFY COLUMN estado ENUM('pendiente','en_espera','completado','negado') NOT NULL DEFAULT 'pendiente'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE solicitudes_referencia MODIFY COLUMN estado ENUM('pendiente','aceptado','en_espera','completado','negado') NOT NULL DEFAULT 'pendiente'");
    }
};
