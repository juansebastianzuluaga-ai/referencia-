<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo real de diagnósticos CIE-10, importado directo de la tabla
     * `diagnostics` de Gomedisys (conexión `gomedisys`) — reemplaza la
     * lista fija que traía la app (332 códigos de categoría incompletos,
     * ej. "B34" en vez de "B34.2") por los ~12.500 códigos específicos que
     * Gomedisys realmente tiene, para que lo que se elija acá siempre
     * exista allá.
     */
    public function up(): void
    {
        Schema::create('diagnosticos_cie10', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10)->unique();
            $table->string('descripcion', 400);
            $table->timestamps();

            $table->fullText('descripcion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnosticos_cie10');
    }
};
