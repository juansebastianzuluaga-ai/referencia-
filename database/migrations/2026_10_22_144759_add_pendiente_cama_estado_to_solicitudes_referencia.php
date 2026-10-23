<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE solicitudes_referencia MODIFY COLUMN estado ENUM('pendiente','pendiente_cama','en_espera','completado','negado') NOT NULL DEFAULT 'pendiente'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE solicitudes_referencia MODIFY COLUMN estado ENUM('pendiente','en_espera','completado','negado') NOT NULL DEFAULT 'pendiente'");
    }
};
