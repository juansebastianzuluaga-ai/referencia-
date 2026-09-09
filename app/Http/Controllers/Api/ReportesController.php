<?php

namespace App\Http\Controllers\Api;

use App\Models\SolicitudReferencia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class ReportesController extends BaseController
{
    private const MESES = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    private const ESTADO_LABELS = [
        'pendiente' => 'Pendiente',
        'en_espera' => 'En espera',
        'completado' => 'Completada',
        'negado' => 'Negada',
    ];

    private const ESTADO_COLORS = [
        'pendiente' => '#eab308',
        'en_espera' => '#3b82f6',
        'completado' => '#22c55e',
        'negado' => '#ef4444',
    ];

    public function stats(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasPermission('clinicas.view'), Response::HTTP_FORBIDDEN);

        [$granularidad, $desde, $hasta] = $this->resolverPeriodo($request);

        $duracionDias = $desde->diffInDays($hasta) + 1;
        $prevHasta = $desde->copy()->subDay()->endOfDay();
        $prevDesde = $prevHasta->copy()->subDays($duracionDias - 1)->startOfDay();

        $query = fn () => $this->aplicarFiltros(SolicitudReferencia::query(), $request, $desde, $hasta);
        $queryAnterior = fn () => $this->aplicarFiltros(SolicitudReferencia::query(), $request, $prevDesde, $prevHasta);

        $total = (clone $query())->count();
        $totalAnterior = (clone $queryAnterior())->count();

        $pendientes = (clone $query())->where('estado', 'pendiente')->count();
        $pendientesAnterior = (clone $queryAnterior())->where('estado', 'pendiente')->count();

        // "Aceptadas" ya no es un estado propio (aceptar pasa directo a "en
        // espera") — se cuenta como toda solicitud que avanzó más allá de
        // pendiente sin ser negada.
        $aceptadas = (clone $query())->whereIn('estado', ['en_espera', 'completado'])->count();
        $aceptadasAnterior = (clone $queryAnterior())->whereIn('estado', ['en_espera', 'completado'])->count();

        $negadas = (clone $query())->where('estado', 'negado')->count();
        $negadasAnterior = (clone $queryAnterior())->where('estado', 'negado')->count();

        $resueltas = $aceptadas + $negadas;
        $resueltasAnterior = $aceptadasAnterior + $negadasAnterior;
        $tasaAceptacion = $resueltas > 0 ? round(($aceptadas / $resueltas) * 100, 1) : 0;
        $tasaAceptacionAnterior = $resueltasAnterior > 0 ? round(($aceptadasAnterior / $resueltasAnterior) * 100, 1) : 0;

        $tendencia = $this->tendencia($query(), $desde, $hasta, $granularidad);
        $tendenciaAnterior = $this->tendencia($queryAnterior(), $prevDesde, $prevHasta, $granularidad);

        $distribucionEstado = $this->distribucionEstado($query(), $total);
        [$distribucionEspecialidad, $topEspecialidad] = $this->distribucionEspecialidad($query(), $total);
        $heatmap = $this->heatmap($request, $hasta, $topEspecialidad['labels']);
        $tiempoRespuesta = $this->tiempoRespuestaConDelta($query(), $queryAnterior());

        return $this->sendResponse([
            'periodo' => [
                'granularidad' => $granularidad,
                'desde' => $desde->toDateString(),
                'hasta' => $hasta->toDateString(),
            ],
            'tarjetas' => [
                'total' => ['valor' => $total, 'delta' => $this->deltaPct($total, $totalAnterior)],
                'pendientes' => ['valor' => $pendientes, 'delta' => $this->deltaPct($pendientes, $pendientesAnterior)],
                'aceptadas' => ['valor' => $aceptadas, 'delta' => $this->deltaPct($aceptadas, $aceptadasAnterior)],
                'tasa_aceptacion' => ['valor' => $tasaAceptacion, 'delta' => round($tasaAceptacion - $tasaAceptacionAnterior, 1)],
            ],
            'tendencia' => [
                'actual' => $tendencia,
                'anterior' => $tendenciaAnterior,
            ],
            'distribucion_estado' => $distribucionEstado,
            'distribucion_especialidad' => $distribucionEspecialidad,
            'heatmap' => $heatmap,
            'tiempo_respuesta' => $tiempoRespuesta,
            'filtros' => [
                'especialidades' => SolicitudReferencia::select('especialidad_requerida')->distinct()->whereNotNull('especialidad_requerida')->orderBy('especialidad_requerida')->pluck('especialidad_requerida'),
            ],
        ], 'Estadísticas de reportes');
    }

    public function exportar(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasPermission('clinicas.view'), Response::HTTP_FORBIDDEN);

        [, $desde, $hasta] = $this->resolverPeriodo($request);

        $filas = $this->aplicarFiltros(SolicitudReferencia::query(), $request, $desde, $hasta)
            ->with('clinica:id,nombre')
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->get()
            ->map(fn (SolicitudReferencia $s) => [
                'id' => $s->id,
                'codigo_aceptacion' => $s->codigo_aceptacion,
                'paciente' => trim("{$s->primer_nombre} {$s->segundo_nombre} {$s->primer_apellido} {$s->segundo_apellido}"),
                'tipo_documento' => $s->tipo_documento,
                'numero_documento' => $s->numero_documento,
                'eps' => $s->eps,
                'especialidad' => $s->especialidad_requerida,
                'clinica' => $s->clinica?->nombre,
                'estado' => self::ESTADO_LABELS[$s->estado] ?? $s->estado,
                'fecha' => $s->fecha?->format('Y-m-d'),
                'hora' => $s->hora,
            ]);

        return $this->sendResponse(['filas' => $filas, 'total' => $filas->count()], 'Datos exportados');
    }

    /**
     * @return array{0: string, 1: Carbon, 2: Carbon}
     */
    private function resolverPeriodo(Request $request): array
    {
        $granularidad = in_array($request->input('granularidad'), ['dia', 'mes', 'anio'], true)
            ? $request->input('granularidad')
            : 'dia';

        $hasta = $request->filled('hasta') ? Carbon::parse($request->input('hasta'))->endOfDay() : now()->endOfDay();

        if ($request->filled('desde')) {
            $desde = Carbon::parse($request->input('desde'))->startOfDay();
        } else {
            $desde = match ($granularidad) {
                'anio' => $hasta->copy()->subYears(4)->startOfYear(),
                'mes' => $hasta->copy()->subMonths(11)->startOfMonth(),
                default => $hasta->copy()->subDays(29)->startOfDay(),
            };
        }

        return [$granularidad, $desde, $hasta];
    }

    private function aplicarFiltros($query, Request $request, Carbon $desde, Carbon $hasta)
    {
        $query->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()]);

        return $this->aplicarFiltrosSinFecha($query, $request);
    }

    /** Mismo filtro de estado/especialidad, pero sin acotar fechas — lo usa el mapa de calor, que necesita su propia ventana. */
    private function aplicarFiltrosSinFecha($query, Request $request)
    {
        if ($request->filled('estado') && $request->input('estado') !== 'todas') {
            $query->where('estado', $request->input('estado'));
        }

        if ($request->filled('especialidad')) {
            $query->where('especialidad_requerida', $request->input('especialidad'));
        }

        return $query;
    }

    private function deltaPct(int $actual, int $anterior): float
    {
        if ($anterior === 0) {
            return $actual > 0 ? 100.0 : 0.0;
        }

        return round((($actual - $anterior) / $anterior) * 100, 1);
    }

    /**
     * @return array<int, array{label: string, total: int}>
     */
    private function tendencia($query, Carbon $desde, Carbon $hasta, string $granularidad): array
    {
        $expr = match ($granularidad) {
            'anio' => 'YEAR(fecha)',
            'mes' => "DATE_FORMAT(fecha, '%Y-%m')",
            default => 'fecha',
        };

        $conteos = (clone $query)
            ->selectRaw("{$expr} as bucket, COUNT(*) as total")
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        $resultado = [];

        if ($granularidad === 'anio') {
            for ($y = $desde->year; $y <= $hasta->year; $y++) {
                $resultado[] = ['label' => (string) $y, 'total' => (int) ($conteos[$y] ?? 0)];
            }
        } elseif ($granularidad === 'mes') {
            $cursor = $desde->copy()->startOfMonth();
            $fin = $hasta->copy()->startOfMonth();
            while ($cursor->lte($fin)) {
                $key = $cursor->format('Y-m');
                $resultado[] = ['label' => self::MESES[$cursor->month - 1].' '.$cursor->format('y'), 'total' => (int) ($conteos[$key] ?? 0)];
                $cursor->addMonth();
            }
        } else {
            $cursor = $desde->copy()->startOfDay();
            while ($cursor->lte($hasta)) {
                $key = $cursor->format('Y-m-d');
                $resultado[] = ['label' => $cursor->day.' '.self::MESES[$cursor->month - 1], 'total' => (int) ($conteos[$key] ?? 0)];
                $cursor->addDay();
            }
        }

        return $resultado;
    }

    /**
     * @return array<int, array{estado: string, label: string, total: int, pct: float, color: string}>
     */
    private function distribucionEstado($query, int $total): array
    {
        $conteos = (clone $query)
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return collect(self::ESTADO_LABELS)->map(function ($label, $estado) use ($conteos, $total) {
            $t = (int) ($conteos[$estado] ?? 0);

            return [
                'estado' => $estado,
                'label' => $label,
                'total' => $t,
                'pct' => $total > 0 ? round(($t / $total) * 100, 1) : 0,
                'color' => self::ESTADO_COLORS[$estado],
            ];
        })->values()->all();
    }

    /**
     * @return array{0: array<int, array{label: string, total: int, pct: float}>, 1: array{labels: array<int, string>, top: string|null, top_pct: float}}
     */
    private function distribucionEspecialidad($query, int $total): array
    {
        $filas = (clone $query)
            ->select('especialidad_requerida')
            ->selectRaw('COUNT(*) as total')
            ->whereNotNull('especialidad_requerida')
            ->groupBy('especialidad_requerida')
            ->orderByDesc('total')
            ->get();

        $top6 = $filas->take(6);
        $resto = $filas->skip(6)->sum('total');

        $resultado = $top6->map(fn ($r) => [
            'label' => $r->especialidad_requerida,
            'total' => (int) $r->total,
            'pct' => $total > 0 ? round(($r->total / $total) * 100, 1) : 0,
        ])->values()->all();

        if ($resto > 0) {
            $resultado[] = [
                'label' => 'Otras especialidades',
                'total' => (int) $resto,
                'pct' => $total > 0 ? round(($resto / $total) * 100, 1) : 0,
            ];
        }

        $primera = $filas->first();

        return [
            $resultado,
            [
                'labels' => $top6->pluck('especialidad_requerida')->values()->all(),
                'top' => $primera?->especialidad_requerida,
                'top_pct' => $primera && $total > 0 ? round(($primera->total / $total) * 100, 1) : 0,
            ],
        ];
    }

    /**
     * Mapa de calor: mes calendario × especialidad (top 6 del periodo filtrado).
     *
     * Usa su propia ventana de al menos 6 meses en vez de la de la tendencia
     * (que en la vista "Día" por defecto son solo 30 días) — si se acotara al
     * mismo rango, casi siempre se vería una sola columna con datos.
     *
     * @param array<int, string> $especialidades
     * @return array<int, array{especialidad: string, mes: string, total: int}>
     */
    private function heatmap(Request $request, Carbon $hastaOriginal, array $especialidades): array
    {
        if (empty($especialidades)) {
            return [];
        }

        $hasta = $hastaOriginal->copy();
        $desde = $hasta->copy()->subMonths(5)->startOfMonth();

        $query = $this->aplicarFiltrosSinFecha(SolicitudReferencia::query(), $request)
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()]);

        $conteos = (clone $query)
            ->whereIn('especialidad_requerida', $especialidades)
            ->selectRaw("especialidad_requerida, DATE_FORMAT(fecha, '%Y-%m') as bucket, COUNT(*) as total")
            ->groupBy('especialidad_requerida', 'bucket')
            ->get()
            ->groupBy('especialidad_requerida');

        $meses = [];
        $cursor = $desde->copy()->startOfMonth();
        $fin = $hasta->copy()->startOfMonth();
        while ($cursor->lte($fin) && count($meses) < 12) {
            $meses[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        $resultado = [];
        foreach ($especialidades as $especialidad) {
            $porMes = ($conteos[$especialidad] ?? collect())->pluck('total', 'bucket');
            foreach ($meses as $mesKey) {
                [$anio, $mes] = explode('-', $mesKey);
                $resultado[] = [
                    'especialidad' => $especialidad,
                    'mes' => self::MESES[((int) $mes) - 1].' '.substr($anio, 2),
                    'total' => (int) ($porMes[$mesKey] ?? 0),
                ];
            }
        }

        return $resultado;
    }

    /**
     * @return array<int, array{estado: string, label: string, dias: float, delta: float}>
     */
    private function tiempoRespuestaConDelta($query, $queryAnterior): array
    {
        $actual = $this->tiempoRespuesta($query);
        $anterior = collect($this->tiempoRespuesta($queryAnterior))->keyBy('estado');

        return collect($actual)->map(function ($fila) use ($anterior) {
            $diasAnterior = $anterior[$fila['estado']]['dias'] ?? 0;
            $fila['delta'] = round($fila['dias'] - $diasAnterior, 1);

            return $fila;
        })->values()->all();
    }

    /**
     * Tiempo de respuesta promedio (en días) por estado, calculado sobre
     * solicitudes que ya tienen hora de respuesta registrada.
     *
     * @return array<int, array{estado: string, label: string, dias: float}>
     */
    private function tiempoRespuesta($query): array
    {
        $filas = (clone $query)
            ->whereNotNull('hora_respuesta')
            ->whereIn('estado', ['completado', 'en_espera', 'negado'])
            ->selectRaw('estado, ABS(TIMESTAMPDIFF(MINUTE, TIMESTAMP(fecha, hora), TIMESTAMP(fecha, hora_respuesta))) as minutos')
            ->get()
            ->groupBy('estado');

        $resultado = [];
        foreach (['completado', 'en_espera', 'negado'] as $estado) {
            $grupo = $filas[$estado] ?? collect();
            $minutosValidos = $grupo->pluck('minutos')->filter(fn ($m) => $m !== null);
            $promedioMin = $minutosValidos->count() > 0 ? $minutosValidos->avg() : 0;

            $resultado[] = [
                'estado' => $estado,
                'label' => self::ESTADO_LABELS[$estado],
                'dias' => round($promedioMin / (60 * 24), 1),
            ];
        }

        return $resultado;
    }

}
