<template>
  <div class="h-full flex flex-col gap-2 p-2 sm:p-3 overflow-y-auto dashboard-bg">

    <!-- ── Encabezado + Filtros ── -->
    <div class="flex items-start justify-between gap-3 flex-wrap shrink-0">
      <div>
        <h1 class="dash-title">Panel principal</h1>
        <p class="dash-subtitle">Resumen general del sistema de referencia</p>
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
          clearable
          @change="aplicarFiltros"
        />
        <el-select v-model="filtros.estado" size="small" style="width:150px" @change="aplicarFiltros">
          <el-option value="todas" label="Todos los estados" />
          <el-option value="pendiente" label="Pendientes" />
          <el-option value="en_espera" label="En espera" />
          <el-option value="completado" label="Completadas" />
          <el-option value="negado" label="Negadas" />
        </el-select>
        <el-select v-model="filtros.especialidad" size="small" style="width:170px" clearable placeholder="Especialidad" @change="aplicarFiltros">
          <el-option v-for="esp in especialidadesOpciones" :key="esp" :value="esp" :label="esp" />
        </el-select>
        <el-select v-model="filtros.eps" size="small" style="width:150px" clearable placeholder="EPS" @change="aplicarFiltros">
          <el-option v-for="e in epsOpciones" :key="e" :value="e" :label="e" />
        </el-select>
        <button v-if="hayFiltrosActivos" class="dash-clear-btn" @click="limpiarFiltros">
          <component :is="XIcon" class="w-3.5 h-3.5" />
          Limpiar
        </button>
      </div>
    </div>

    <!-- ── Aviso de error ── -->
    <div v-if="errorCarga" class="dash-error-banner shrink-0">
      <component :is="AlertTriangleIcon" class="w-4 h-4" />
      <span>No se pudieron cargar las estadísticas. Verifica tu conexión.</span>
      <button @click="cargarStats">Reintentar</button>
    </div>

    <!-- ── Stat cards ── -->
    <div v-if="primeraCarga && cargando" class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 shrink-0">
      <div v-for="i in 4" :key="i" class="stat-card-pastel-skeleton rounded-2xl p-3.5 flex items-center gap-3">
        <div class="shimmer-box" style="width:42px; height:42px; border-radius:12px; flex-shrink:0;"></div>
        <div class="flex-1 space-y-2">
          <div class="shimmer-bar" style="width:50%; height:20px;"></div>
          <div class="shimmer-bar" style="width:65%; height:10px;"></div>
        </div>
      </div>
    </div>
    <div v-else class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 shrink-0 animate-fade-in-up"
      style="animation-duration: 0.4s; animation-delay: 0.1s; animation-fill-mode: both;">
      <StatCard
        v-for="(card, i) in statCards" :key="i"
        class="anim-slide-up"
        :style="{ animationDelay: (i * 0.06) + 's' }"
        variant="pastel"
        :dark="isDark"
        :tone="card.tone"
        :value="card.value"
        :label="card.label"
        :icon="card.icon"
        :percent="card.percent"
        :delta="card.delta"
        :sparkline="card.sparkline"
        :comparacion="card.comparacion"
      />
    </div>

    <!-- ── Antigüedad de la solicitud pendiente más vieja ── -->
    <div v-if="slaAlerta" class="sla-alert shrink-0" :class="slaAlerta.clase">
      <component :is="ClockIcon" class="w-3.5 h-3.5" />
      <span>La solicitud pendiente más antigua lleva <strong>{{ slaAlerta.texto }}</strong> esperando revisión</span>
    </div>

    <!-- ── Proporción de estados + Mapa de calor + Solicitudes por clínica ── -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-2 shrink-0 animate-fade-in-up"
      style="animation-duration: 0.4s; animation-delay: 0.18s; animation-fill-mode: both;">
      <div class="distrib-card rounded-xl p-3 flex flex-col distrib-card-hover">
        <div class="flex items-center gap-2 mb-2">
          <div class="donut-header-icon">
            <component :is="PieChartIcon" class="w-3.5 h-3.5" />
          </div>
          <p class="text-xs font-bold" style="color:#1e2d55;">Proporción de estados</p>
          <span class="estado-total-badge">{{ stats.solicitudes.total }} total</span>
        </div>
        <div class="flex-1 flex items-center gap-3 min-h-0">
          <div class="estado-donut-wrap">
            <apexchart type="donut" height="108" width="108" :options="estadoDonutOptions" :series="estadoDonutSeries" />
            <div class="estado-donut-center">
              <span class="estado-donut-num">{{ stats.solicitudes.total }}</span>
              <span class="estado-donut-label">Total</span>
            </div>
          </div>
          <div class="flex-1 min-w-0 flex flex-col gap-1.5">
            <div v-for="item in donutBars" :key="item.label" class="estado-legend-row">
              <span class="estado-legend-dot" :style="{ background: item.color }"></span>
              <span class="estado-legend-label">{{ item.label }}</span>
              <span class="estado-legend-value" :style="{ color: item.color }">{{ item.value }}</span>
              <span class="estado-legend-pct">{{ item.pct }}%</span>
            </div>
          </div>
        </div>
      </div>

      <div class="distrib-card rounded-xl p-3 flex flex-col lg:col-span-2 distrib-card-hover">
        <div class="flex items-center gap-2 mb-2">
          <div class="donut-header-icon">
            <component :is="Grid3x3Icon" class="w-3.5 h-3.5" />
          </div>
          <div>
            <p class="text-xs font-bold" style="color:#1e2d55;">Solicitudes por día y hora</p>
            <p class="text-[10px] mt-0.5" style="color:#8a9ab5;">Mapa de calor · histórico completo</p>
          </div>
        </div>
        <div class="flex-1">
          <apexchart type="heatmap" height="150" :options="heatmapOptions" :series="heatmapSeries" />
        </div>
      </div>

      <div class="distrib-card rounded-xl p-3 flex flex-col distrib-card-hover">
        <div class="flex items-center gap-2 mb-2">
          <div class="donut-header-icon">
            <component :is="Building2Icon" class="w-3.5 h-3.5" />
          </div>
          <p class="text-xs font-bold" style="color:#1e2d55;">Solicitudes por clínica</p>
        </div>
        <div class="flex-1 w-full flex flex-col justify-center gap-2 min-h-0">
          <div v-for="(item, i) in clinicaBars" :key="item.clinica"
            class="ciudad-row anim-slide-up"
            :style="{ animationDelay: (i * 0.06) + 's' }">
            <span class="ciudad-label" :title="item.clinica">{{ item.clinica }}</span>
            <div class="ciudad-track">
              <div class="ciudad-fill" :style="{ width: item.pct + '%' }"></div>
            </div>
            <span class="ciudad-value">{{ item.total }}</span>
          </div>
          <div v-if="!clinicaBars.length" class="specialties-empty">
            <component :is="Building2Icon" class="w-6 h-6" />
            <span>Sin datos de clínicas</span>
          </div>
        </div>
      </div>
    </div>

    <div class="operations-grid animate-fade-in-up min-h-0"
      style="animation-duration: 0.4s; animation-delay: 0.25s; animation-fill-mode: both;">
      <section class="operations-card operations-card-hover">
        <div class="operations-glow"></div>
        <div class="operations-heading">
          <div class="flex items-center gap-2">
            <div class="donut-header-icon">
              <component :is="ShieldIcon" class="w-3.5 h-3.5" />
            </div>
            <div>
              <p class="panel-eyebrow">Aseguradoras</p>
              <h3 class="panel-title">Solicitudes por EPS</h3>
            </div>
          </div>
          <span class="operations-hint">Histórico completo</span>
        </div>

        <div class="specialties-chart-wrap">
          <apexchart
            v-if="epsBars.length"
            type="bar"
            height="100%"
            :options="epsChartOptions"
            :series="epsChartSeries"
          />
          <div v-else class="specialties-empty">
            <component :is="ShieldIcon" class="w-6 h-6" />
            <span>Sin datos suficientes</span>
          </div>
        </div>
      </section>

      <section class="flow-card flow-card-hover ref-flow-card">
        <div class="flow-orbit flow-orbit-one"></div>
        <div class="flow-orbit flow-orbit-two"></div>
        <div class="flow-content h-full flex flex-col">
          <div class="flow-heading">
            <div class="flex items-center gap-2">
              <div class="gauge-header-icon">
                <component :is="ClipboardListIcon" class="w-3.5 h-3.5" />
              </div>
              <div>
                <p class="flow-eyebrow">Recientes</p>
                <h3>Últimas 5 referencias</h3>
              </div>
            </div>
            <span class="flow-live"><i></i> En línea</span>
          </div>

          <div class="gauge-wrap flex-1 ultimas-ref-list">
            <template v-if="ultimas5Ref.length">
              <button
                v-for="(s, idx) in ultimas5Ref"
                :key="s.id"
                type="button"
                class="ref-row"
                :style="{ '--ref-color': estadoColorMap[s.estado] ?? '#94a3b8', animationDelay: `${idx * 60}ms` }"
                @click="router.push('/solicitudes-referencia')"
                @mouseenter="onRefRowEnter($event, s)"
                @mouseleave="onRefRowLeave"
              >
                <span class="ref-avatar">{{ (s.paciente || '?').trim().charAt(0).toUpperCase() }}</span>
                <span class="ref-info">
                  <span class="ref-name">{{ s.paciente }}</span>
                  <span class="ref-meta">
                    <span>{{ s.tipo_documento || 'CC' }} {{ s.numero_documento || '—' }}</span>
                    <span class="ref-dot">•</span>
                    <span>{{ s.telefono_contacto || 'Sin teléfono' }}</span>
                  </span>
                </span>
                <span class="ref-badge">{{ estadoLabel(s.estado) }}</span>
              </button>
            </template>
            <div v-else class="specialties-empty">
              <component :is="ClipboardListIcon" class="w-6 h-6" />
              <span>Sin solicitudes</span>
            </div>
          </div>
        </div>
      </section>

      <Teleport to="body">
        <div
          v-if="refTooltip"
          class="ref-tooltip-float"
          :style="{ top: refTooltip.top + 'px', left: refTooltip.left + 'px', '--ref-color': estadoColorMap[refTooltip.s.estado] ?? '#94a3b8', transform: refTooltip.placement === 'top' ? 'translate(-50%, -100%)' : 'translate(-50%, 0)' }"
        >
          <span class="ref-tooltip-arrow" :class="refTooltip.placement === 'top' ? 'arrow-down' : 'arrow-up'"></span>
          <span class="ref-tooltip-head">
            <span class="ref-tooltip-avatar">{{ (refTooltip.s.paciente || '?').trim().charAt(0).toUpperCase() }}</span>
            <span class="ref-tooltip-name">{{ refTooltip.s.nombre_completo || refTooltip.s.paciente }}</span>
          </span>
          <span class="ref-tooltip-row"><strong>Doc:</strong> {{ refTooltip.s.tipo_documento || 'CC' }} {{ refTooltip.s.numero_documento || '—' }}</span>
          <span class="ref-tooltip-row"><strong>Tel:</strong> {{ refTooltip.s.telefono_contacto || '—' }}</span>
          <span class="ref-tooltip-row"><strong>Estado:</strong> <span class="ref-tooltip-estado">{{ estadoLabel(refTooltip.s.estado) }}</span></span>
        </div>
      </Teleport>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useLayoutStore } from '@/stores/layout';
