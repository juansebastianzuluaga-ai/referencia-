<template>
  <div class="h-full flex flex-col gap-2.5 p-2 sm:p-3 overflow-y-auto rp-page">

    <!-- ── Encabezado ── -->
    <div class="flex items-start justify-between gap-3 flex-wrap shrink-0">
      <div>
        <h1 class="rp-title">Reportes</h1>
        <p class="rp-subtitle">Visualiza y analiza las solicitudes de referencia en tiempo real</p>
      </div>
      <div class="flex items-center gap-2 flex-wrap">
        <el-date-picker
          v-model="rangoFechas"
          type="daterange"
          unlink-panels
          range-separator="–"
          start-placeholder="Desde"
          end-placeholder="Hasta"
          size="small"
          format="DD MMM YYYY"
          value-format="YYYY-MM-DD"
          :clearable="false"
          class="rp-date-picker"
          @change="cargarStats"
        />
        <el-select v-model="filtroEstado" size="small" style="width:150px" @change="cargarStats">
          <el-option value="todas" label="Todos los estados" />
          <el-option value="pendiente" label="Pendientes" />
          <el-option value="en_espera" label="En espera" />
          <el-option value="completado" label="Completadas" />
          <el-option value="negado" label="Negadas" />
        </el-select>
        <el-select v-model="filtroEspecialidad" size="small" style="width:170px" clearable placeholder="Especialidad" @change="cargarStats">
          <el-option v-for="esp in stats.filtros.especialidades" :key="esp" :value="esp" :label="esp" />
        </el-select>
        <button v-if="filtroEspecialidad" class="rp-clear-btn" @click="filtroEspecialidad = ''; cargarStats()">
          <component :is="XCircleIcon" class="w-3.5 h-3.5" />
          Quitar filtro
        </button>
        <button class="rp-export-btn" :disabled="exportando" @click="exportar">
          <component :is="DownloadIcon" class="w-3.5 h-3.5" />
          {{ exportando ? 'Exportando…' : 'Exportar CSV' }}
        </button>
      </div>
    </div>

    <div class="flex flex-col gap-2.5" :class="{ 'rp-loading': cargando }">

      <!-- ── Stat cards ── -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 shrink-0">
        <div
          v-for="(card, i) in statCards" :key="card.label"
          class="rp-stat anim-slide-up"
          :style="{ animationDelay: (i * 0.06) + 's' }"
        >
          <div class="rp-stat-icon" :style="{ background: card.color }">
            <component :is="card.icon" class="w-4.5 h-4.5 text-white" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="rp-stat-label">{{ card.label }}</p>
            <p class="rp-stat-value">{{ card.value }}</p>
            <span class="rp-stat-delta" :class="card.up ? 'rp-delta-up' : 'rp-delta-down'">
              <component :is="card.up ? TrendingUpIcon : TrendingDownIcon" class="w-3 h-3" />
              {{ card.delta }} vs periodo anterior
            </span>
          </div>
          <Sparkline v-if="card.sparkline" :valores="card.sparkline" :color="card.color" class="rp-stat-sparkline" />
          <div v-else class="rp-stat-ring">
            <svg viewBox="0 0 44 44" width="44" height="44">
              <circle cx="22" cy="22" r="18" fill="none" :stroke="isDark ? '#1e293b' : '#eef2ff'" stroke-width="4" />
              <circle
                cx="22" cy="22" r="18" fill="none" :stroke="card.color" stroke-width="4"
                stroke-linecap="round" :stroke-dasharray="113.1"
                :stroke-dashoffset="113.1 - (113.1 * (card.ringPct ?? 0)) / 100"
                transform="rotate(-90 22 22)"
              />
            </svg>
          </div>
        </div>
      </div>

      <!-- ── Tendencia + Distribución por estado ── -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-2.5 shrink-0">
        <div class="rp-card lg:col-span-2 anim-slide-up" style="animation-delay:.1s">
          <div class="flex items-center justify-between flex-wrap gap-2 mb-1">
            <div class="flex items-center gap-2">
              <div class="rp-card-icon" style="background:linear-gradient(135deg,#eef2ff,#e0e7ff); color:var(--rf-primary);">
                <component :is="TrendingUpIcon" class="w-3.5 h-3.5" />
              </div>
              <div>
                <p class="rp-card-title">Tendencia de solicitudes</p>
                <p class="rp-card-sub">Volumen de remisiones</p>
              </div>
            </div>
            <div class="rp-period-group">
              <button class="rp-period-btn" :class="{ 'rp-period-btn-active': granularidad === 'dia' }" @click="cambiarGranularidad('dia')">Día</button>
              <button class="rp-period-btn" :class="{ 'rp-period-btn-active': granularidad === 'mes' }" @click="cambiarGranularidad('mes')">Mes</button>
              <button class="rp-period-btn" :class="{ 'rp-period-btn-active': granularidad === 'anio' }" @click="cambiarGranularidad('anio')">Año</button>
            </div>
          </div>
          <div class="flex items-center gap-4 mb-1 pl-1">
            <span class="rp-legend-item"><span class="rp-legend-line rp-legend-line-solid"></span>Periodo actual</span>
            <span class="rp-legend-item"><span class="rp-legend-line rp-legend-line-dashed"></span>Periodo anterior</span>
          </div>
          <div class="flex-1" style="min-height:220px;">
            <apexchart :key="chartKey" type="area" height="220" :options="tendenciaOptions" :series="tendenciaSeries" />
          </div>
        </div>

        <div class="rp-card anim-slide-up" style="animation-delay:.16s">
          <div class="flex items-center gap-2 mb-2">
            <div class="rp-card-icon" style="background:linear-gradient(135deg,#dcfce7,#bbf7d0); color:#15966a;">
              <component :is="PieChartIcon" class="w-3.5 h-3.5" />
            </div>
            <div>
              <p class="rp-card-title">Distribución por estado</p>
              <p class="rp-card-sub">Resumen general</p>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <div class="rp-donut-wrap">
              <apexchart :key="chartKey" type="donut" height="150" width="150" :options="estadoDonutOptions" :series="estadoDonutSeries" />
              <div class="rp-donut-center">
                <span class="rp-donut-center-num">{{ estadoTotal }}</span>
                <span class="rp-donut-center-label">Total</span>
              </div>
            </div>
            <div class="flex-1 min-w-0 flex flex-col gap-1.5">
              <button
                v-for="item in distribucionEstado" :key="item.label"
                type="button" class="rp-legend-row rp-legend-row-clickable"
                :class="{ 'rp-legend-row-active': filtroEstado === item.estado }"
                @click="filtrarPorEstado(item.estado)"
              >
                <span class="rp-legend-dot" :style="{ background: item.color }"></span>
                <span class="rp-legend-label">{{ item.label }}</span>
                <span class="rp-legend-pct">{{ item.pct }}%</span>
                <span class="rp-legend-count">{{ item.total }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Especialidad + Mapa de calor + Tiempo de respuesta ── -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-2.5 shrink-0">
        <div class="rp-card anim-slide-up" style="animation-delay:.2s">
          <div class="flex items-center gap-2 mb-2">
            <div class="rp-card-icon" style="background:linear-gradient(135deg,#ede9fe,#ddd6fe); color:#7048e8;">
              <component :is="StethoscopeIcon" class="w-3.5 h-3.5" />
            </div>
            <div>
              <p class="rp-card-title">Solicitudes por especialidad</p>
              <p class="rp-card-sub">Distribución por área</p>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <div class="rp-donut-wrap">
              <apexchart :key="chartKey" type="donut" height="140" width="140" :options="especialidadDonutOptions" :series="especialidadDonutSeries" />
              <div class="rp-donut-center">
                <span class="rp-donut-center-num">{{ estadoTotal }}</span>
                <span class="rp-donut-center-label">Total</span>
              </div>
            </div>
            <div class="flex-1 min-w-0 flex flex-col gap-1.5">
              <button
                v-for="item in distribucionEspecialidad" :key="item.label"
                type="button" class="rp-legend-row"
                :class="{ 'rp-legend-row-clickable': item.label !== 'Otras especialidades', 'rp-legend-row-active': filtroEspecialidad === item.label }"
                :disabled="item.label === 'Otras especialidades'"
                @click="filtrarPorEspecialidad(item.label)"
              >
                <span class="rp-legend-dot" :style="{ background: item.color }"></span>
                <span class="rp-legend-label">{{ item.label }}</span>
                <span class="rp-legend-pct">{{ item.pct }}%</span>
                <span class="rp-legend-count">{{ item.total }}</span>
              </button>
            </div>
          </div>
        </div>

        <div class="rp-card anim-slide-up" style="animation-delay:.24s">
          <div class="flex items-center gap-2 mb-2">
            <div class="rp-card-icon" style="background:linear-gradient(135deg,#fce7f3,#fbcfe8); color:#be185d;">
              <component :is="GridIcon" class="w-3.5 h-3.5" />
            </div>
            <div>
              <p class="rp-card-title">Mapa de calor</p>
              <p class="rp-card-sub">Solicitudes por área y mes</p>
            </div>
          </div>
          <div v-if="heatmapSeries.length" class="w-full">
            <apexchart :key="chartKey" type="heatmap" height="200" :options="heatmapOptions" :series="heatmapSeries" />
          </div>
          <div v-else class="rp-empty">
            <component :is="GridIcon" class="w-5 h-5 rp-empty-icon" />
            Sin datos suficientes en el rango seleccionado
          </div>
        </div>

        <div class="rp-card anim-slide-up" style="animation-delay:.28s">
          <div class="flex items-center gap-2 mb-2">
            <div class="rp-card-icon" style="background:linear-gradient(135deg,#e0f2fe,#bae6fd); color:#0369a1;">
              <component :is="TimerIcon" class="w-3.5 h-3.5" />
            </div>
            <div>
              <p class="rp-card-title">Tiempo promedio de respuesta</p>
              <p class="rp-card-sub">Por estado</p>
            </div>
          </div>
          <div class="flex flex-col gap-1.5">
            <div v-for="item in tiempoRespuesta" :key="item.label" class="rp-tr-row">
              <div class="rp-tr-icon" :style="{ background: item.tint, color: item.color }">
                <component :is="item.icon" class="w-3.5 h-3.5" />
              </div>
              <span class="rp-tr-label">{{ item.label }}</span>
              <span class="rp-tr-dias">{{ item.dias }} días</span>
              <span class="rp-tr-delta" :class="item.up ? 'rp-delta-up' : 'rp-delta-down'">
                <component :is="item.up ? TrendingUpIcon : TrendingDownIcon" class="w-3 h-3" />
                {{ item.deltaLabel }} vs anterior
              </span>
            </div>
          </div>
        </div>
      </div>

    </div>

  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useLayoutStore } from '@/stores/layout';
