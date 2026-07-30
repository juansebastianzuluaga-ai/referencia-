<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE solicitudes_referencia MODIFY COLUMN estado ENUM('pendiente','aceptado','en_espera','completado','negado') NOT NULL DEFAULT 'pendiente'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE solicitudes_referencia MODIFY COLUMN estado ENUM('pendiente','aceptado','negado') NOT NULL DEFAULT 'pendiente'");
    }
};
