<?php

namespace App\Http\Controllers\Api;

use App\Models\Clinica;
use App\Models\SolicitudReferencia;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends BaseController
{
    public function stats(): JsonResponse
    {
        $solicitudesTotal = SolicitudReferencia::count();
        $solicitudesPendientes = SolicitudReferencia::where('estado', 'pendiente')->count();
        $solicitudesAceptadas = SolicitudReferencia::where('estado', 'aceptado')->count();
        $solicitudesNegadas = SolicitudReferencia::where('estado', 'negado')->count();

        $clinicasTotal = Clinica::count();
        $clinicasActivas = Clinica::where('is_active', true)->count();
        $clinicasPendientes = Clinica::where('estado', 'pendiente')->count();

        $usuariosTotal = User::count();
        $usuariosActivos = User::where('is_active', true)->count();

        $solicitudesRecientes = SolicitudReferencia::with('clinica:id,nombre')
            ->select('id', 'clinica_id', 'primer_nombre', 'primer_apellido', 'estado', 'especialidad_requerida', 'created_at')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'paciente' => trim("{$s->primer_nombre} {$s->primer_apellido}"),
                'estado' => $s->estado,
                'especialidad' => $s->especialidad_requerida,
                'clinica' => $s->clinica?->nombre,
                'created_at' => $s->created_at?->toISOString(),
            ]);

        return $this->sendResponse([
            'solicitudes' => [
                'total' => $solicitudesTotal,
                'pendientes' => $solicitudesPendientes,
                'aceptadas' => $solicitudesAceptadas,
                'negadas' => $solicitudesNegadas,
            ],
            'clinicas' => [
                'total' => $clinicasTotal,
                'activas' => $clinicasActivas,
                'pendientes' => $clinicasPendientes,
            ],
            'usuarios' => [
                'total' => $usuariosTotal,
                'activos' => $usuariosActivos,
            ],
            'solicitudes_recientes' => $solicitudesRecientes,
        ], 'Estadísticas del dashboard');
    }
}