import http from '@/plugins/axios';
import notify from '@/plugins/toast';
import Sparkline from '@/components/ui/Sparkline.vue';
import {
  ClipboardList as ClipboardListIcon,
  Clock3 as ClockIcon,
  CheckCircle2 as CheckCircleIcon,
  Target as TargetIcon,
  TrendingUp as TrendingUpIcon,
  TrendingDown as TrendingDownIcon,
  PieChart as PieChartIcon,
  Stethoscope as StethoscopeIcon,
  Grid3x3 as GridIcon,
  Timer as TimerIcon,
  Download as DownloadIcon,
  Hourglass as HourglassIcon,
  XCircle as XCircleIcon,
} from '@lucide/vue';

const layout = useLayoutStore();
const isDark = computed(() => layout.isDarkMode);

type Granularidad = 'dia' | 'mes' | 'anio';

interface Tarjeta { valor: number; delta: number }
interface TendenciaPunto { label: string; total: number }
interface DistribucionEstadoApi { estado: string; label: string; total: number; pct: number; color: string }
interface DistribucionEspecialidadApi { label: string; total: number; pct: number }
interface HeatmapCelda { especialidad: string; mes: string; total: number }
interface TiempoRespuestaApi { estado: string; label: string; dias: number; delta: number }

const stats = ref({
  periodo: { granularidad: 'dia' as Granularidad, desde: '', hasta: '' },
  tarjetas: {
    total: { valor: 0, delta: 0 } as Tarjeta,
    pendientes: { valor: 0, delta: 0 } as Tarjeta,
    aceptadas: { valor: 0, delta: 0 } as Tarjeta,
    tasa_aceptacion: { valor: 0, delta: 0 } as Tarjeta,
  },
  tendencia: { actual: [] as TendenciaPunto[], anterior: [] as TendenciaPunto[] },
  distribucion_estado: [] as DistribucionEstadoApi[],
  distribucion_especialidad: [] as DistribucionEspecialidadApi[],
  heatmap: [] as HeatmapCelda[],
  tiempo_respuesta: [] as TiempoRespuestaApi[],
  filtros: { especialidades: [] as string[] },
});

