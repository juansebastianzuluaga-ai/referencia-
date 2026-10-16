<?php

namespace Database\Seeders;

use App\Models\CiudadGomedisys;
use Illuminate\Database\Seeder;

class CiudadGomedisysSeeder extends Seeder
{
    /**
     * Catálogo local de municipios de Colombia (~1.104), usado por el campo
     * "Municipio" del formulario externo. Los datos vienen del dataset
     * público colombia-json (database/seeders/data/colombia-municipios.json)
     * porque este entorno no tiene el driver sqlsrv para leer la tabla
     * `generalPoliticalDivisions` de Gomedisys. `id_political_division` es
     * sintético: el formulario solo envía `nombre` ("Municipio, Departamento").
     */
    public function run(): void
    {
        $dataset = json_decode(
            file_get_contents(database_path('seeders/data/colombia-municipios.json')),
            true,
        );

        $id = 0;
        $filas = [];

        foreach ($dataset as $departamento) {
            foreach ($departamento['ciudades'] as $municipio) {
                $filas[] = [
                    'id_political_division' => ++$id,
                    'municipio' => $municipio,
                    'departamento' => $departamento['departamento'],
                    'nombre' => "{$municipio}, {$departamento['departamento']}",
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        CiudadGomedisys::upsert($filas, ['id_political_division'], ['municipio', 'departamento', 'nombre']);
    }
}
