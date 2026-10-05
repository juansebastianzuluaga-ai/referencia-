<?php

namespace App\Http\Controllers\Api;

use App\Models\Clinica;
use App\Models\SolicitudReferencia;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends BaseController
{
    private const MESES = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    public function stats(Request $request): JsonResponse
    {
        // Filtros de dimensión (estado/especialidad/eps/clínica) — se aplican
        // tanto al query principal como a las tendencias, que llevan su propia
        // ventana de fechas.
        $queryDims = SolicitudReferencia::query();
        if ($request->filled('estado') && $request->input('estado') !== 'todas') {
            $queryDims->where('solicitudes_referencia.estado', $request->input('estado'));
        }
        if ($request->filled('especialidad')) {
            $queryDims->where('especialidad_requerida', $request->input('especialidad'));
        }
        if ($request->filled('eps')) {
            $queryDims->where('eps', $request->input('eps'));
        }
        if ($request->filled('clinica')) {
            $queryDims->where('clinica_id', $request->input('clinica'));
        }

        $query = (clone $queryDims);
        if ($request->filled('desde')) {
            $query->whereDate('created_at', '>=', $request->input('desde'));
        }
        if ($request->filled('hasta')) {
            $query->whereDate('created_at', '<=', $request->input('hasta'));
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
        $clinicasNuevasMes = Clinica::where('created_at', '>=', now()->startOfMonth())->count();

        $usuariosTotal = User::count();
        $usuariosActivos = User::where('is_active', true)->count();

        $solicitudesRecientes = (clone $query)->with('clinica:id,nombre')
            ->select('id', 'clinica_id', 'tipo_documento', 'numero_documento', 'primer_nombre', 'segundo_nombre', 'primer_apellido', 'segundo_apellido', 'telefono_contacto', 'estado', 'especialidad_requerida', 'eps', 'fecha', 'hora', 'hora_respuesta', 'created_at')
            ->latest()
            ->limit(8)
            ->get()
            ->map(function ($s) {
                // "Tiempo": duración de la gestión si ya respondieron; si no,
                // tiempo que lleva esperando desde su creación.
                $minutos = null;
                if ($s->hora_respuesta && $s->fecha && $s->hora) {
                    $inicio = Carbon::parse("{$s->fecha->toDateString()} {$s->hora}");
                    $minutos = $inicio->diffInMinutes(Carbon::parse("{$s->fecha->toDateString()} {$s->hora_respuesta}"));
                } elseif ($s->created_at) {
                    $minutos = (int) abs(now()->diffInMinutes($s->created_at));
                }
                $tiempo = null;
                if ($minutos !== null) {
                    $tiempo = $minutos >= 60
                        ? intdiv($minutos, 60).'h '.str_pad((string) ($minutos % 60), 2, '0', STR_PAD_LEFT).'m'
                        : $minutos.'m';
                }

                return [
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
                    'fecha_solicitud' => $s->created_at?->format('d M Y · H:i'),
                    'tiempo' => $tiempo,
                    'created_at' => $s->created_at?->toISOString(),
                ];
            });

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

        $granularidad = in_array($request->input('granularidad'), ['dia', 'mes', 'anio'], true)
            ? $request->input('granularidad')
            : 'dia';
        $tendencia = $this->tendenciaPeriodo(clone $queryDims, $granularidad);
        $tendenciaAnterior = $this->tendenciaPeriodo(clone $queryDims, $granularidad, anterior: true);
        $tendenciaClinicas = $this->tendenciaUltimosDias(Clinica::class, 14);
        $tendenciaUsuarios = $this->tendenciaUltimosDias(User::class, 14);
        $tendenciaPendientes = $this->tendenciaUltimosDias(SolicitudReferencia::class, 14, fn ($q) => $q->where('estado', 'pendiente'), 0, $queryDims);
        $tendenciaPendientesAnterior = $this->tendenciaUltimosDias(SolicitudReferencia::class, 14, fn ($q) => $q->where('estado', 'pendiente'), 14, $queryDims);
        $tendenciaEnEspera = $this->tendenciaUltimosDias(SolicitudReferencia::class, 14, fn ($q) => $q->where('estado', 'en_espera'), 0, $queryDims);
        $tendenciaEnEsperaAnterior = $this->tendenciaUltimosDias(SolicitudReferencia::class, 14, fn ($q) => $q->where('estado', 'en_espera'), 14, $queryDims);
        $tendenciaCompletadas = $this->tendenciaUltimosDias(SolicitudReferencia::class, 14, fn ($q) => $q->where('estado', 'completado'), 0, $queryDims);
        $tendenciaCompletadasAnterior = $this->tendenciaUltimosDias(SolicitudReferencia::class, 14, fn ($q) => $q->where('estado', 'completado'), 14, $queryDims);

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
        $heatmapEspMes = $this->heatmapEspecialidadMes();
        $sla = $this->slaRespuesta(clone $query);
        $slaDiario = $this->slaDiario(14);
        $actividad = $this->actividadReciente(clone $query);
        $clinicasFiltro = Clinica::orderBy('nombre')->get(['id', 'nombre']);
        $porClinica = (clone $query)
            ->join('clinicas', 'clinicas.id', '=', 'solicitudes_referencia.clinica_id')
            ->select('clinicas.nombre')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('clinicas.nombre')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($r) => ['clinica' => $r->nombre, 'total' => (int) $r->total]);

        $topClinicas = $this->topConSparkline($query, 'clinicas.nombre', 'clinicas', 5, $solicitudesTotal);
        $topEspecialidadesConSerie = $this->topConSparkline($query, 'especialidad_requerida', 'especialidades', 5, $solicitudesTotal);

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
                'nuevas_mes' => $clinicasNuevasMes,
            ],
            'usuarios' => [
                'total' => $usuariosTotal,
                'activos' => $usuariosActivos,
            ],
            'solicitudes_recientes' => $solicitudesRecientes,
            'filtros' => [
                'especialidades' => $especialidades,
                'eps' => $epsList,
                'clinicas' => $clinicasFiltro,
            ],
            'tendencia' => $tendencia,
            'tendencia_anterior' => $tendenciaAnterior,
            'tendencia_clinicas' => $tendenciaClinicas,
            'tendencia_usuarios' => $tendenciaUsuarios,
            'tendencia_pendientes' => $tendenciaPendientes,
            'tendencia_pendientes_anterior' => $tendenciaPendientesAnterior,
            'tendencia_en_espera' => $tendenciaEnEspera,
            'tendencia_en_espera_anterior' => $tendenciaEnEsperaAnterior,
            'tendencia_completadas' => $tendenciaCompletadas,
            'tendencia_completadas_anterior' => $tendenciaCompletadasAnterior,
            'pendiente_mas_antigua_horas' => $pendienteMasAntiguaHoras,
            'top_especialidades' => $topEspecialidades,
            'tasa_aceptacion' => $tasaAceptacion,
            'por_dia_hora' => $porDiaHora,
            'heatmap_especialidad_mes' => $heatmapEspMes,
            'por_clinica' => $porClinica,
            'por_eps' => $porEps,
            'sla' => $sla,
            'sla_diario' => $slaDiario,
            'top_clinicas' => $topClinicas,
            'top_especialidades_serie' => $topEspecialidadesConSerie,
            'actividad' => $actividad,
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
     * Mapa de calor: especialidad × mes calendario (top 6 por volumen,
     * últimos 6 meses del histórico).
     *
     * @return array<int, array{especialidad: string, mes: string, total: int}>
     */
    private function heatmapEspecialidadMes(): array
    {
        $hasta = now();
        $desde = $hasta->copy()->subMonths(5)->startOfMonth();

        $especialidades = SolicitudReferencia::select('especialidad_requerida')
            ->selectRaw('COUNT(*) as total')
            ->whereNotNull('especialidad_requerida')
            ->groupBy('especialidad_requerida')
            ->orderByDesc('total')
            ->limit(6)
            ->pluck('especialidad_requerida');

        if ($especialidades->isEmpty()) {
            return [];
        }

        $conteos = SolicitudReferencia::whereBetween('created_at', [$desde, $hasta])
            ->whereIn('especialidad_requerida', $especialidades)
            ->selectRaw("especialidad_requerida, DATE_FORMAT(created_at, '%Y-%m') as bucket, COUNT(*) as total")
            ->groupBy('especialidad_requerida', 'bucket')
            ->get()
            ->groupBy('especialidad_requerida');

        $meses = [];
        $cursor = $desde->copy();
        $fin = $hasta->copy()->startOfMonth();
        while ($cursor->lte($fin)) {
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
     * Tendencia del gráfico principal agrupada por día/mes/año según la
     * granularidad elegida, con ventanas de 30 días, 12 meses o 5 años.
     * El período anterior es la misma ventana corrida justo atrás.
     *
     * @return array<int, array{label: string, total: int}>
     */
    private function tendenciaPeriodo($base, string $granularidad, bool $anterior = false): array
    {
        $hasta = now();
        if ($granularidad === 'mes') {
            $desde = $hasta->copy()->subMonths(11)->startOfMonth();
            $fmt = '%Y-%m';
            $paso = 'addMonth';
            $clave = 'Y-m';
        } elseif ($granularidad === 'anio') {
            $desde = $hasta->copy()->subYears(4)->startOfYear();
            $fmt = '%Y';
            $paso = 'addYear';
            $clave = 'Y';
        } else {
            $desde = $hasta->copy()->subDays(29)->startOfDay();
            $fmt = '%Y-%m-%d';
            $paso = 'addDay';
            $clave = 'Y-m-d';
        }

        if ($anterior) {
            $hasta = $desde->copy()->subDay()->endOfDay();
            $desde = match ($granularidad) {
                'mes' => $desde->copy()->subMonths(12),
                'anio' => $desde->copy()->subYears(5),
                default => $desde->copy()->subDays(30),
            };
        }

        $conteos = $base
            ->whereBetween('created_at', [$desde, $hasta])
            ->selectRaw("DATE_FORMAT(created_at, '{$fmt}') as bucket, COUNT(*) as total")
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        $resultado = [];
        $cursor = $desde->copy();
        while ($cursor->lte($hasta)) {
            $resultado[] = [
                'label' => match ($granularidad) {
                    'mes' => self::MESES[$cursor->month - 1].' '.$cursor->format('y'),
                    'anio' => $cursor->format('Y'),
                    default => $cursor->day.' '.self::MESES[$cursor->month - 1],
                },
                'total' => (int) ($conteos[$cursor->format($clave)] ?? 0),
            ];
            $cursor->{$paso}();
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
    private function tendenciaUltimosDias(string $modelClass, int $dias, ?\Closure $scope = null, int $offsetDias = 0, $base = null): array
    {
        $desde = now()->subDays($dias - 1 + $offsetDias)->startOfDay();
        $hasta = now()->subDays($offsetDias)->endOfDay();

        // $base permite partir del query ya filtrado por dimensión
        // (estado/especialidad/clínica) — la ventana de fechas siempre
        // la pone esta función.
        $query = ($base ? clone $base : $modelClass::query())->whereBetween('created_at', [$desde, $hasta]);
        if ($scope) {
            $scope($query);
        }

        $conteos = $query->selectRaw('DATE(created_at) as fecha, COUNT(*) as total')
            ->groupByRaw('DATE(created_at)')
            ->pluck('total', 'fecha');

        $resultado = [];
        for ($i = $dias - 1; $i >= 0; $i--) {
            $fecha = now()->subDays($i + $offsetDias)->format('Y-m-d');
            $resultado[] = [
                'fecha' => $fecha,
                'total' => (int) ($conteos[$fecha] ?? 0),
            ];
        }

        return $resultado;
    }

    /**
     * Top N de una dimensión (clínica o especialidad) con su conteo, el % que
     * representa del total de solicitudes y una sparkline de los últimos
     * 14 días — alimenta las tablas con mini-gráficas del dashboard.
     *
     * @return array<int, array{nombre: string, total: int, pct: int, serie: array<int, int>}>
     */
    private function topConSparkline($query, string $columna, string $tipo, int $limite, ?int $totalBase = null): array
    {
        $porDia = $this->tendenciaUltimosDias(SolicitudReferencia::class, 14);
        $dias = array_column($porDia, 'fecha');

        if ($tipo === 'clinicas') {
            $base = (clone $query)
                ->join('clinicas', 'clinicas.id', '=', 'solicitudes_referencia.clinica_id')
                ->select('clinicas.nombre as nombre')
                ->selectRaw('COUNT(*) as total')
                ->groupBy('clinicas.nombre');
        } else {
            $base = (clone $query)
                ->select("{$columna} as nombre")
                ->selectRaw('COUNT(*) as total')
                ->whereNotNull($columna)
                ->groupBy($columna);
        }

        $top = $base->orderByDesc('total')->limit($limite)->get();
        $nombres = $top->pluck('nombre');

        $conteos = SolicitudReferencia::query()
            ->leftJoin('clinicas', 'clinicas.id', '=', 'solicitudes_referencia.clinica_id')
            ->where('solicitudes_referencia.created_at', '>=', now()->subDays(13)->startOfDay())
            ->whereIn($tipo === 'clinicas' ? 'clinicas.nombre' : "solicitudes_referencia.{$columna}", $nombres)
            ->selectRaw(($tipo === 'clinicas' ? 'clinicas.nombre' : "solicitudes_referencia.{$columna}").' as nombre, DATE(solicitudes_referencia.created_at) as f, COUNT(*) as t')
            ->groupByRaw(($tipo === 'clinicas' ? 'clinicas.nombre' : "solicitudes_referencia.{$columna}").', DATE(solicitudes_referencia.created_at)')
            ->get()
            ->groupBy('nombre');

        $divisor = $totalBase ?? max(1, (int) $top->sum('total'));

        return $top->map(fn ($r) => [
            'nombre' => $r->nombre,
            'total' => (int) $r->total,
            'pct' => (int) round(((int) $r->total / $divisor) * 100),
            'serie' => array_map(function ($dia) use ($conteos, $r) {
                $fila = $conteos->get($r->nombre)?->firstWhere('f', $dia);

                return $fila ? (int) $fila->t : 0;
            }, $dias),
        ])->values()->all();
    }

    /**
     * SLA desglosado por día (últimos $dias): cuántas respuestas cayeron en
     * cada banda (≤2h, 2–4h, >4h) y el promedio en horas — alimenta las
     * barras apiladas + línea del panel "Tiempo de respuesta".
     *
     * @return array{dias: array<int, array{fecha: string, hasta2: int, de2a4: int, mas4: int, avg_horas: float}>, promedio: string|null, promedio_min: int|null, promedio_delta: int|null}
     */
    private function slaDiario(int $dias): array
    {
        $expresion = "TIMESTAMPDIFF(MINUTE, CONCAT(fecha, ' ', hora), CONCAT(fecha, ' ', hora_respuesta))";
        $desde = now()->subDays($dias - 1)->startOfDay();

        $filas = SolicitudReferencia::query()
            ->whereNotNull('hora_respuesta')
            ->where('created_at', '>=', $desde)
            ->selectRaw("DATE(created_at) as f, {$expresion} as m")
            ->get()
            ->groupBy('f');

        $resultado = [];
        for ($i = $dias - 1; $i >= 0; $i--) {
            $fecha = now()->subDays($i)->format('Y-m-d');
            $del = $filas->get($fecha, collect());
            $hasta2 = $del->filter(fn ($r) => $r->m <= 120)->count();
            $de2a4 = $del->filter(fn ($r) => $r->m > 120 && $r->m <= 240)->count();
            $mas4 = $del->count() - $hasta2 - $de2a4;
            $resultado[] = [
                'fecha' => $fecha,
                'hasta2' => $hasta2,
                'de2a4' => $de2a4,
                'mas4' => $mas4,
                'avg_horas' => $del->count() ? round($del->avg('m') / 60, 1) : 0,
            ];
        }

        // Promedio del período vs el período anterior para el delta del mockup.
        $promedioDe = function ($desdeDias, $hastaDias) use ($expresion) {
            return SolicitudReferencia::query()
                ->whereNotNull('hora_respuesta')
                ->whereBetween('created_at', [now()->subDays($desdeDias)->startOfDay(), now()->subDays($hastaDias)->endOfDay()])
                ->selectRaw("AVG({$expresion}) as m")
                ->value('m');
        };

        $promedio = $promedioDe($dias - 1, 0);
        $promedioAnt = $promedioDe(2 * $dias - 1, $dias);
        $promedioMin = $promedio !== null ? (int) round($promedio) : null;
        $delta = ($promedioMin !== null && $promedioAnt)
            ? (int) round((($promedioMin - $promedioAnt) / $promedioAnt) * 100)
            : null;

        return [
            'dias' => $resultado,
            'promedio' => $promedioMin !== null ? intdiv($promedioMin, 60).'h '.str_pad((string) ($promedioMin % 60), 2, '0', STR_PAD_LEFT).'m' : null,
            'promedio_min' => $promedioMin,
            'promedio_delta' => $delta,
        ];
    }

    /**
     * Distribución del tiempo de respuesta (creación → hora_respuesta) en
     * tres bandas: ≤2h, 2–4h y >4h. `hora` y `hora_respuesta` son columnas
     * TIME sobre la misma `fecha`, así que TIMESTAMPDIFF entre los CONCAT
     * da los minutos sin depender de created_at.
     *
     * @return array{bandas: array<int, array{etiqueta: string, total: int, pct: int}>, dentro_pct: int, total: int}
     */
    private function slaRespuesta($query): array
    {
        $expresion = "TIMESTAMPDIFF(MINUTE, CONCAT(fecha, ' ', hora), CONCAT(fecha, ' ', hora_respuesta))";

        $filas = $query->whereNotNull('hora_respuesta')
            ->selectRaw("{$expresion} as minutos")
            ->pluck('minutos');

        $total = $filas->count();
        $hasta2 = $filas->filter(fn ($m) => $m <= 120)->count();
        $de2a4 = $filas->filter(fn ($m) => $m > 120 && $m <= 240)->count();
        $mas4 = $total - $hasta2 - $de2a4;

        $pct = fn (int $n) => $total > 0 ? (int) round(($n / $total) * 100) : 0;

        return [
            'bandas' => [
                ['etiqueta' => '≤ 2 horas', 'total' => $hasta2, 'pct' => $pct($hasta2)],
                ['etiqueta' => '2 – 4 horas', 'total' => $de2a4, 'pct' => $pct($de2a4)],
                ['etiqueta' => '> 4 horas', 'total' => $mas4, 'pct' => $pct($mas4)],
            ],
            'dentro_pct' => $pct($hasta2),
            'total' => $total,
        ];
    }

    /**
     * Feed "Actividad reciente": sintetiza eventos a partir de las
     * solicitudes (recibida en created_at; gestionada en fecha+hora_respuesta)
     * y de los usuarios más nuevos. No hay tabla de eventos — derivarlo de
     * los registros existentes mantiene el feed honesto sin infraestructura
     * extra.
     *
     * @return array<int, array{tipo: string, titulo: string, detalle: string, at: string}>
     */
    private function actividadReciente($query): array
    {
        $eventos = [];

        $recientes = $query->with('clinica:id,nombre')
            ->select('id', 'clinica_id', 'primer_nombre', 'primer_apellido', 'estado', 'especialidad_requerida', 'fecha', 'hora_respuesta', 'created_at')
            ->latest()
            ->limit(12)
            ->get();

        foreach ($recientes as $s) {
            $clinica = $s->clinica?->nombre ?? 'Clínica';
            $paciente = trim("{$s->primer_nombre} {$s->primer_apellido}");

            $eventos[] = [
                'tipo' => 'recibida',
                'titulo' => 'Nueva referencia recibida',
                'detalle' => "{$clinica} · {$paciente}",
                'at' => $s->created_at?->toISOString(),
            ];

            if ($s->hora_respuesta && $s->fecha) {
                $at = Carbon::parse("{$s->fecha->toDateString()} {$s->hora_respuesta}");
                [$tipo, $titulo] = match ($s->estado) {
                    'negado' => ['rechazada', 'Referencia rechazada'],
                    'en_espera' => ['en_espera', 'Referencia en espera'],
                    default => ['aceptada', 'Referencia aceptada'],
                };
                $eventos[] = [
                    'tipo' => $tipo,
                    'titulo' => $titulo,
                    'detalle' => trim("{$clinica} - {$s->especialidad_requerida}", ' -'),
                    'at' => $at->toISOString(),
                ];
            }
        }

        foreach (User::latest()->limit(2)->get(['first_name', 'last_name', 'created_at']) as $u) {
            $eventos[] = [
                'tipo' => 'usuario',
                'titulo' => 'Nuevo usuario creado',
                'detalle' => trim("{$u->first_name} {$u->last_name}"),
                'at' => $u->created_at?->toISOString(),
            ];
        }

        usort($eventos, fn ($a, $b) => strcmp($b['at'] ?? '', $a['at'] ?? ''));

        return array_slice($eventos, 0, 8);
    }

    /**
     * Buscador global del encabezado: pacientes/solicitudes por nombre,
     * documento o código de aceptación, y clínicas por nombre o NIT.
     */
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));

        if (mb_strlen($q) < 2) {
            return $this->sendResponse(['solicitudes' => [], 'clinicas' => []], 'Búsqueda');
        }

        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';

        $solicitudes = SolicitudReferencia::query()
            ->with('clinica:id,nombre')
            ->where(function ($w) use ($like) {
                $w->where('numero_documento', 'like', $like)
                    ->orWhere('codigo_aceptacion', 'like', $like)
                    ->orWhereRaw("CONCAT_WS(' ', primer_nombre, segundo_nombre, primer_apellido, segundo_apellido) LIKE ?", [$like]);
            })
            ->latest()
            ->limit(6)
            ->get(['id', 'clinica_id', 'primer_nombre', 'segundo_nombre', 'primer_apellido', 'segundo_apellido', 'tipo_documento', 'numero_documento', 'codigo_aceptacion', 'estado', 'created_at'])
            ->map(fn ($s) => [
                'id' => $s->id,
                'paciente' => trim("{$s->primer_nombre} {$s->segundo_nombre} {$s->primer_apellido} {$s->segundo_apellido}"),
                'documento' => trim("{$s->tipo_documento} {$s->numero_documento}"),
                'codigo' => $s->codigo_aceptacion,
                'estado' => $s->estado,
                'clinica' => $s->clinica?->nombre,
            ]);

        $clinicas = Clinica::query()
            ->where('nombre', 'like', $like)
            ->orWhere('nit', 'like', $like)
            ->orWhere('razon_social', 'like', $like)
            ->orderBy('nombre')
            ->limit(5)
            ->get(['id', 'nombre', 'nit', 'ciudad', 'estado'])
            ->map(fn ($c) => [
                'id' => $c->id,
                'nombre' => $c->nombre,
                'nit' => $c->nit,
                'ciudad' => $c->ciudad,
                'estado' => $c->estado,
            ]);

        return $this->sendResponse([
            'solicitudes' => $solicitudes,
            'clinicas' => $clinicas,
        ], 'Resultados de búsqueda');
    }
}
