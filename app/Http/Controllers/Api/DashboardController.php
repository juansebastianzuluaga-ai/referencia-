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
            $query->where('solicitudes_referencia.estado', $request->input('estado'));
        }
        if ($request->filled('especialidad')) {
            $query->where('especialidad_requerida', $request->input('especialidad'));
        }
        if ($request->filled('eps')) {
            $query->where('eps', $request->input('eps'));
        }

        $solicitudesTotal = (clone $query)->count();
        $solicitudesPendientes = (clone $query)->where('solicitudes_referencia.estado', 'pendiente')->count();
        $solicitudesEnEspera = (clone $query)->where('solicitudes_referencia.estado', 'en_espera')->count();
        $solicitudesCompletadas = (clone $query)->where('solicitudes_referencia.estado', 'completado')->count();
        $solicitudesNegadas = (clone $query)->where('solicitudes_referencia.estado', 'negado')->count();
        // "Aceptadas" ya no es un estado propio (aceptar pasa directo a
        // "en espera") — para la tasa de aceptación se cuenta como toda
        // solicitud que avanzó más allá de pendiente sin ser negada.
        $solicitudesAceptadas = $solicitudesEnEspera + $solicitudesCompletadas;

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

        $tendencia = $this->tendenciaUltimosDias(SolicitudReferencia::class, 14);
        $tendenciaClinicas = $this->tendenciaUltimosDias(Clinica::class, 14);
        $tendenciaUsuarios = $this->tendenciaUltimosDias(User::class, 14);
        $tendenciaPendientes = $this->tendenciaUltimosDias(SolicitudReferencia::class, 14, fn ($q) => $q->where('estado', 'pendiente'));

        // Hace cuánto está esperando revisión la solicitud pendiente más
        // antigua — el volumen total no dice si el personal va al día o si
        // algo lleva días sin atenderse.
        $pendienteMasAntigua = (clone $query)->where('estado', 'pendiente')->min('created_at');
        $pendienteMasAntiguaHoras = $pendienteMasAntigua ? abs(now()->diffInHours($pendienteMasAntigua)) : null;

        $topEspecialidades = (clone $query)
            ->select('especialidad_requerida')
            ->selectRaw('COUNT(*) as total')
            ->whereNotNull('especialidad_requerida')
            ->groupBy('especialidad_requerida')
            ->orderByDesc('total')
            ->limit(15)
            ->get()
            ->map(fn ($r) => [
                'especialidad' => mb_strlen($r->especialidad_requerida) > 30
                    ? mb_substr($r->especialidad_requerida, 0, 30).'...'
                    : $r->especialidad_requerida,
                'total' => (int) $r->total,
            ]);

        $resueltas = $solicitudesAceptadas + $solicitudesNegadas;
        $tasaAceptacion = $resueltas > 0 ? round(($solicitudesAceptadas / $resueltas) * 100) : 0;

        $porDiaHora = $this->porDiaHora();
        $porClinica = (clone $query)
            ->join('clinicas', 'clinicas.id', '=', 'solicitudes_referencia.clinica_id')
            ->select('clinicas.nombre')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('clinicas.nombre')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($r) => ['clinica' => $r->nombre, 'total' => (int) $r->total]);

        $porEps = (clone $query)
            ->select('eps')
            ->selectRaw('COUNT(*) as total')
            ->whereNotNull('eps')
            ->where('eps', '!=', '')
            ->groupBy('eps')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($r) => ['eps' => $r->eps, 'total' => (int) $r->total]);

        return $this->sendResponse([
            'solicitudes' => [
                'total' => $solicitudesTotal,
                'pendientes' => $solicitudesPendientes,
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
            'tendencia_clinicas' => $tendenciaClinicas,
            'tendencia_usuarios' => $tendenciaUsuarios,
            'tendencia_pendientes' => $tendenciaPendientes,
            'pendiente_mas_antigua_horas' => $pendienteMasAntiguaHoras,
            'top_especialidades' => $topEspecialidades,
            'tasa_aceptacion' => $tasaAceptacion,
            'por_dia_hora' => $porDiaHora,
            'por_clinica' => $porClinica,
            'por_eps' => $porEps,
        ], 'Estadísticas del dashboard');
    }

    /**
     * Mapa de calor de solicitudes por día de la semana × franja horaria
     * (bloques de 2h), sobre el histórico completo (el volumen de datos es
     * bajo, así que limitar a un periodo corto dejaría casi todo en cero).
     *
     * @return array<int, array{dia: string, hora: string, total: int}>
     */
    private function porDiaHora(): array
    {
        $dias = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
        $horas = range(0, 22, 2);

        $conteos = SolicitudReferencia::selectRaw('DAYOFWEEK(created_at) as dow, HOUR(created_at) as hora, COUNT(*) as total')
            ->groupByRaw('DAYOFWEEK(created_at), HOUR(created_at)')
            ->get();

        $grid = [];
        foreach ($dias as $dia) {
            foreach ($horas as $h) {
                $grid[$dia][$h] = 0;
            }
        }
        foreach ($conteos as $fila) {
            $dia = $dias[$fila->dow - 1];
            $bloque = (int) (floor($fila->hora / 2) * 2);
            $grid[$dia][$bloque] += (int) $fila->total;
        }

        $resultado = [];
        // Lunes a domingo, como en el mockup.
        $orden = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
        foreach ($orden as $dia) {
            foreach ($horas as $h) {
                $resultado[] = [
                    'dia' => $dia,
                    'hora' => str_pad((string) $h, 2, '0', STR_PAD_LEFT).':00',
                    'total' => $grid[$dia][$h],
                ];
            }
        }

        return $resultado;
    }

    /**
     * Conteo diario de registros nuevos de $modelClass en los últimos $dias
     * días (opcionalmente acotado con $scope, ej. solo estado 'pendiente').
     * Genérico para poder graficar la misma tendencia sobre Solicitudes,
     * Clínicas o Usuarios sin repetir la consulta tres veces.
     *
     * @param  class-string  $modelClass
     * @return array<int, array{fecha: string, total: int}>
     */
    private function tendenciaUltimosDias(string $modelClass, int $dias, ?\Closure $scope = null): array
    {
        $desde = now()->subDays($dias - 1)->startOfDay();

        $query = $modelClass::query()->where('created_at', '>=', $desde);
        if ($scope) {
            $scope($query);
        }

        $conteos = $query->selectRaw('DATE(created_at) as fecha, COUNT(*) as total')
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
