<?php

namespace App\Console\Commands;

use App\Models\Clinica;
use App\Services\NominatimGeocodingService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:geocodificar-clinicas {--forzar : Vuelve a geocodificar incluso las clínicas que ya tienen coordenadas}')]
#[Description('Geocodifica las clínicas sin latitud/longitud usando Nominatim (OpenStreetMap)')]
class GeocodificarClinicas extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(NominatimGeocodingService $geocoding): int
    {
        $query = Clinica::query();

        if (! $this->option('forzar')) {
            $query->whereNull('latitud');
        }

        $clinicas = $query->get();

        if ($clinicas->isEmpty()) {
            $this->info('No hay clínicas pendientes de geocodificar.');

            return self::SUCCESS;
        }

        $this->info("Geocodificando {$clinicas->count()} clínica(s)...");
        $bar = $this->output->createProgressBar($clinicas->count());
        $ok = 0;
        $fallidas = 0;

        foreach ($clinicas as $clinica) {
            $coords = $geocoding->geocode($clinica->direccion, $clinica->ciudad, $clinica->departamento);

            if ($coords) {
                $clinica->update([
                    'latitud' => $coords['lat'],
                    'longitud' => $coords['lon'],
                    'geocoded_at' => now(),
                ]);
                $ok++;
            } else {
                $fallidas++;
            }

            $bar->advance();

            // Política de uso de Nominatim: máx. 1 solicitud por segundo.
            if (! $clinicas->last() || $clinica->isNot($clinicas->last())) {
                sleep(1);
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Completado: {$ok} geocodificadas, {$fallidas} sin resultado.");

        return self::SUCCESS;
    }
}