const cargando = ref(true);
const exportando = ref(false);
const granularidad = ref<Granularidad>('dia');
const filtroEstado = ref('todas');
const filtroEspecialidad = ref('');

function hoyStr(): string {
  return new Date().toISOString().slice(0, 10);
}
function desdeDefault(g: Granularidad): string {
  const d = new Date();
  if (g === 'dia') d.setDate(d.getDate() - 29);
  else if (g === 'mes') d.setMonth(d.getMonth() - 11);
  else d.setFullYear(d.getFullYear() - 4);
  return d.toISOString().slice(0, 10);
}

const rangoFechas = ref<[string, string]>([desdeDefault('dia'), hoyStr()]);

/**
 * Fuerza el remonte de los gráficos ApexCharts cuando llegan datos de un
 * periodo distinto. Se basa en el periodo que devuelve la API (no en los
 * filtros de entrada) a propósito: si se basara en los filtros, la key
 * cambiaría apenas se hace clic (antes de que la petición resuelva), el
 * componente remontaría con los datos VIEJOS todavía en `stats`, y la
 * actualización posterior a los datos nuevos ocurriría in-place — que es
 * justo el caso en que ApexCharts deja etiquetas del eje anteriores sin
 * limpiar. Al esperar al eco del backend, el remonte siempre nace ya con
 * las categorías finales correctas.
 */