import { usePolling } from '@/lib/usePolling';
import {
  ArrowRight as ArrowRightIcon,
  CalendarDays as CalendarDaysIcon,
  Clock3 as ClockIcon,
  Users as UsersIcon,
  ClipboardList as ClipboardListIcon,
  Hospital as HospitalIcon,
  TrendingUp as TrendingUpIcon,
  TrendingDown as TrendingDownIcon,
  PieChart as PieChartIcon,
  Target as TargetIcon,
  Grid3x3 as Grid3x3Icon,
  Building2 as Building2Icon,
  Shield as ShieldIcon,
  X as XIcon,
  AlertTriangle as AlertTriangleIcon,
} from '@lucide/vue';
import http from '@/plugins/axios';
import StatCard, { type StatCardTone } from '@/components/ui/StatCard.vue';

const router = useRouter();
const layout = useLayoutStore();
const isDark = computed(() => layout.isDarkMode);

type SolicitudReciente = {
  id: number; paciente: string; nombre_completo: string; tipo_documento: string; numero_documento: string; telefono_contacto: string; estado: string; especialidad: string; clinica: string | null; created_at: string | null;
};

const refTooltip = ref<{ top: number; left: number; placement: 'top' | 'bottom'; s: SolicitudReciente } | null>(null);

function onRefRowEnter(event: MouseEvent, s: SolicitudReciente) {
  const target = event.currentTarget as HTMLElement;
  const rect = target.getBoundingClientRect();
  const spaceAbove = rect.top;
  const placement: 'top' | 'bottom' = spaceAbove > 140 ? 'top' : 'bottom';
  refTooltip.value = {
    top: placement === 'top' ? rect.top - 10 : rect.bottom + 10,
    left: rect.left + rect.width / 2,
    placement,
    s,
  };
}

function onRefRowLeave() {
  refTooltip.value = null;
}

const cargando = ref(true);
const primeraCarga = ref(true);
const errorCarga = ref(false);

const stats = ref({
  solicitudes: { total: 0, pendientes: 0, negadas: 0, en_espera: 0, completadas: 0 },
  clinicas: { total: 0, activas: 0, pendientes: 0 },
  usuarios: { total: 0, activos: 0 },
  solicitudes_recientes: [] as Array<{
    id: number; paciente: string; nombre_completo: string; tipo_documento: string; numero_documento: string; telefono_contacto: string; estado: string; especialidad: string; clinica: string | null; created_at: string | null;
  }>,
  filtros: { especialidades: [] as string[], eps: [] as string[] },
  tendencia: [] as Array<{ fecha: string; total: number }>,
  tendencia_clinicas: [] as Array<{ fecha: string; total: number }>,
  tendencia_usuarios: [] as Array<{ fecha: string; total: number }>,
  tendencia_pendientes: [] as Array<{ fecha: string; total: number }>,
  pendiente_mas_antigua_horas: null as number | null,
  top_especialidades: [] as Array<{ especialidad: string; total: number }>,
  tasa_aceptacion: 0,
  por_dia_hora: [] as Array<{ dia: string; hora: string; total: number }>,
  por_clinica: [] as Array<{ clinica: string; total: number }>,
  por_eps: [] as Array<{ eps: string; total: number }>,
});

