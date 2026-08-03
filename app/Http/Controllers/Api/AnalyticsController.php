<?php

namespace App\Http\Controllers\Api;

use App\Models\Clinica;
use App\Models\SolicitudReferencia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends BaseController
{
    private const DIAS_LABEL = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

    /**
     * MySQL DAYOFWEEK(): 1=Domingo ... 7=Sábado. Reordenamos a Lun..Dom.
     *
     * @var array<int, int>
     */
    private const DAYOFWEEK_ORDEN = [2, 3, 4, 5, 6, 7, 1];

    public function stats(Request $request): JsonResponse
    {
        return $this->sendResponse([
            'heatmap_dia_hora' => $this->heatmapDiaHora(),
            'heatmap_clinica_especialidad' => $this->heatmapClinicaEspecialidad(),
            'tendencia_mensual' => $this->tendenciaMensual(6),
            'top_eps' => $this->topEps(),
            'kpis' => $this->kpis(),
        ], 'Estadísticas de analítica avanzada');
    }

    /**
     * @return array<int, array{name: string, data: array<int, array{x: string, y: int}>}>
     */
    private function heatmapDiaHora(): array
    {
        $rows = SolicitudReferencia::selectRaw('DAYOFWEEK(created_at) as dow, HOUR(created_at) as hora, COUNT(*) as total')
            ->groupByRaw('DAYOFWEEK(created_at), HOUR(created_at)')
            ->get();

        $matriz = [];
        foreach (self::DAYOFWEEK_ORDEN as $dow) {
            $matriz[$dow] = array_fill(0, 24, 0);
        }

        foreach ($rows as $r) {
            $matriz[(int) $r->dow][(int) $r->hora] = (int) $r->total;
        }

        $series = [];
        foreach (self::DAYOFWEEK_ORDEN as $i => $dow) {
            $data = [];
            for ($h = 0; $h < 24; $h++) {
                $data[] = [
                    'x' => str_pad((string) $h, 2, '0', STR_PAD_LEFT).'h',
                    'y' => $matriz[$dow][$h],
                ];
            }
            $series[] = [
                'name' => self::DIAS_LABEL[$i],
                'data' => $data,
            ];
        }

        return $series;
    }

    /**
     * @return array{clinicas: array<int, string>, especialidades: array<int, string>, series: array<int, array{name: string, data: array<int, array{x: string, y: int}>}>}
     */
    private function heatmapClinicaEspecialidad(): array
    {
        $topClinicaIds = SolicitudReferencia::selectRaw('clinica_id, COUNT(*) as total')
            ->whereNotNull('clinica_id')
            ->groupBy('clinica_id')
            ->orderByDesc('total')
            ->limit(6)
            ->pluck('clinica_id');

        $topEspecialidades = SolicitudReferencia::selectRaw('especialidad_requerida, COUNT(*) as total')
            ->whereNotNull('especialidad_requerida')
            ->groupBy('especialidad_requerida')
            ->orderByDesc('total')
            ->limit(8)
            ->pluck('especialidad_requerida')
            ->values();

        $clinicaNombres = Clinica::whereIn('id', $topClinicaIds)->pluck('nombre', 'id');

        $rows = SolicitudReferencia::selectRaw('clinica_id, especialidad_requerida, COUNT(*) as total')
            ->whereIn('clinica_id', $topClinicaIds)
            ->whereIn('especialidad_requerida', $topEspecialidades)
            ->groupBy('clinica_id', 'especialidad_requerida')
            ->get();

        $matriz = [];
        foreach ($topClinicaIds as $cid) {
            $matriz[$cid] = array_fill_keys($topEspecialidades->all(), 0);
        }
        foreach ($rows as $r) {
            $matriz[$r->clinica_id][$r->especialidad_requerida] = (int) $r->total;
        }

        $series = [];
        foreach ($topClinicaIds as $cid) {
            $nombre = $clinicaNombres[$cid] ?? "Clínica #{$cid}";
            $nombreCorto = mb_strlen($nombre) > 22 ? mb_substr($nombre, 0, 22).'...' : $nombre;
            $data = [];
            foreach ($topEspecialidades as $esp) {
                $espCorta = mb_strlen($esp) > 16 ? mb_substr($esp, 0, 16).'...' : $esp;
                $data[] = [
                    'x' => $espCorta,
                    'y' => $matriz[$cid][$esp],
                ];
            }
            $series[] = [
                'name' => $nombreCorto,
                'data' => $data,
            ];
        }

        return [
            'clinicas' => $topClinicaIds->map(fn ($cid) => $clinicaNombres[$cid] ?? "Clínica #{$cid}")->values()->all(),
            'especialidades' => $topEspecialidades->all(),
            'series' => $series,
        ];
    }

    /**
     * @return array<int, array{mes: string, total: int}>
     */
    private function tendenciaMensual(int $meses): array
    {
        $desde = now()->subMonths($meses - 1)->startOfMonth();

        $conteos = SolicitudReferencia::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as mes, COUNT(*) as total")
            ->where('created_at', '>=', $desde)
            ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m')")
            ->pluck('total', 'mes');

        $resultado = [];
        for ($i = $meses - 1; $i >= 0; $i--) {
            $fecha = now()->subMonths($i);
            $key = $fecha->format('Y-m');
            $resultado[] = [
                'mes' => $key,
                'label' => ucfirst($fecha->translatedFormat('M Y')),
                'total' => (int) ($conteos[$key] ?? 0),
            ];
        }

        return $resultado;
    }

    /**
     * @return array<int, array{eps: string, total: int}>
     */
    private function topEps(): array
    {
        return SolicitudReferencia::selectRaw('eps, COUNT(*) as total')
            ->whereNotNull('eps')
            ->groupBy('eps')
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(fn ($r) => [
                'eps' => $r->eps,
                'total' => (int) $r->total,
            ])
            ->all();
    }

    /**
     * @return array{promedio_diario: float, dia_pico: string, hora_pico: string, clinica_mas_activa: string}
     */
    private function kpis(): array
    {
        $total = SolicitudReferencia::count();
        $primera = SolicitudReferencia::oldest('created_at')->value('created_at');
        $dias = $primera ? max(1, now()->diffInDays($primera)) : 1;
        $promedioDiario = round($total / $dias, 1);

        $porDia = SolicitudReferencia::selectRaw('DAYOFWEEK(created_at) as dow, COUNT(*) as total')
            ->groupByRaw('DAYOFWEEK(created_at)')
            ->orderByDesc('total')
            ->first();
        $diaPico = '—';
        if ($porDia) {
            $idx = array_search((int) $porDia->dow, self::DAYOFWEEK_ORDEN, true);
            $diaPico = $idx !== false ? self::DIAS_LABEL[$idx] : '—';
        }

        $porHora = SolicitudReferencia::selectRaw('HOUR(created_at) as hora, COUNT(*) as total')
            ->groupByRaw('HOUR(created_at)')
            ->orderByDesc('total')
            ->first();
        $horaPico = $porHora ? str_pad((string) $porHora->hora, 2, '0', STR_PAD_LEFT).':00' : '—';

        $clinicaTop = SolicitudReferencia::selectRaw('clinica_id, COUNT(*) as total')
            ->whereNotNull('clinica_id')
            ->groupBy('clinica_id')
            ->orderByDesc('total')
            ->first();
        $clinicaNombre = '—';
        if ($clinicaTop) {
            $clinicaNombre = Clinica::find($clinicaTop->clinica_id)?->nombre ?? '—';
        }

        return [
            'promedio_diario' => $promedioDiario,
            'dia_pico' => $diaPico,
            'hora_pico' => $horaPico,
            'clinica_mas_activa' => $clinicaNombre,
        ];
    }
}
