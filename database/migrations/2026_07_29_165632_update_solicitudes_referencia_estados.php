<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite no soporta MODIFY COLUMN — el enum nativo es una columna
        // varchar con CHECK, así que se reescribe como string (change()
        // reconstruye la tabla y quita el CHECK viejo).
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('solicitudes_referencia', function (Blueprint $table) {
                $table->string('estado')->default('pendiente')->change();
            });

            return;
        }

        DB::statement("ALTER TABLE solicitudes_referencia MODIFY COLUMN estado ENUM('pendiente','aceptado','en_espera','completado','negado') NOT NULL DEFAULT 'pendiente'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE solicitudes_referencia MODIFY COLUMN estado ENUM('pendiente','aceptado','negado') NOT NULL DEFAULT 'pendiente'");
    }
};