const filtros = ref({
  desde: '',
  hasta: '',
  estado: 'todas',
  especialidad: '',
  eps: '',
});

const especialidadesOpciones = computed(() => stats.value.filtros?.especialidades ?? []);
const epsOpciones = computed(() => stats.value.filtros?.eps ?? []);

const rangoFechas = computed<[string, string] | null>({
  get: (): [string, string] | null => (filtros.value.desde && filtros.value.hasta) ? [filtros.value.desde, filtros.value.hasta] : null,
  set: (val: [string, string] | null) => {
    filtros.value.desde = val?.[0] ?? '';
    filtros.value.hasta = val?.[1] ?? '';
  },
});

const hayFiltrosActivos = computed(() =>
  filtros.value.desde || filtros.value.hasta ||
  (filtros.value.estado && filtros.value.estado !== 'todas') ||
  filtros.value.especialidad || filtros.value.eps
);

function limpiarFiltros() {
  filtros.value = { desde: '', hasta: '', estado: 'todas', especialidad: '', eps: '' };
  cargarStats();
}

function aplicarFiltros() {
  cargarStats();
}

const displayStats = ref([0, 0, 0, 0]);

function animateCounters(targets: number[]) {
  const duration = 900;
  const steps = 40;
  const interval = duration / steps;
  let step = 0;
  const timer = setInterval(() => {
    step++;
    const progress = step / steps;
    const ease = 1 - Math.pow(1 - progress, 3);
    displayStats.value = targets.map(t => Math.round(t * ease));
    if (step >= steps) {
      displayStats.value = [...targets];
      clearInterval(timer);
    }
  }, interval);
}

const statCards = computed((): { label: string; value: string; icon: typeof ClipboardListIcon; tone: StatCardTone; delta: string; percent: number; sparkline?: number[]; comparacion?: string }[] => [
  {
    label: 'Solicitudes totales',
    value: String(displayStats.value[0]),
    icon: ClipboardListIcon,
    tone: 'info',
    delta: `${stats.value.solicitudes.pendientes} pend.`,
    percent: stats.value.solicitudes.total ? Math.round(((stats.value.solicitudes.en_espera + stats.value.solicitudes.completadas) / stats.value.solicitudes.total) * 100) : 0,
    sparkline: stats.value.tendencia.map(t => t.total),
    comparacion: comparacionTexto(tendenciaCambioPct.value),
  },
  {
    label: 'Clínicas registradas',
    value: String(displayStats.value[1]),
    icon: HospitalIcon,
    tone: 'success',
    delta: `${stats.value.clinicas.activas} activas`,
    percent: stats.value.clinicas.total ? Math.round((stats.value.clinicas.activas / stats.value.clinicas.total) * 100) : 0,
    sparkline: stats.value.tendencia_clinicas.map(t => t.total),
    comparacion: comparacionTexto(cambioPct(stats.value.tendencia_clinicas)),
  },
  {
    label: 'Solicitudes pendientes',
    value: String(displayStats.value[2]),
    icon: ClockIcon,
    tone: 'warning',
    delta: `${stats.value.solicitudes.en_espera} en espera`,
    percent: stats.value.solicitudes.total ? Math.round((stats.value.solicitudes.pendientes / stats.value.solicitudes.total) * 100) : 0,
    sparkline: stats.value.tendencia_pendientes.map(t => t.total),
    comparacion: comparacionTexto(cambioPct(stats.value.tendencia_pendientes)),
  },
  {
    label: 'Usuarios activos',
    value: String(displayStats.value[3]),
    icon: UsersIcon,
    tone: 'violet',
    delta: `${stats.value.usuarios.total} total`,
    percent: stats.value.usuarios.total ? Math.round((stats.value.usuarios.activos / stats.value.usuarios.total) * 100) : 0,
    sparkline: stats.value.tendencia_usuarios.map(t => t.total),
    comparacion: comparacionTexto(cambioPct(stats.value.tendencia_usuarios)),
  },
]);

const donutBars = computed(() => {
  const s = stats.value.solicitudes;
  const total = Math.max(1, s.total);
  return [
    { label: 'Pendientes', value: s.pendientes, color: '#f59e0b', color2: '#fbbf24', pct: Math.round((s.pendientes / total) * 100) },
    { label: 'En espera', value: s.en_espera, color: '#3b82f6', color2: '#60a5fa', pct: Math.round((s.en_espera / total) * 100) },
    { label: 'Completadas', value: s.completadas, color: '#22c55e', color2: '#4ade80', pct: Math.round((s.completadas / total) * 100) },
    { label: 'Negadas', value: s.negadas, color: '#ef4444', color2: '#f87171', pct: Math.round((s.negadas / total) * 100) },
  ];
});

const estadoDonutSeries = computed(() => donutBars.value.map(b => b.value));
const estadoDonutOptions = computed(() => ({
  chart: { type: 'donut' as const, fontFamily: 'inherit', animations: { enabled: true, speed: 700 }, sparkline: { enabled: true } },
  labels: donutBars.value.map(b => b.label),
  colors: donutBars.value.map(b => b.color),
  legend: { show: false, floating: true },
  dataLabels: { enabled: false },
  stroke: { width: 2, colors: [isDark.value ? '#0f172a' : '#fff'] },
  plotOptions: { pie: { offsetY: 10, customScale: 1, donut: { size: '72%', labels: { show: false } } } },
  grid: { padding: { top: 0, right: 0, bottom: 0, left: 0 } },
  tooltip: {
    theme: isDark.value ? 'dark' : 'light',
    y: { formatter: (val: number) => `${val} solicitudes` },
    // Fijo en vez de seguir el cursor — en un donut chico (108px) el tooltip
    // "flotante" se salía de la tarjeta y quedaba recortado para las
    // porciones cercanas al borde (Completadas/Negadas).
    fixed: { enabled: true, position: 'topRight' },
  },
}));

