<?php

namespace Database\Seeders;

use App\Models\DiagnosticoCie10;
use Illuminate\Database\Seeder;

class DiagnosticoCie10Seeder extends Seeder
{
    /**
     * Catálogo CIE-10 en español (~14.200 códigos), usado por el
     * autocompletado de "Diagnóstico" del formulario externo. Los datos
     * vienen del dataset público verasativa/CIE-10
     * (database/seeders/data/cie10.json, solo códigos hoja — sin rangos
     * de capítulo tipo "A00-B99") porque este entorno no tiene el driver
     * sqlsrv para leer la tabla `diagnostics` de Gomedisys.
     */
    public function run(): void
    {
        $dataset = json_decode(
            file_get_contents(database_path('seeders/data/cie10.json')),
            true,
        );

        $filas = array_map(fn (array $d) => [
            'codigo' => $d['codigo'],
            'descripcion' => $d['descripcion'],
            'created_at' => now(),
            'updated_at' => now(),
        ], $dataset);

        foreach (array_chunk($filas, 1000) as $chunk) {
            DiagnosticoCie10::upsert($chunk, ['codigo'], ['descripcion']);
        }
    }
}
