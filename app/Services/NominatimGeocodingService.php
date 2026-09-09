<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Geocodificación por dirección usando Nominatim (OpenStreetMap), gratuito.
 * Política de uso de Nominatim: máx. 1 solicitud/segundo, User-Agent
 * identificable obligatorio, no cachear indefinidamente sin necesidad
 * (aquí el resultado se guarda una sola vez por clínica en la BD).
 */
class NominatimGeocodingService
{
    private const ENDPOINT = 'https://nominatim.openstreetmap.org/search';

    /**
     * @return array{lat: float, lon: float}|null
     */
    public function geocode(?string $direccion, ?string $ciudad, ?string $departamento): ?array
    {
        $partes = array_filter([$direccion, $ciudad, $departamento, 'Colombia']);

        if (count($partes) <= 1) {
            return null;
        }

        $consulta = implode(', ', $partes);

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'ReferenciaCAC-SantaBarbara/1.0 (contacto@cacsantabarbara.co)',
            ])
                ->timeout(6)
                ->get(self::ENDPOINT, [
                    'q' => $consulta,
                    'format' => 'json',
                    'limit' => 1,
                    'countrycodes' => 'co',
                ]);

            if (! $response->successful()) {
                return null;
            }

            $resultados = $response->json();

            if (empty($resultados)) {
                return $this->geocodeSoloCiudad($ciudad, $departamento);
            }

            return [
                'lat' => (float) $resultados[0]['lat'],
                'lon' => (float) $resultados[0]['lon'],
            ];
        } catch (\Throwable $e) {
            Log::warning('Geocodificación Nominatim falló: '.$e->getMessage(), ['consulta' => $consulta]);

            return null;
        }
    }

    /**
     * Reintento sin la dirección exacta: cuando la dirección no existe en
     * el mapa (calles inventadas, direcciones con errores), Nominatim no
     * devuelve nada para la consulta completa. Repetir solo con
     * ciudad+departamento da al menos un punto real dentro de la ciudad.
     *
     * @return array{lat: float, lon: float}|null
     */
    private function geocodeSoloCiudad(?string $ciudad, ?string $departamento): ?array
    {
        $partes = array_filter([$ciudad, $departamento, 'Colombia']);

        if (count($partes) <= 1) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'ReferenciaCAC-SantaBarbara/1.0 (contacto@cacsantabarbara.co)',
            ])
                ->timeout(6)
                ->get(self::ENDPOINT, [
                    'q' => implode(', ', $partes),
                    'format' => 'json',
                    'limit' => 1,
                    'countrycodes' => 'co',
                ]);

            $resultados = $response->successful() ? $response->json() : [];

            if (empty($resultados)) {
                return null;
            }

            return [
                'lat' => (float) $resultados[0]['lat'],
                'lon' => (float) $resultados[0]['lon'],
            ];
        } catch (\Throwable $e) {
            Log::warning('Geocodificación Nominatim (solo ciudad) falló: '.$e->getMessage());

            return null;
        }
    }
}