const chartKey = computed(() => `${stats.value.periodo.granularidad}|${stats.value.periodo.desde}|${stats.value.periodo.hasta}`);

function cambiarGranularidad(g: Granularidad) {
  granularidad.value = g;
  rangoFechas.value = [desdeDefault(g), hoyStr()];
  cargarStats();
}

/** Drill-down: clic en una porción del donut de estados filtra todo el reporte por ese estado (o lo quita si ya estaba activo). */
function filtrarPorEstado(estado: string) {
  filtroEstado.value = filtroEstado.value === estado ? 'todas' : estado;
  cargarStats();
}

/** Drill-down: clic en una especialidad (donut o celda del heatmap) filtra todo el reporte por esa especialidad. */
function filtrarPorEspecialidad(especialidad: string) {
  if (especialidad === 'Otras especialidades') return;
  filtroEspecialidad.value = filtroEspecialidad.value === especialidad ? '' : especialidad;
  cargarStats();
}

async function cargarStats() {
  try {
    cargando.value = true;
    const params: Record<string, string> = { granularidad: granularidad.value };
    if (rangoFechas.value?.[0]) params.desde = rangoFechas.value[0];
    if (rangoFechas.value?.[1]) params.hasta = rangoFechas.value[1];
    if (filtroEstado.value !== 'todas') params.estado = filtroEstado.value;
    if (filtroEspecialidad.value) params.especialidad = filtroEspecialidad.value;
    const { data } = await http.get('/api/reportes/stats', { params });
    stats.value = data.data;
  } catch {
    notify.error('No se pudieron cargar los reportes');
  } finally {
    cargando.value = false;
  }
}

async function exportar() {
  try {
    exportando.value = true;
    const params: Record<string, string> = {};
    if (rangoFechas.value?.[0]) params.desde = rangoFechas.value[0];
    if (rangoFechas.value?.[1]) params.hasta = rangoFechas.value[1];
    if (filtroEstado.value !== 'todas') params.estado = filtroEstado.value;
    if (filtroEspecialidad.value) params.especialidad = filtroEspecialidad.value;
    const { data } = await http.get('/api/reportes/exportar', { params });
    const filas = data.data.filas as Array<Record<string, unknown>>;
    if (!filas.length) {
      notify.info('No hay solicitudes en el rango seleccionado');
      return;
    }
    const headers = ['ID', 'Código aceptación', 'Paciente', 'Tipo doc.', 'Documento', 'EPS', 'Especialidad', 'Clínica', 'Estado', 'Fecha', 'Hora'];
    const csv = [
      headers.join('\t'),
      ...filas.map(f => [
        f.id, f.codigo_aceptacion ?? '', f.paciente, f.tipo_documento, f.numero_documento,
        f.eps, f.especialidad, f.clinica ?? '', f.estado, f.fecha, f.hora,
      ].map(v => `"${String(v ?? '').replace(/"/g, '""')}"`).join('\t')),
    ].join('\n');
    const blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `reportes_${rangoFechas.value[0]}_a_${rangoFechas.value[1]}.csv`;
    a.click();
    URL.revokeObjectURL(url);
    notify.success(`Exportadas ${filas.length} solicitudes`);
  } catch {
    notify.error('No se pudo exportar');
  } finally {
    exportando.value = false;
  }
}

onMounted(cargarStats);

// ── Stat cards ──────────────────────────────────────────────────────────
function formatDelta(delta: number, sufijo = '%'): string {
  return `${delta >= 0 ? '+' : ''}${delta}${sufijo}`;
}

