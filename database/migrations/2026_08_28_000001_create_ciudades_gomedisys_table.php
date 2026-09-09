<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo real de municipios, importado directo de la tabla
     * `generalPoliticalDivisions` de Gomedisys (conexión `gomedisys`,
     * nivel 3 = "Municipio") — mismo enfoque que diagnosticos_cie10:
     * en vez de escribir el nombre a mano y esperar que Gomedisys lo
     * reconozca al buscar, se elige de la lista real que Gomedisys ya
     * tiene, así el texto que se envía es exacto.
     */
    public function up(): void
    {
        Schema::create('ciudades_gomedisys', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('id_political_division')->unique();
            $table->string('municipio', 120);
            $table->string('departamento', 120);
            $table->string('nombre', 250);
            $table->timestamps();

            $table->fullText('nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ciudades_gomedisys');
    }
};
