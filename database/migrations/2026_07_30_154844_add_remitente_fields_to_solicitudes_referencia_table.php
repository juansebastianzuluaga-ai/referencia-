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
            $table->string('quien_remitente', 200)->nullable()->after('servicio_remision');
            $table->string('telefono_contacto', 30)->nullable()->after('quien_remitente');
            $table->string('correo_contacto', 150)->nullable()->after('telefono_contacto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitudes_referencia', function (Blueprint $table) {
            $table->dropColumn(['quien_remitente', 'telefono_contacto', 'correo_contacto']);
        });
    }
};
