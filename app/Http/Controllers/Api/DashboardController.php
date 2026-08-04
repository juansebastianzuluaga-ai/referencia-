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
            ->select('id', 'clinica_id', 'tipo_documento', 'numero_documento', 'primer_nombre', 'segundo_nombre', 'primer_apellido', 'segundo_apellido', 'telefono_contacto', 'estado', 'especialidad_requerida', 'eps', 'created_at')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'paciente' => trim("{$s->primer_nombre} {$s->primer_apellido}"),
                'nombre_completo' => trim("{$s->primer_nombre} {$s->segundo_nombre} {$s->primer_apellido} {$s->segundo_apellido}"),
                'tipo_documento' => $s->tipo_documento,
                'numero_documento' => $s->numero_documento,
                'telefono_contacto' => $s->telefono_contacto,
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

        $tendencia = $this->tendenciaUltimosDias(14);

        $topEspecialidades = (clone $query)
            ->select('especialidad_requerida')
            ->selectRaw('COUNT(*) as total')
            ->whereNotNull('especialidad_requerida')
            ->groupBy('especialidad_requerida')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'especialidad' => mb_strlen($r->especialidad_requerida) > 30
                    ? mb_substr($r->especialidad_requerida, 0, 30).'...'
                    : $r->especialidad_requerida,
                'total' => (int) $r->total,
            ]);

        $resueltas = $solicitudesAceptadas + $solicitudesNegadas;
        $tasaAceptacion = $resueltas > 0 ? round(($solicitudesAceptadas / $resueltas) * 100) : 0;

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
            'tendencia' => $tendencia,
            'top_especialidades' => $topEspecialidades,
            'tasa_aceptacion' => $tasaAceptacion,
        ], 'Estadísticas del dashboard');
    }

    /**
     * @return array<int, array{fecha: string, total: int}>
     */
    private function tendenciaUltimosDias(int $dias): array
    {
        $desde = now()->subDays($dias - 1)->startOfDay();

        $conteos = SolicitudReferencia::selectRaw('DATE(created_at) as fecha, COUNT(*) as total')
            ->where('created_at', '>=', $desde)
            ->groupByRaw('DATE(created_at)')
            ->pluck('total', 'fecha');

        $resultado = [];
        for ($i = $dias - 1; $i >= 0; $i--) {
            $fecha = now()->subDays($i)->format('Y-m-d');
            $resultado[] = [
                'fecha' => $fecha,
                'total' => (int) ($conteos[$fecha] ?? 0),
            ];
        }

        return $resultado;
    }
}