/** Mapa de calor: día de la semana × franja horaria, sobre el histórico completo. */
const heatmapSeries = computed(() => {
  const dias = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
  return dias.map(dia => ({
    name: dia,
    data: stats.value.por_dia_hora
      .filter(c => c.dia === dia)
      .map(c => ({ x: c.hora, y: c.total })),
  })).reverse();
});
const heatmapOptions = computed(() => ({
  chart: { type: 'heatmap' as const, fontFamily: 'inherit', toolbar: { show: false } },
  dataLabels: { enabled: false },
  legend: { show: false },
  xaxis: {
    labels: { style: { fontSize: '9px', colors: '#94a3b8' } },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: { labels: { style: { fontSize: '9px', colors: '#475569' } } },
  grid: { padding: { left: 4, right: 4 } },
  plotOptions: {
    heatmap: {
      radius: 3,
      colorScale: {
        ranges: [
          { from: 0, to: 0, color: isDark.value ? '#1e293b' : '#eef2ff', name: 'Sin solicitudes' },
          { from: 1, to: 1, color: '#a5b4fc', name: 'Baja' },
          { from: 2, to: 3, color: '#6366f1', name: 'Media' },
          { from: 4, to: 999, color: '#3730a3', name: 'Alta' },
        ],
      },
    },
  },
  tooltip: { theme: isDark.value ? 'dark' : 'light', y: { formatter: (val: number) => `${val} solicitud${val === 1 ? '' : 'es'}` } },
}));

/** Distribución real por clínica, viene ya agregada y ordenada desde el backend. */
const clinicaBars = computed(() => {
  const filas = stats.value.por_clinica;
  const max = Math.max(1, ...filas.map(f => f.total));
  return filas.map(f => ({ ...f, pct: Math.round((f.total / max) * 100) }));
});

/** % de cambio entre la primera y segunda mitad de una serie diaria — mismo cálculo para cualquier tendencia (solicitudes, clínicas, usuarios, pendientes). */
function cambioPct(dias: Array<{ total: number }>): number {
  const mitad = Math.floor(dias.length / 2);
  if (!mitad) return 0;
  const primeraMitad = dias.slice(0, mitad).reduce((acc, t) => acc + t.total, 0);
  const segundaMitad = dias.slice(mitad).reduce((acc, t) => acc + t.total, 0);
  if (primeraMitad === 0) return segundaMitad > 0 ? 100 : 0;
  return Math.round(((segundaMitad - primeraMitad) / primeraMitad) * 100);
}

function comparacionTexto(pct: number): string {
  return `${pct >= 0 ? '↑' : '↓'}${Math.abs(pct)}% vs periodo anterior`;
}

const tendenciaCambioPct = computed(() => cambioPct(stats.value.tendencia));

/** Distribución real por EPS (aseguradora), viene ya agregada desde el backend. */
const epsBars = computed(() => stats.value.por_eps);
const epsChartSeries = computed(() => [{
  name: 'Solicitudes',
  data: epsBars.value.map(e => e.total),
}]);
const epsChartOptions = computed(() => ({
  chart: {
    type: 'bar' as const,
    fontFamily: 'inherit',
    background: 'transparent',
    toolbar: { show: false },
    animations: { enabled: true, easing: 'easeinout' as const, speed: 700 },
  },
  plotOptions: {
    bar: {
      horizontal: true,
      borderRadius: 6,
      borderRadiusApplication: 'end' as const,
      barHeight: '60%',
      distributed: true,
      dataLabels: { position: 'end' as const },
    },
  },
  colors: ['#4f46e5', '#6366f1', '#818cf8', '#a5b4fc', '#7048e8', '#9333ea', '#c026d3', '#db2777'],
  xaxis: {
    categories: epsBars.value.map(e => e.eps),
    labels: { style: { fontSize: '10px', colors: '#94a3b8' } },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: {
    labels: { style: { fontSize: '9px', fontWeight: 600, colors: isDark.value ? '#cbd5e1' : '#475569' }, maxWidth: 260, trim: false },
  },
  grid: {
    borderColor: isDark.value ? '#1e293b' : '#f1f5f9',
    strokeDashArray: 4,
    yaxis: { lines: { show: false } },
    padding: { top: -8, right: 20, bottom: -8, left: 16 },
  },
  dataLabels: {
    enabled: true,
    formatter: (val: number) => String(val),
    position: 'end' as const,
    textAnchor: 'start' as const,
    style: { fontSize: '10.5px', fontWeight: 800, colors: ['#fff'] },
    dropShadow: { enabled: false },
    offsetX: -4,
  },
  tooltip: {
    y: { formatter: (val: number) => `${val} solicitudes` },
    theme: isDark.value ? 'dark' : 'light',
  },
  legend: { show: false },
}));

// ── Últimas 5 referencias (bar chart horizontal) ───────────────────────────
const estadoColorMap: Record<string, string> = {
  pendiente: '#f59e0b',
  en_espera: '#3b82f6',
  completado: '#22c55e',
  negado: '#ef4444',
};

function estadoLabel(estado: string): string {
  return { pendiente: 'Pendiente', en_espera: 'En espera', completado: 'Completada', negado: 'Negada' }[estado] ?? estado;
}

const ultimas5Ref = computed(() => stats.value.solicitudes_recientes.slice(0, 5));

/** Texto/color del aviso de antigüedad de la solicitud pendiente más vieja. */
const slaAlerta = computed(() => {
  const horas = stats.value.pendiente_mas_antigua_horas;
  if (horas === null || horas === undefined) return null;

  const texto = horas < 1
    ? 'menos de 1 hora'
    : horas < 24
      ? `${Math.floor(horas)} h`
      : `${Math.floor(horas / 24)} día(s)`;

  const clase = horas > 6 ? 'sla-alert-danger' : horas > 2 ? 'sla-alert-warning' : 'sla-alert-ok';

  return { texto, clase };
});

async function cargarStats() {
  try {
    cargando.value = true;
    const params: Record<string, string> = {};
    if (filtros.value.desde) params.desde = filtros.value.desde;
    if (filtros.value.hasta) params.hasta = filtros.value.hasta;
    if (filtros.value.estado && filtros.value.estado !== 'todas') params.estado = filtros.value.estado;
    if (filtros.value.especialidad) params.especialidad = filtros.value.especialidad;
    if (filtros.value.eps) params.eps = filtros.value.eps;
    const { data } = await http.get('/api/dashboard/stats', { params });
    stats.value = data.data;
    errorCarga.value = false;
    animateCounters([
      stats.value.solicitudes.total,
      stats.value.clinicas.total,
      stats.value.solicitudes.pendientes,
      stats.value.usuarios.activos,
    ]);
  } catch {
    errorCarga.value = true;
  } finally {
    cargando.value = false;
    primeraCarga.value = false;
  }
}

onMounted(cargarStats);

// Refresco silencioso: mantiene honesto el "En línea" de Últimas referencias
// sin mostrar el esqueleto de carga en cada ciclo.
usePolling(() => {
  if (!cargando.value) cargarStats();
}, 30000);
</script>

<style scoped>
/* ── Background ── */
.dashboard-bg {
  background:
    radial-gradient(ellipse at 90% 0%, rgba(188, 218, 255, 0.35), transparent 30rem),
    radial-gradient(ellipse at 10% 100%, rgba(208, 242, 226, 0.25), transparent 28rem);
}

/* ── Encabezado + filtros ── */
.dash-title { font-size: 20px; font-weight: 800; color: #1e293b; letter-spacing: -0.02em; }
.dark .dash-title { color: #e2e8f0; }
.dash-subtitle { font-size: 12px; color: #94a3b8; margin-top: 2px; }
.dark .dash-subtitle { color: #64748b; }
.dash-clear-btn {
  display: flex; align-items: center; gap: 6px;
  font-size: 12px; font-weight: 600; color: #64748b;
  background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
  padding: 7px 12px; cursor: pointer; transition: all .15s ease;
}
.dash-clear-btn:hover { border-color: var(--rf-primary); color: var(--rf-primary); }
.dark .dash-clear-btn { background: #161b28; border-color: rgba(255,255,255,0.08); color: #cbd5e1; }
.dark .dash-clear-btn:hover { border-color: var(--rf-primary); color: #a5b4fc; }

/* ── Aviso de error ── */
.dash-error-banner {
  display: flex; align-items: center; gap: 8px;
  background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c;
  border-radius: 10px; padding: 8px 12px; font-size: 12px; font-weight: 600;
}
.dash-error-banner button {
  margin-left: auto; background: #fff; border: 1px solid #fecaca; color: #b91c1c;
  border-radius: 8px; padding: 4px 10px; font-size: 11px; font-weight: 700; cursor: pointer;
}
.dash-error-banner button:hover { background: #fee2e2; }
.dark .dash-error-banner { background: rgba(220,38,38,0.12); border-color: rgba(220,38,38,0.3); color: #fca5a5; }
.dark .dash-error-banner button { background: transparent; border-color: rgba(220,38,38,0.3); color: #fca5a5; }

/* ── Skeleton de tarjetas ── */
.stat-card-pastel-skeleton {
  background: #fff; border: 1px solid rgba(15, 23, 42, 0.04);
}
.dark .stat-card-pastel-skeleton { background: #161b28; }

/* ── Antigüedad de la solicitud pendiente más vieja ── */
.sla-alert {
  display: flex; align-items: center; gap: 7px;
  border-radius: 10px; padding: 7px 12px; font-size: 12px; font-weight: 600;
}
.sla-alert strong { font-weight: 800; }
.sla-alert-ok { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
.sla-alert-warning { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
.sla-alert-danger { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
.dark .sla-alert-ok { background: rgba(34,197,94,0.12); color: #86efac; border-color: rgba(34,197,94,0.25); }
.dark .sla-alert-warning { background: rgba(245,158,11,0.12); color: #fcd34d; border-color: rgba(245,158,11,0.25); }
.dark .sla-alert-danger { background: rgba(220,38,38,0.12); color: #fca5a5; border-color: rgba(220,38,38,0.3); }

/* ── Stat cards ── */
/* El marcado y estilos de la tarjeta viven ahora en components/ui/StatCard.vue */

/* ── Panel cards ── */
.panel-card {
  background: rgba(255,255,255,0.92);
  border: 1px solid rgba(212, 222, 234, 0.6);
  box-shadow: 0 4px 20px rgba(22, 70, 142, .07);
  transition: transform .3s cubic-bezier(.22,1,.36,1), box-shadow .3s cubic-bezier(.22,1,.36,1);
  position: relative;
  overflow: hidden;
  backdrop-filter: blur(10px);
}
.panel-top-bar {
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: linear-gradient(90deg, #0D2D6B, #16468E, #7eb3ff);
  opacity: .85;
  transition: height .28s ease, opacity .28s ease;
}
.panel-glow {
  position: absolute; top: -60px; right: -60px; width: 200px; height: 200px;
  border-radius: 50%; pointer-events: none;
  background: radial-gradient(circle, rgba(126,179,255,0.12), transparent 70%);
}
.panel-glow-amber {
  background: radial-gradient(circle, rgba(251,191,36,0.10), transparent 70%);
}
.panel-card:hover {
  box-shadow: 0 12px 32px rgba(22, 70, 142, .12);
}
.panel-card:hover .panel-top-bar {
  height: 5px;
  opacity: 1;
}
.panel-icon-wrap {
  width: 36px; height: 36px;
  border-radius: 11px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #eaf4ff, #dbeafe);
  color: #16468E;
  flex-shrink: 0;
}
.panel-eyebrow {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .08em;
  color: #8a9ab5;
  margin: 0;
}
.panel-title {
  font-size: 15px;
  font-weight: 800;
  color: #1e2d55;
  margin: 2px 0 0;
}

/* ── Status pill ── */
.status-pill {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  padding: .35rem .8rem;
  border-radius: 999px;
  background: #dcfce7;
  color: #168759;
  font-size: 11px;
  font-weight: 600;
  white-space: nowrap;
}
.status-pill span {
  width: 7px; height: 7px;
  border-radius: 999px;
  background: #22c55e;
  box-shadow: 0 0 0 3px rgba(34, 197, 94, .15);
}

/* ── Service rows ── */
.service-row {
  padding: 8px 12px;
  border-radius: 12px;
  transition: background .2s ease;
}
.service-row:hover {
  background: #f8fafc;
}
.service-dot {
  width: 8px; height: 8px;
  border-radius: 999px;
  flex-shrink: 0;
  box-shadow: 0 0 0 3px rgba(255,255,255,0.8);
}
.service-name {
  font-size: 13px;
  font-weight: 600;
  color: #334155;
}
.service-percent {
  font-size: 14px;
  font-weight: 800;
}
.service-status-tag {
  font-size: 9px;
  font-weight: 700;
  padding: 2px 7px;
  border-radius: 999px;
  text-transform: uppercase;
  letter-spacing: .04em;
}

/* ── Capacity bars ── */
.capacity-track {
  height: 8px;
  overflow: hidden;
  background: #edf2f7;
  border-radius: 999px;
}
.capacity-bar {
  height: 100%;
  border-radius: inherit;
  transition: width .8s cubic-bezier(.22,1,.36,1);
  box-shadow: 0 1px 3px rgba(12, 45, 94, .12);
}

/* ── Timeline ── */
.timeline {
  position: relative;
  padding-left: 4px;
}
.timeline-item {
  position: relative;
  display: flex;
  gap: 12px;
  padding-bottom: 20px;
}
.timeline-item:last-child { padding-bottom: 0; }
.timeline-line {
  position: absolute;
  left: 15px;
  top: 32px;
  bottom: 0;
  width: 2px;
  background: linear-gradient(to bottom, #e2e8f0, transparent);
}
.timeline-dot {
  width: 32px; height: 32px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  z-index: 1;
  box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.timeline-content {
  flex: 1;
  min-width: 0;
}
.timeline-title {
  font-size: 12px;
  font-weight: 700;
  color: #334155;
  margin: 2px 0 0;
}
.timeline-desc {
  font-size: 11px;
  color: #94a3b8;
  margin: 3px 0 0;
  line-height: 1.4;
}
.timeline-time {
  font-size: 10px;
  color: #cbd5e1;
  font-weight: 600;
  margin-top: 4px;
  display: inline-block;
}

/* ── Activity tones ── */
.activity-success { color: #168759; background: #dcfce7; }
.activity-primary { color: #2563c4; background: #dbeafe; }
.activity-warning { color: #b7750a; background: #fef3c7; }

/* ── Distribución + Donut ── */
.distrib-card {
  background: rgba(255,255,255,0.95);
  border: 1px solid rgba(212, 222, 234, 0.6);
  box-shadow: 0 4px 20px rgba(22, 70, 142, .07);
  transition: transform .3s cubic-bezier(.22,1,.36,1), box-shadow .3s cubic-bezier(.22,1,.36,1);
  backdrop-filter: blur(10px);
  position: relative;
  overflow: hidden;
}
.distrib-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: linear-gradient(90deg, #0D2D6B 0%, #16468E 50%, #3b82f6 100%);
  opacity: 0.8;
}
.distrib-card:hover { transform: translateY(-4px); box-shadow: 0 14px 30px rgba(22,70,142,.12); }
.distrib-card-hover {
  transition: transform .35s cubic-bezier(.22,1,.36,1), box-shadow .35s cubic-bezier(.22,1,.36,1);
}
.distrib-card-hover:hover {
  transform: translateY(-4px) scale(1.005);
  box-shadow: 0 16px 36px rgba(22,70,142,.14);
}

/* ── Estado total badge ── */
.estado-total-badge {
  margin-left: auto;
  font-size: 9px;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 999px;
  background: #e0ecff;
  color: #16468e;
}

/* ── Donut header + custom legend ── */
.donut-header-icon {
  width: 24px; height: 24px;
  border-radius: 7px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #eaf4ff, #dbeafe);
  color: #16468E;
  flex-shrink: 0;
}
.donut-chart-wrap {
  flex-shrink: 0;
  width: 55%;
  min-height: 140px;
}
.donut-legend {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: .35rem;
}
.donut-legend-item {
  display: flex;
  align-items: center;
  gap: .4rem;
  padding: .3rem .5rem;
  border-radius: 8px;
  background: #f8fafc;
  transition: background .2s ease, transform .2s ease;
}
.donut-legend-item:hover {
  background: #eff6ff;
  transform: translateX(2px);
}
.donut-legend-dot {
  width: 8px; height: 8px;
  border-radius: 999px;
  flex-shrink: 0;
  box-shadow: 0 0 0 3px rgba(255,255,255,0.9);
}
.donut-legend-label {
  flex: 1;
  min-width: 0;
  font-size: 10.5px;
  font-weight: 600;
  color: #475569;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.donut-legend-value {
  font-size: 12px;
  font-weight: 800;
  color: #1e2d55;
}

/* ── Estado bars (reemplazo del donut) ── */
.estado-bar-wrap {
  position: relative;
  transition: transform .25s ease;
}
.estado-bar-wrap:hover {
  transform: translateX(3px);
}
.estado-bar-dot {
  width: 8px; height: 8px;
  border-radius: 999px;
  flex-shrink: 0;
  box-shadow: 0 0 0 3px rgba(255,255,255,0.9);
  animation: barDotPulse 2.5s ease-in-out infinite;
}
@keyframes barDotPulse {
  0%, 100% { box-shadow: 0 0 0 3px rgba(255,255,255,0.9); }
  50% { box-shadow: 0 0 0 5px rgba(255,255,255,0.6); }
}
.estado-bar-label {
  font-size: 11px;
  font-weight: 600;
  color: #475569;
}
.estado-bar-value {
  font-size: 13px;
  font-weight: 800;
  transition: transform .25s ease;
}
.estado-bar-wrap:hover .estado-bar-value {
  transform: scale(1.15);
}
.estado-bar-track {
  height: 8px;
  border-radius: 999px;
  background: #f1f5f9;
  overflow: hidden;
  box-shadow: inset 0 1px 2px rgba(0,0,0,0.06);
}
.estado-bar-fill {
  height: 100%;
  border-radius: 999px;
  transition: width 1s cubic-bezier(.22,1,.36,1);
  box-shadow: 0 1px 4px rgba(0,0,0,0.12);
  position: relative;
  overflow: hidden;
}
.estado-bar-shine {
  position: absolute;
  top: 0; left: -40%;
  width: 40%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.5), transparent);
  animation: barShine 2.5s ease-in-out infinite;
}
@keyframes barShine {
  0% { left: -40%; }
  100% { left: 100%; }
}

/* ── Dona de estados ── */
.estado-donut-wrap { position: relative; flex-shrink: 0; width: 108px; height: 108px; }
.estado-donut-center { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; pointer-events: none; }
.estado-donut-num { font-size: 17px; font-weight: 800; color: #1e293b; }
.dark .estado-donut-num { color: #e2e8f0; }
.estado-donut-label { font-size: 8.5px; color: #94a3b8; font-weight: 600; }
.dark .estado-donut-label { color: #64748b; }

.estado-legend-row { display: flex; align-items: center; gap: 6px; font-size: 11px; }
.estado-legend-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.estado-legend-label { flex: 1; min-width: 0; color: #475569; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.dark .estado-legend-label { color: #cbd5e1; }
.estado-legend-value { font-weight: 800; font-size: 11.5px; }
.estado-legend-pct { color: #94a3b8; font-weight: 700; font-size: 10px; min-width: 30px; text-align: right; }
.dark .estado-legend-pct { color: #64748b; }

/* ── Barras por ciudad/clínica ── */
.ciudad-row { display: flex; align-items: center; gap: 8px; font-size: 11px; }
.ciudad-label { width: 100px; flex-shrink: 0; color: #475569; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.dark .ciudad-label { color: #cbd5e1; }
.ciudad-track { flex: 1; height: 6px; border-radius: 999px; background: #f1f5f9; overflow: hidden; }
.dark .ciudad-track { background: rgba(255,255,255,0.06); }
.ciudad-fill { height: 100%; border-radius: 999px; background: linear-gradient(90deg, #16468E, #2563c4); transition: width .6s cubic-bezier(.22,1,.36,1); }
.ciudad-value { width: 22px; flex-shrink: 0; text-align: right; font-weight: 800; color: #1e293b; }
.dark .ciudad-value { color: #e2e8f0; }

/* ── Specialties bar chart ── */
.specialties-chart-wrap {
  flex: 1;
  min-height: 0;
  position: relative;
  z-index: 1;
}
.specialties-empty {
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: .4rem;
  color: #94a3b8;
  font-size: 11px;
  font-weight: 600;
}

/* ── Acceptance gauge ── */
.gauge-header-icon {
  width: 24px; height: 24px;
  border-radius: 7px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #dcfce7, #bbf7d0);
  color: #15966a;
  flex-shrink: 0;
}
.gauge-wrap {
  min-height: 160px;
  position: relative;
  z-index: 1;
}
.gauge-caption {
  text-align: center;
  font-size: 10px;
  font-weight: 600;
  color: #8a9ab5;
  position: relative;
  z-index: 1;
  margin-top: -.25rem;
}

/* ── Últimas referencias (list) ── */
.ref-flow-card {
  overflow: visible;
}
.ref-flow-card .flow-orbit {
  display: none;
}
.ultimas-ref-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  min-height: 0;
  max-height: 260px;
  overflow-y: auto;
  overflow-x: hidden;
  padding-right: 4px;
  scrollbar-width: thin;
  scrollbar-color: #93c5fd #eef2ff;
}
.ultimas-ref-list::-webkit-scrollbar {
  width: 6px;
}
.ultimas-ref-list::-webkit-scrollbar-track {
  background: #eef2ff;
  border-radius: 999px;
}
.ultimas-ref-list::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, #60a5fa, #3b82f6);
  border-radius: 999px;
}
.ultimas-ref-list::-webkit-scrollbar-thumb:hover {
  background: #2563eb;
}
.ref-row {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  flex-shrink: 0;
  padding: 8px 12px;
  border-radius: 12px;
  border: 1px solid #eef1f6;
  background: #fff;
  cursor: pointer;
  text-align: left;
  font: inherit;
  position: relative;
  overflow: hidden;
  opacity: 0;
  animation: refRowIn .5s ease forwards;
  transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
}
.ref-row::before {
  content: '';
  position: absolute;
  left: 0; top: 0; bottom: 0;
  width: 4px;
  background: var(--ref-color, #94a3b8);
  border-radius: 0 4px 4px 0;
}
.ref-row:hover {
  border-color: color-mix(in srgb, var(--ref-color, #94a3b8) 45%, #eef1f6);
  box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08);
  transform: translateX(2px);
}
@keyframes refRowIn {
  from { opacity: 0; transform: translateY(6px); }
  to { opacity: 1; transform: translateY(0); }
}
.ref-avatar {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 800;
  color: #fff;
  background: linear-gradient(135deg, color-mix(in srgb, var(--ref-color, #94a3b8) 85%, #fff), var(--ref-color, #94a3b8));
  box-shadow: 0 3px 8px color-mix(in srgb, var(--ref-color, #94a3b8) 40%, transparent);
}
.ref-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.ref-name {
  font-size: 12.5px;
  font-weight: 700;
  color: #1e2d55;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.ref-meta {
  font-size: 10.5px;
  font-weight: 500;
  color: #94a3b8;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  display: flex;
  align-items: center;
  gap: 5px;
}
.ref-dot {
  color: #cbd5e1;
  flex-shrink: 0;
}
.ref-badge {
  flex-shrink: 0;
  font-size: 10px;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 999px;
  color: var(--ref-color, #94a3b8);
  background: color-mix(in srgb, var(--ref-color, #94a3b8) 14%, #fff);
  white-space: nowrap;
}
.ref-tooltip-float {
  position: fixed;
  min-width: 210px;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 12px 32px rgba(15, 23, 42, 0.2);
  padding: 10px 14px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  z-index: 9999;
  text-align: left;
  pointer-events: none;
  animation: refTooltipIn .15s ease;
}
@keyframes refTooltipIn {
  from { opacity: 0; }
  to { opacity: 1; }
}
.ref-tooltip-arrow {
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
  width: 0; height: 0;
  border: 6px solid transparent;
}
.ref-tooltip-arrow.arrow-down {
  top: 100%;
  border-top-color: #fff;
  filter: drop-shadow(0 2px 2px rgba(0,0,0,0.06));
}
.ref-tooltip-arrow.arrow-up {
  bottom: 100%;
  border-bottom-color: #fff;
  filter: drop-shadow(0 -2px 2px rgba(0,0,0,0.06));
}
.ref-tooltip-head {
  display: flex;
  align-items: center;
  gap: 8px;
  padding-bottom: 6px;
  margin-bottom: 2px;
  border-bottom: 1px solid #f1f5f9;
}
.ref-tooltip-avatar {
  width: 24px;
  height: 24px;
  border-radius: 7px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 800;
  color: #fff;
  background: var(--ref-color, #94a3b8);
}
.ref-tooltip-name {
  font-size: 12px;
  font-weight: 700;
  color: #1e2d55;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.ref-tooltip-row {
  font-size: 11px;
  color: #64748b;
  line-height: 1.5;
}
.ref-tooltip-row strong {
  color: #475569;
  font-weight: 700;
}
.ref-tooltip-estado {
  color: var(--ref-color, #94a3b8);
  font-weight: 700;
}

/* ── Legend tooltips ── */
.legend-tip-wrap { position: relative; }
.legend-tip {
  position: absolute; bottom: calc(100% + 8px); left: 50%; transform: translateX(-50%);
  background: #1e2d55; color: #fff; font-size: 11px; font-weight: 600;
  padding: 6px 12px; border-radius: 8px; white-space: nowrap;
  opacity: 0; pointer-events: none; transition: opacity .25s ease; z-index: 100;
  box-shadow: 0 4px 16px rgba(13,45,107,.3);
  max-width: 280px; white-space: normal; text-align: center; line-height: 1.4;
}
.legend-tip::after {
  content: ''; position: absolute; top: 100%; left: 50%; transform: translateX(-50%);
  border: 5px solid transparent; border-top-color: #1e2d55;
}
.legend-tip-wrap:hover .legend-tip { opacity: 1; }

/* ── Service count badge ── */
.service-count-badge {
  font-size: 9px; font-weight: 700; padding: 1px 6px; border-radius: 999px; margin-left: 4px;
}

/* ── Capacity bar shine ── */
.capacity-bar { position: relative; overflow: hidden; }
.capacity-bar-shine {
  position: absolute; top: 0; left: 0; bottom: 0; width: 40%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
  animation: barShine 2.5s ease-in-out infinite;
}
@keyframes barShine { 0% { left: -40%; } 100% { left: 100%; } }

/* ── Recent count badge ── */
.recent-count-badge {
  font-size: 10px; font-weight: 700; color: #16468e;
  background: #e0ecff; padding: 2px 8px; border-radius: 999px;
}

/* ── Activity cards (horizontal) ── */
.activity-card {
  width: 240px;
  padding: 14px;
  border-radius: 14px;
  background: rgba(248, 250, 252, 0.8);
  border: 1px solid rgba(226, 232, 240, 0.6);
  transition: transform .25s cubic-bezier(.22,1,.36,1), box-shadow .25s ease;
  display: flex;
  flex-direction: column;
}
.activity-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 24px rgba(22, 70, 142, .10);
  background: #fff;
}

/* ── Operations ── */
.operations-grid {
  display: grid;
  grid-template-columns: minmax(0, 4fr) minmax(280px, 2fr);
  gap: .5rem;
  flex: 1;
  min-height: 0;
}
.operations-card,
.flow-card {
  position: relative;
  overflow: hidden;
  border-radius: 1rem;
  display: flex;
  flex-direction: column;
  min-height: 0;
}
.operations-card {
  padding: .65rem;
  background: rgba(255, 255, 255, .92);
  border: 1px solid rgba(212, 222, 234, .65);
  box-shadow: 0 4px 16px rgba(22, 70, 142, .06);
  backdrop-filter: blur(10px);
  transition: transform .35s cubic-bezier(.22,1,.36,1), box-shadow .35s cubic-bezier(.22,1,.36,1);
}
.operations-card-hover:hover {
  transform: translateY(-3px);
  box-shadow: 0 16px 36px rgba(22, 70, 142, .12);
}
.operations-card::before {
  content: '';
  position: absolute;
  inset: 0 0 auto;
  height: 3px;
  background: linear-gradient(90deg, #0D2D6B, #3978cf, #8dbdff);
}
.operations-glow {
  position: absolute;
  right: -55px;
  bottom: -70px;
  width: 180px;
  height: 180px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(126, 179, 255, .16), transparent 70%);
  pointer-events: none;
  animation: orbitFloat 7s ease-in-out infinite;
}
.operations-heading,
.flow-heading {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: .5rem;
}
.operations-hint {
  color: #94a3b8;
  font-size: 10px;
  font-weight: 500;
}
.quick-actions {
  position: relative;
  z-index: 1;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: .5rem;
}
.quick-action {
  --quick-color: #2563c4;
  --quick-bg: #e7efff;
  position: relative;
  display: flex;
  align-items: center;
  min-width: 0;
  gap: .5rem;
  padding: .55rem;
  border: 1px solid rgba(226, 232, 240, .85);
  border-radius: .75rem;
  background: linear-gradient(145deg, #fff, #f8fafc);
  color: inherit;
  text-decoration: none;
  transition: transform .25s cubic-bezier(.22,1,.36,1), border-color .25s ease, box-shadow .25s ease;
}
.quick-action::after {
  content: '';
  position: absolute;
  inset: auto .8rem 0;
  height: 2px;
  border-radius: 999px 999px 0 0;
  background: var(--quick-color);
  opacity: 0;
  transform: scaleX(.4);
  transition: opacity .25s ease, transform .25s ease;
}
.quick-action:hover {
  transform: translateY(-4px);
  border-color: color-mix(in srgb, var(--quick-color) 30%, white);
  box-shadow: 0 12px 25px rgba(22, 70, 142, .11);
}
.quick-action:hover::after {
  opacity: 1;
  transform: scaleX(1);
}
.quick-action-green { --quick-color: #15966a; --quick-bg: #dcfce7; }
.quick-action-purple { --quick-color: #7048e8; --quick-bg: #ede9fe; }
.quick-action-icon {
  width: 30px;
  height: 30px;
  flex: 0 0 auto;
  display: grid;
  place-items: center;
  border-radius: 9px;
  color: var(--quick-color);
  background: var(--quick-bg);
  box-shadow: inset 0 1px 0 rgba(255,255,255,.75);
}
.quick-action-icon svg { width: 16px; height: 16px; }
.quick-action-copy {
  display: flex;
  flex: 1;
  min-width: 0;
  flex-direction: column;
}
.quick-action-copy strong {
  color: #1e2d55;
  font-size: 11px;
  font-weight: 800;
}
.quick-action-copy span {
  overflow: hidden;
  margin-top: 2px;
  color: #8a9ab5;
  font-size: 9px;
  line-height: 1.3;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.quick-action-arrow {
  width: 14px;
  height: 14px;
  flex: 0 0 auto;
  color: #c3ccda;
  transition: color .2s ease, transform .2s ease;
}
.quick-action:hover .quick-action-arrow {
  color: var(--quick-color);
  transform: translateX(3px);
}
.flow-card {
  min-height: 0;
  padding: .65rem;
  background: rgba(255,255,255,0.92);
  border: 1px solid rgba(212, 222, 234, 0.6);
  box-shadow: 0 4px 16px rgba(22, 70, 142, .06);
  backdrop-filter: blur(10px);
  transition: transform .35s cubic-bezier(.22,1,.36,1), box-shadow .35s cubic-bezier(.22,1,.36,1);
}
.flow-card-hover:hover {
  transform: translateY(-3px);
  box-shadow: 0 16px 36px rgba(22, 70, 142, .12);
}
.flow-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: linear-gradient(90deg, #15966a, #4ade80, #86efac);
  border-radius: 1rem 1rem 0 0;
}
.flow-orbit {
  position: absolute;
  border-radius: 50%;
  pointer-events: none;
  animation: orbitFloat 6s ease-in-out infinite;
}
.flow-orbit-one {
  top: -60px;
  right: -40px;
  width: 160px;
  height: 160px;
  background: radial-gradient(circle, rgba(74,222,128,.10), transparent 70%);
  animation-delay: 0s;
}
.flow-orbit-two {
  right: 30px;
  bottom: -70px;
  width: 120px;
  height: 120px;
  background: radial-gradient(circle, rgba(126,179,255,.08), transparent 70%);
  animation-delay: 2s;
}
@keyframes orbitFloat {
  0%, 100% { transform: translate(0, 0) scale(1); opacity: 1; }
  50% { transform: translate(-8px, 6px) scale(1.08); opacity: 0.7; }
}
.flow-content { position: relative; z-index: 1; }
.flow-eyebrow {
  margin: 0;
  color: #8a9ab5;
  font-size: 9px;
  font-weight: 700;
  letter-spacing: .1em;
  text-transform: uppercase;
}
.flow-heading h3 {
  margin: 2px 0 0;
  color: #1e2d55;
  font-size: 14px;
  font-weight: 800;
}
.flow-live {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  padding: .28rem .58rem;
  border: 1px solid rgba(74,222,128,.2);
  border-radius: 999px;
  background: #dcfce7;
  color: #15966a;
  font-size: 9px;
  font-weight: 600;
}
.flow-live i {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #22c55e;
  box-shadow: 0 0 0 3px rgba(74,222,128,.16);
  animation: livePulse 2s ease-in-out infinite;
}
.flow-steps {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: .45rem;
}
.flow-step {
  display: flex;
  min-width: 0;
  align-items: center;
  gap: .5rem;
}
.flow-step-icon {
  width: 28px;
  height: 28px;
  flex: 0 0 auto;
  display: grid;
  place-items: center;
  border-radius: 9px;
  background: #e7efff;
  color: #2563c4;
  box-shadow: inset 0 1px 0 rgba(255,255,255,.75);
  transition: transform .25s ease, box-shadow .25s ease;
}
.flow-step:hover .flow-step-icon {
  transform: scale(1.1);
  box-shadow: 0 6px 16px rgba(37,99,196,.15);
}
.flow-step-icon svg { width: 15px; height: 15px; }
.flow-step-success {
  background: #dcfce7;
  color: #15966a;
}
.flow-step div:last-child {
  display: flex;
  min-width: 0;
  flex-direction: column;
}
.flow-step strong {
  color: #1e2d55;
  font-size: 10px;
  font-weight: 700;
}
.flow-step span {
  overflow: hidden;
  margin-top: 2px;
  color: #8a9ab5;
  font-size: 8px;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.flow-connector {
  position: relative;
  min-width: 18px;
  height: 2px;
  flex: 1;
  overflow: hidden;
  border-radius: 999px;
  background: #e2e8f0;
}
.flow-connector span {
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, transparent, #2563c4, transparent);
  animation: flowMove 2.6s linear infinite;
}
@keyframes flowMove {
  from { transform: translateX(-100%); }
  to { transform: translateX(100%); }
}
@media (max-width: 1100px) {
  .operations-grid { grid-template-columns: 1fr; overflow-y: auto; }
}
@media (max-width: 720px) {
  .quick-actions { grid-template-columns: 1fr; }
  .operations-hint { display: none; }
  .flow-steps { align-items: stretch; flex-direction: column; }
  .flow-connector { width: 1px; min-width: 1px; height: 12px; margin-left: 15px; flex: 0 0 auto; }
}

/* ── Scrollbar ── */
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #c5c9d0; border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }

/* ── Shimmer ── */
.shimmer-box, .shimmer-bar { position: relative; overflow: hidden; background: #e6ebf3; }
.shimmer-box { border-radius: 8px; }
.shimmer-bar { border-radius: 4px; }
.shimmer-box::after, .shimmer-bar::after {
  content: ''; position: absolute; inset: 0;
  background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.6) 50%, transparent 100%);
  animation: shimmer 1.8s ease-in-out infinite;
}
@keyframes shimmer { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }

/* ── Animations ── */
.anim-fade-down { animation: fadeDown .5s ease both; }
@keyframes fadeDown {
  from { opacity: 0; transform: translateY(-12px); }
  to { opacity: 1; transform: none; }
}
.anim-slide-up { animation: slideUp .5s cubic-bezier(.22,1,.36,1) both; }
@keyframes slideUp {
  from { opacity: 0; transform: translateY(14px); }
  to { opacity: 1; transform: none; }
}
</style>