const statCards = computed(() => {
  const t = stats.value.tarjetas;
  const total = Math.max(1, t.total.valor);
  return [
    {
      label: 'Total solicitudes',
      value: String(t.total.valor),
      delta: formatDelta(t.total.delta),
      up: t.total.delta >= 0,
      icon: ClipboardListIcon,
      color: 'var(--rf-primary)',
      sparkline: stats.value.tendencia.actual.map(x => x.total),
    },
    {
      label: 'Pendientes',
      value: String(t.pendientes.valor),
      delta: formatDelta(t.pendientes.delta),
      up: t.pendientes.delta >= 0,
      icon: ClockIcon,
      color: '#d97706',
      sparkline: null,
      ringPct: Math.round((t.pendientes.valor / total) * 100),
    },
    {
      label: 'Aceptadas',
      value: String(t.aceptadas.valor),
      delta: formatDelta(t.aceptadas.delta),
      up: t.aceptadas.delta >= 0,
      icon: CheckCircleIcon,
      color: '#16a34a',
      sparkline: null,
      ringPct: Math.round((t.aceptadas.valor / total) * 100),
    },
    {
      label: 'Tasa de aprobación',
      value: `${t.tasa_aceptacion.valor}%`,
      delta: formatDelta(t.tasa_aceptacion.delta, ' pts'),
      up: t.tasa_aceptacion.delta >= 0,
      icon: TargetIcon,
      color: '#0ea5e9',
      sparkline: null,
      ringPct: t.tasa_aceptacion.valor,
    },
  ];
});

// ── Tendencia de solicitudes (periodo actual vs anterior) ──────────────
const tendenciaSeries = computed(() => [
  { name: 'Periodo actual', data: stats.value.tendencia.actual.map(t => t.total) },
  { name: 'Periodo anterior', data: stats.value.tendencia.anterior.map(t => t.total) },
]);

const tendenciaOptions = computed(() => ({
  chart: { type: 'area' as const, fontFamily: 'inherit', toolbar: { show: false }, zoom: { enabled: false }, animations: { enabled: true, speed: 500 } },
  colors: ['var(--rf-primary)', isDark.value ? '#475569' : '#cbd5e1'],
  stroke: { curve: 'smooth' as const, width: [3, 2], dashArray: [0, 6] },
  fill: {
    type: ['gradient', 'solid'],
    gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.02, stops: [0, 90, 100] },
    opacity: [1, 0],
  },
  markers: { size: 0, hover: { size: 6 }, strokeWidth: 2, strokeColors: '#fff' },
  xaxis: {
    categories: stats.value.tendencia.actual.map(t => t.label),
    labels: { style: { fontSize: '10px', fontWeight: 600, colors: isDark.value ? '#64748b' : '#94a3b8' } },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: { labels: { style: { fontSize: '10px', colors: isDark.value ? '#64748b' : '#94a3b8' } } },
  grid: { borderColor: isDark.value ? '#1e293b' : '#f1f5f9', strokeDashArray: 4, xaxis: { lines: { show: false } } },
  dataLabels: { enabled: false },
  legend: { show: false },
  tooltip: {
    shared: true,
    style: { fontSize: '12px', fontFamily: 'inherit' },
    theme: isDark.value ? 'dark' : 'light',
    y: { formatter: (val: number) => `${val} solicitudes` },
  },
}));

// ── Distribución por estado ─────────────────────────────────────────────
const distribucionEstado = computed(() => stats.value.distribucion_estado);
const estadoTotal = computed(() => stats.value.tarjetas.total.valor);

const estadoDonutSeries = computed(() => distribucionEstado.value.map(d => d.total));
const estadoDonutOptions = computed(() => ({
  chart: { type: 'donut' as const, fontFamily: 'inherit', animations: { enabled: false } },
  labels: distribucionEstado.value.map(d => d.label),
  colors: distribucionEstado.value.map(d => d.color),
  legend: { show: false },
  dataLabels: { enabled: false },
  stroke: { width: 2, colors: [isDark.value ? '#161b28' : '#fff'] },
  plotOptions: { pie: { offsetY: 15, donut: { size: '72%', labels: { show: false } } } },
  tooltip: {
    theme: isDark.value ? 'dark' : 'light',
    y: { formatter: (val: number) => `${val} solicitudes` },
    fixed: { enabled: true, position: 'topRight' },
  },
}));

