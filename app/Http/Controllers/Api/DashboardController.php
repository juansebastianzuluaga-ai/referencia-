<?php

namespace App\Http\Controllers\Api;

use App\Models\Clinica;
use App\Models\SolicitudReferencia;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends BaseController
{
    public function stats(Request $request): JsonResponse
    {
        $query = SolicitudReferencia::query();

        if ($request->filled('desde')) {
            $query->whereDate('created_at', '>=', $request->input('desde'));
        }
        if ($request->filled('hasta')) {
            $query->whereDate('created_at', '<=', $request->input('hasta'));
        }
        if ($request->filled('estado') && $request->input('estado') !== 'todas') {
            $query->where('estado', $request->input('estado'));
        }
        if ($request->filled('especialidad')) {
            $query->where('especialidad_requerida', $request->input('especialidad'));
        }
        if ($request->filled('eps')) {
            $query->where('eps', $request->input('eps'));
        }

        $solicitudesTotal = (clone $query)->count();
        $solicitudesPendientes = (clone $query)->where('estado', 'pendiente')->count();
        $solicitudesAceptadas = (clone $query)->where('estado', 'aceptado')->count();
        $solicitudesEnEspera = (clone $query)->where('estado', 'en_espera')->count();
        $solicitudesCompletadas = (clone $query)->where('estado', 'completado')->count();
        $solicitudesNegadas = (clone $query)->where('estado', 'negado')->count();

        $clinicasTotal = Clinica::count();
        $clinicasActivas = Clinica::where('is_active', true)->count();
        $clinicasPendientes = Clinica::where('estado', 'pendiente')->count();

        $usuariosTotal = User::count();
        $usuariosActivos = User::where('is_active', true)->count();

        $solicitudesRecientes = (clone $query)->with('clinica:id,nombre')
            ->select('id', 'clinica_id', 'primer_nombre', 'primer_apellido', 'estado', 'especialidad_requerida', 'eps', 'created_at')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'paciente' => trim("{$s->primer_nombre} {$s->primer_apellido}"),
                'estado' => $s->estado,
                'especialidad' => $s->especialidad_requerida,
                'eps' => $s->eps,
                'clinica' => $s->clinica?->nombre,
                'created_at' => $s->created_at?->toISOString(),
            ]);

        $especialidades = SolicitudReferencia::select('especialidad_requerida')
            ->distinct()
            ->orderBy('especialidad_requerida')
            ->pluck('especialidad_requerida')
            ->filter()
            ->values();

        $epsList = SolicitudReferencia::select('eps')
            ->distinct()
            ->orderBy('eps')
            ->pluck('eps')
            ->filter()
            ->values();

        return $this->sendResponse([
            'solicitudes' => [
                'total' => $solicitudesTotal,
                'pendientes' => $solicitudesPendientes,
                'aceptadas' => $solicitudesAceptadas,
                'en_espera' => $solicitudesEnEspera,
                'completadas' => $solicitudesCompletadas,
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
            'filtros' => [
                'especialidades' => $especialidades,
                'eps' => $epsList,
            ],
        ], 'Estadísticas del dashboard');
    }
}
