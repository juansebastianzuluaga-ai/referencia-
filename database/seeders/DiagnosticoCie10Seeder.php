<?php

namespace Database\Seeders;

use App\Models\DiagnosticoCie10;
use Illuminate\Database\Seeder;

class DiagnosticoCie10Seeder extends Seeder
{
    /**
     * Catálogo CIE-10 usado por el autocompletado de "Diagnóstico" del
     * formulario externo. Los datos vienen de la tabla de referencia real
     * de Gomedisys (export "TablaReferencia_CIE10", convertida a
     * database/seeders/data/cie10.json) — así solo se pueden escoger
     * diagnósticos que Gomedisys realmente acepta al diligenciar.
     *
     * Se reemplaza el catálogo completo: truncate + insert, para que no
     * queden códigos viejos que Gomedisys no reconoce.
     */
    public function run(): void
    {
        $dataset = json_decode(
            file_get_contents(database_path('seeders/data/cie10.json')),
            true,
        );

        DiagnosticoCie10::truncate();

        $filas = array_map(fn (array $d) => [
            'codigo' => $d['codigo'],
            'descripcion' => $d['descripcion'],
            'created_at' => now(),
            'updated_at' => now(),
        ], $dataset);

        foreach (array_chunk($filas, 1000) as $chunk) {
            DiagnosticoCie10::insert($chunk);
        }
    }
}