// ── Solicitudes por especialidad ────────────────────────────────────────
const ESPECIALIDAD_COLORES = ['#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b', '#ef4444', '#10b981'];
const distribucionEspecialidad = computed(() =>
  stats.value.distribucion_especialidad.map((d, i) => ({
    ...d,
    color: d.label === 'Otras especialidades' ? '#94a3b8' : ESPECIALIDAD_COLORES[i % ESPECIALIDAD_COLORES.length],
  }))
);

const especialidadDonutSeries = computed(() => distribucionEspecialidad.value.map(d => d.total));
const especialidadDonutOptions = computed(() => ({
  chart: { type: 'donut' as const, fontFamily: 'inherit', animations: { enabled: false } },
  labels: distribucionEspecialidad.value.map(d => d.label),
  colors: distribucionEspecialidad.value.map(d => d.color),
  legend: { show: false },
  dataLabels: { enabled: false },
  stroke: { width: 2, colors: [isDark.value ? '#161b28' : '#fff'] },
  plotOptions: { pie: { offsetY: 14, donut: { size: '68%', labels: { show: false } } } },
  tooltip: {
    theme: isDark.value ? 'dark' : 'light',
    y: { formatter: (val: number) => `${val} solicitudes` },
    fixed: { enabled: true, position: 'topRight' },
  },
}));

// ── Mapa de calor: especialidad × mes ────────────────────────────────────
const heatmapSeries = computed(() => {
  const especialidades: string[] = [];
  for (const c of stats.value.heatmap) {
    if (!especialidades.includes(c.especialidad)) especialidades.push(c.especialidad);
  }
  return especialidades.map(esp => ({
    name: esp,
    data: stats.value.heatmap.filter(c => c.especialidad === esp).map(c => ({ x: c.mes, y: c.total })),
  })).reverse();
});

const heatmapOptions = computed(() => ({
  chart: {
    type: 'heatmap' as const, fontFamily: 'inherit', toolbar: { show: false },
    events: {
      dataPointSelection: (_e: unknown, _chart: unknown, opts: { seriesIndex: number }) => {
        const especialidad = heatmapSeries.value[opts.seriesIndex]?.name;
        if (especialidad) filtrarPorEspecialidad(especialidad);
      },
    },
  },
  dataLabels: { enabled: false },
  legend: { show: false },
  colors: ['#7048e8'],
  xaxis: {
    labels: { style: { fontSize: '9px', colors: isDark.value ? '#64748b' : '#94a3b8' } },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: { labels: { style: { fontSize: '9px', colors: isDark.value ? '#94a3b8' : '#475569' } } },
  grid: { padding: { left: 4, right: 4 } },
  plotOptions: { heatmap: { radius: 3 } },
  tooltip: { theme: isDark.value ? 'dark' : 'light', y: { formatter: (val: number) => `${val} solicitud${val === 1 ? '' : 'es'}` } },
}));

// ── Tiempo promedio de respuesta ────────────────────────────────────────
const TIEMPO_RESPUESTA_META: Record<string, { icon: typeof CheckCircleIcon; color: string; tint: string }> = {
  completado: { icon: CheckCircleIcon, color: '#16a34a', tint: '#dcfce7' },
  en_espera: { icon: HourglassIcon, color: '#3b82f6', tint: '#dbeafe' },
  negado: { icon: XCircleIcon, color: '#dc2626', tint: '#fee2e2' },
};

const tiempoRespuesta = computed(() => stats.value.tiempo_respuesta.map(t => ({
  label: t.label,
  dias: t.dias.toFixed(1),
  deltaLabel: `${t.delta >= 0 ? '+' : ''}${t.delta}`,
  up: t.delta >= 0,
  ...(TIEMPO_RESPUESTA_META[t.estado] ?? { icon: ClockIcon, color: '#64748b', tint: '#f1f5f9' }),
})));

</script>

<style scoped>
.rp-title { font-size: 20px; font-weight: 800; color: #1e293b; letter-spacing: -0.02em; }
.dark .rp-title { color: #e2e8f0; }
.rp-subtitle { font-size: 12px; color: #94a3b8; margin-top: 2px; }
.dark .rp-subtitle { color: #64748b; }

.rp-export-btn {
  display: flex; align-items: center; gap: 6px;
  font-size: 12px; font-weight: 600; color: #fff;
  background: linear-gradient(135deg, var(--rf-primary), var(--rf-primary-2));
  border: none; border-radius: 10px;
  padding: 7px 12px; cursor: pointer; transition: all .15s ease;
}
.rp-export-btn:hover { opacity: .92; }
.rp-export-btn:disabled { opacity: .6; cursor: not-allowed; }

.rp-clear-btn {
  display: flex; align-items: center; gap: 6px;
  font-size: 12px; font-weight: 600; color: #b91c1c;
  background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px;
  padding: 7px 12px; cursor: pointer; transition: all .15s ease;
}
.rp-clear-btn:hover { background: #fee2e2; }
.dark .rp-clear-btn { background: rgba(220,38,38,0.12); border-color: rgba(220,38,38,0.3); color: #fca5a5; }

.rp-loading { opacity: .55; pointer-events: none; transition: opacity .2s ease; }

/* ── Stat cards ── */
.rp-stat {
  display: flex; align-items: center; gap: 10px;
  background: #fff; border: 1px solid rgba(15,23,42,0.05); border-radius: 16px;
  padding: 12px; position: relative; overflow: hidden;
  box-shadow: 0 6px 18px rgba(15,23,42,0.05);
  transition: transform .25s cubic-bezier(.22,1,.36,1), box-shadow .25s ease, border-color .25s ease;
}
.rp-stat:hover {
  transform: translateY(-5px);
  box-shadow: 0 16px 30px rgba(13,45,107,0.16);
  border-color: rgba(13,45,107,0.15);
}
.dark .rp-stat { background: #161b28; border-color: rgba(255,255,255,0.06); box-shadow: 0 6px 18px rgba(0,0,0,0.2); }
.dark .rp-stat:hover { box-shadow: 0 16px 30px rgba(0,0,0,0.4); border-color: rgba(255,255,255,0.14); }
.rp-stat-icon {
  width: 38px; height: 38px; border-radius: 11px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  transition: transform .3s cubic-bezier(.22,1,.36,1);
}
.rp-stat:hover .rp-stat-icon { transform: scale(1.12) rotate(-6deg); }
.rp-stat:hover .rp-stat-sparkline { opacity: 1; }
.rp-stat:hover .rp-stat-ring circle:last-child { filter: drop-shadow(0 0 4px currentColor); }
.rp-stat-label { font-size: 10.5px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .03em; }
.dark .rp-stat-label { color: #64748b; }
.rp-stat-value { font-size: 20px; font-weight: 800; color: #1e293b; line-height: 1.2; }
.dark .rp-stat-value { color: #e2e8f0; }
.rp-stat-delta { display: inline-flex; align-items: center; gap: 3px; font-size: 10px; font-weight: 700; margin-top: 1px; }
.rp-delta-up { color: #16a34a; }
.rp-delta-down { color: #dc2626; }
.rp-stat-sparkline { position: absolute; right: 10px; bottom: 10px; opacity: .8; }
.rp-stat-ring { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); }

/* ── Cards ── */
.rp-card {
  background: #fff; border: 1px solid rgba(15,23,42,0.05); border-radius: 16px;
  padding: 12px; box-shadow: 0 6px 18px rgba(15,23,42,0.05);
  transition: transform .3s cubic-bezier(.22,1,.36,1), box-shadow .3s ease, border-color .3s ease;
}
.rp-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 18px 34px rgba(13,45,107,0.14);
  border-color: rgba(13,45,107,0.15);
}
.dark .rp-card { background: #161b28; border-color: rgba(255,255,255,0.06); box-shadow: 0 6px 18px rgba(0,0,0,0.2); }
.dark .rp-card:hover { box-shadow: 0 18px 34px rgba(0,0,0,0.45); border-color: rgba(255,255,255,0.14); }
.rp-card-icon {
  width: 26px; height: 26px; border-radius: 8px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  transition: transform .3s cubic-bezier(.22,1,.36,1);
}
.rp-card:hover .rp-card-icon { transform: scale(1.1) rotate(-4deg); }
.rp-card-title { font-size: 13px; font-weight: 800; color: #1e293b; }
.dark .rp-card-title { color: #e2e8f0; }
.rp-card-sub { font-size: 10px; color: #94a3b8; margin-top: 1px; }
.dark .rp-card-sub { color: #64748b; }

.rp-period-group { display: flex; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
.dark .rp-period-group { background: #0f1420; border-color: rgba(255,255,255,0.08); }
.rp-period-btn { font-size: 10.5px; font-weight: 700; color: #94a3b8; background: none; border: none; padding: 4px 9px; cursor: pointer; }
.rp-period-btn-active { background: var(--rf-primary); color: #fff; border-radius: 6px; }

.rp-legend-item { display: inline-flex; align-items: center; gap: 5px; font-size: 10.5px; font-weight: 600; color: #64748b; }
.dark .rp-legend-item { color: #94a3b8; }
.rp-legend-line { width: 16px; height: 0; border-top: 3px solid var(--rf-primary); display: inline-block; }
.rp-legend-line-dashed { border-top-style: dashed; border-top-color: #cbd5e1; }
.dark .rp-legend-line-dashed { border-top-color: #475569; }

.rp-donut-wrap { position: relative; flex-shrink: 0; }
.rp-donut-center { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; pointer-events: none; }
.rp-donut-center-num { font-size: 17px; font-weight: 800; color: #1e293b; }
.dark .rp-donut-center-num { color: #e2e8f0; }
.rp-donut-center-label { font-size: 8.5px; color: #94a3b8; font-weight: 600; }
.dark .rp-donut-center-label { color: #64748b; }

.rp-legend-row {
  display: flex; align-items: center; gap: 6px; font-size: 11px;
  padding: 2px 4px; margin: 0 -4px; border-radius: 6px;
  transition: background .2s ease, transform .2s ease;
  width: 100%; border: none; background: transparent; font-family: inherit; text-align: left; cursor: default;
}
.rp-legend-row-clickable { cursor: pointer; }
.rp-legend-row-clickable:hover { background: rgba(13,45,107,0.06); transform: translateX(2px); }
.dark .rp-legend-row-clickable:hover { background: rgba(255,255,255,0.06); }
.rp-legend-row-active { background: rgba(13,45,107,0.1) !important; }
.dark .rp-legend-row-active { background: rgba(255,255,255,0.1) !important; }
.rp-legend-row:disabled { cursor: default; }
.rp-legend-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; transition: transform .2s ease; }
.rp-legend-row:hover .rp-legend-dot { transform: scale(1.4); }
.rp-legend-label { flex: 1; min-width: 0; color: #475569; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.dark .rp-legend-label { color: #cbd5e1; }
.rp-legend-pct { color: #94a3b8; font-weight: 700; font-size: 10px; }
.dark .rp-legend-pct { color: #64748b; }
.rp-legend-count { color: #1e293b; font-weight: 800; font-size: 11px; min-width: 28px; text-align: right; }
.dark .rp-legend-count { color: #e2e8f0; }

.rp-empty {
  display: flex; flex-direction: column; align-items: center; gap: 6px;
  font-size: 11px; color: #94a3b8; font-weight: 600; text-align: center; padding: 18px 0;
}
.dark .rp-empty { color: #64748b; }
.rp-empty-icon { color: #cbd5e1; }
.dark .rp-empty-icon { color: #334155; }

.rp-tr-row {
  display: flex; align-items: center; gap: 8px; padding: 6px 4px; border-bottom: 1px solid #f1f5f9;
  border-radius: 8px; margin: 0 -4px; transition: background .2s ease, transform .2s ease;
}
.rp-tr-row:hover { background: rgba(13,45,107,0.05); transform: translateX(2px); }
.dark .rp-tr-row { border-bottom-color: rgba(255,255,255,0.05); }
.dark .rp-tr-row:hover { background: rgba(255,255,255,0.05); }
.rp-tr-row:last-child { border-bottom: none; }
.rp-tr-icon {
  width: 26px; height: 26px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
  transition: transform .2s cubic-bezier(.22,1,.36,1);
}
.rp-tr-row:hover .rp-tr-icon { transform: scale(1.12) rotate(-6deg); }
.rp-tr-label { flex: 1; font-size: 11.5px; font-weight: 700; color: #475569; }
.dark .rp-tr-label { color: #cbd5e1; }
.rp-tr-dias { font-size: 12px; font-weight: 800; color: #1e293b; }
.dark .rp-tr-dias { color: #e2e8f0; }
.rp-tr-delta { display: inline-flex; align-items: center; gap: 2px; font-size: 9.5px; font-weight: 700; min-width: 78px; justify-content: flex-end; }

/* ── Donuts / heatmap: leve realce al pasar el cursor ── */
.rp-donut-wrap { transition: transform .25s cubic-bezier(.22,1,.36,1); }
.rp-card:hover .rp-donut-wrap { transform: scale(1.03); }
.rp-card :deep(.apexcharts-canvas) { transition: filter .25s ease; }
.rp-card:hover :deep(.apexcharts-canvas) { filter: brightness(1.03); }

/* ── Animación de entrada ── */
.anim-slide-up { animation: rpSlideUp .5s cubic-bezier(.22,1,.36,1) both; }
@keyframes rpSlideUp {
  from { opacity: 0; transform: translateY(14px); }
  to { opacity: 1; transform: none; }
}
</style>
