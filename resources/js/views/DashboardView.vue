<template>
  <div class="h-full flex flex-col gap-2 p-2 sm:p-3 overflow-hidden dashboard-bg">

    <!-- ── Hero + Filtros compacto ── -->
    <div class="hero-card rounded-2xl p-3 sm:p-4 flex items-center gap-4 relative overflow-hidden shrink-0 animate-fade-in-down"
      style="animation-duration: 0.4s; animation-fill-mode: both;">
      <div class="hero-glow"></div>
      <div class="hero-pattern"></div>
      <div class="flex items-center gap-3 z-10 shrink-0">
        <div class="hero-logo">
          <img :src="'/images/logo-w.png'" alt="Logo" class="w-full h-full object-contain" />
        </div>
      </div>
      <div class="flex-1 min-w-0 z-10">
        <div class="flex items-center gap-2 mb-0.5">
          <span class="hero-live-dot"></span>
          <p class="text-[9px] font-semibold uppercase tracking-[0.12em]" style="color:rgba(255,255,255,0.55);">En línea</p>
        </div>
        <h1 class="text-sm sm:text-lg font-bold leading-tight text-white tracking-tight">
          Centro de control
        </h1>
        <p class="text-[10px] sm:text-xs font-semibold mt-0.5" style="color:#7eb3ff;">Clínica Santa Bárbara · {{ today }}</p>
      </div>
      <div class="hero-right z-10 shrink-0 hidden sm:flex items-center gap-3">
        <div class="hero-mini-stats">
          <div class="hero-mini-stat">
            <span class="hero-mini-stat-num">{{ stats.solicitudes.total }}</span>
            <span class="hero-mini-stat-label">Solicitudes</span>
          </div>
          <div class="hero-mini-divider"></div>
          <div class="hero-mini-stat">
            <span class="hero-mini-stat-num">{{ stats.clinicas.activas }}</span>
            <span class="hero-mini-stat-label">Clínicas</span>
          </div>
          <div class="hero-mini-divider"></div>
          <div class="hero-mini-stat">
            <span class="hero-mini-stat-num">{{ stats.usuarios.activos }}</span>
            <span class="hero-mini-stat-label">Usuarios</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Stat cards ── -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 shrink-0 animate-fade-in-up"
      style="animation-duration: 0.4s; animation-delay: 0.1s; animation-fill-mode: both;">
      <div
        v-for="(card, i) in statCards" :key="i"
        class="stat-card rounded-2xl p-3.5 flex items-center gap-3 anim-slide-up"
        :style="{ animationDelay: (i * 0.06) + 's', '--accent': card.color, '--accent-2': card.color2, '--icon-bg': card.iconBg, '--icon-color': card.color, '--delta-bg': card.deltaBg, '--delta-color': card.deltaColor }"
      >
        <div class="stat-card-mesh"></div>
        <div class="stat-card-glow"></div>

        <div class="stat-ring shrink-0" :style="{ '--ring-pct': card.percent }">
          <svg viewBox="0 0 64 64" class="stat-ring-svg">
            <circle cx="32" cy="32" r="27" class="stat-ring-track" />
            <circle cx="32" cy="32" r="27" class="stat-ring-fill" :style="{ strokeDashoffset: 169.6 - (169.6 * card.percent / 100) }" />
          </svg>
          <div class="stat-ring-icon">
            <component :is="card.icon" class="w-[18px] h-[18px]" />
          </div>
        </div>

        <div class="flex-1 min-w-0 relative z-10">
          <p class="stat-value">{{ card.value }}</p>
          <p class="stat-label">{{ card.label }}</p>
          <span class="stat-delta-badge">{{ card.delta }}</span>
        </div>
      </div>
    </div>

    <!-- ── Distribución + Donut ── -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-2 shrink-0 animate-fade-in-up"
      style="animation-duration: 0.4s; animation-delay: 0.18s; animation-fill-mode: both;">
      <div class="distrib-card rounded-xl p-3 flex flex-col lg:col-span-2 distrib-card-hover">
        <div class="flex items-center justify-between mb-1">
          <div class="flex items-center gap-2">
            <div class="chart-header-icon" style="background: linear-gradient(135deg,#eaf4ff,#dbeafe); color:#16468E;">
              <component :is="TrendingUpIcon" class="w-3.5 h-3.5" />
            </div>
            <div>
              <p class="text-xs font-bold" style="color:#1e2d55;">Tendencia de solicitudes</p>
              <p class="text-[10px] mt-0.5" style="color:#8a9ab5;">Últimos 14 días · {{ tendenciaTotal }} solicitudes</p>
            </div>
          </div>
          <span class="trend-badge" :class="tendenciaCambioPct >= 0 ? 'trend-badge-up' : 'trend-badge-down'">
            <component :is="tendenciaCambioPct >= 0 ? TrendingUpIcon : TrendingDownIcon" class="w-3 h-3" />
            {{ tendenciaCambioPct >= 0 ? '+' : '' }}{{ tendenciaCambioPct }}%
          </span>
        </div>
        <div class="flex-1">
          <apexchart
            type="area"
            height="140"
            :options="tendenciaChartOptions"
            :series="tendenciaChartSeries"
          />
        </div>
      </div>

      <div class="distrib-card rounded-xl p-3 flex flex-col distrib-card-hover">
        <div class="flex items-center gap-2 mb-2">
          <div class="donut-header-icon">
            <component :is="PieChartIcon" class="w-3.5 h-3.5" />
          </div>
          <p class="text-xs font-bold" style="color:#1e2d55;">Proporción de estados</p>
          <span class="estado-total-badge">{{ stats.solicitudes.total }} total</span>
        </div>
        <div class="flex-1 w-full flex flex-col justify-center gap-2.5 min-h-0">
          <div v-for="(item, i) in donutBars" :key="item.label"
            class="estado-bar-wrap anim-slide-up"
            :style="{ animationDelay: (i * 0.08) + 's', '--bar-color': item.color, '--bar-color2': item.color2 }">
            <div class="flex items-center justify-between mb-1">
              <div class="flex items-center gap-1.5">
                <span class="estado-bar-dot" :style="{ background: item.color }"></span>
                <span class="estado-bar-label">{{ item.label }}</span>
              </div>
              <span class="estado-bar-value" :style="{ color: item.color }">{{ item.value }}</span>
            </div>
            <div class="estado-bar-track">
              <div class="estado-bar-fill"
                :style="{ width: item.pct + '%', background: `linear-gradient(90deg, ${item.color}, ${item.color2})` }">
                <div class="estado-bar-shine"></div>
              </div>
            </div>
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
              <component :is="BarChart3Icon" class="w-3.5 h-3.5" />
            </div>
            <div>
              <p class="panel-eyebrow">Demanda por servicio</p>
              <h3 class="panel-title">Especialidades más solicitadas</h3>
            </div>
          </div>
          <span class="operations-hint">Top 5 · periodo filtrado</span>
        </div>

        <div class="specialties-chart-wrap">
          <apexchart
            v-if="topEspecialidadesSeries[0]?.data?.length"
            type="bar"
            height="100%"
            :options="topEspecialidadesOptions"
            :series="topEspecialidadesSeries"
          />
          <div v-else class="specialties-empty">
            <component :is="StethoscopeIcon" class="w-6 h-6" />
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
import {
  ArrowRight as ArrowRightIcon,
  CalendarDays as CalendarDaysIcon,
  CheckCircle2 as CheckCircleIcon,
  Clock3 as ClockIcon,
  Stethoscope as StethoscopeIcon,
  Users as UsersIcon,
  Activity as ActivityIcon,
  BedDouble as BedDoubleIcon,
  AlertTriangle as AlertTriangleIcon,
  FileText as FileTextIcon,
  UserCheck as UserCheckIcon,
  FlaskConical as FlaskIcon,
  Building2 as Building2Icon,
  ClipboardList as ClipboardListIcon,
  Hospital as HospitalIcon,
  Filter as FilterIcon,
  X as XIcon,
  TrendingUp as TrendingUpIcon,
  TrendingDown as TrendingDownIcon,
  PieChart as PieChartIcon,
  BarChart3 as BarChart3Icon,
  Target as TargetIcon,
} from '@lucide/vue';
import http from '@/plugins/axios';

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

const today = new Intl.DateTimeFormat('es-CO', {
  weekday: 'long',
  day: 'numeric',
  month: 'long',
}).format(new Date());

const todayShort = new Intl.DateTimeFormat('es-CO', {
  day: '2-digit',
  month: 'short',
}).format(new Date());

const cargando = ref(true);

const stats = ref({
  solicitudes: { total: 0, pendientes: 0, aceptadas: 0, negadas: 0, en_espera: 0, completadas: 0 },
  clinicas: { total: 0, activas: 0, pendientes: 0 },
  usuarios: { total: 0, activos: 0 },
  solicitudes_recientes: [] as Array<{
    id: number; paciente: string; nombre_completo: string; tipo_documento: string; numero_documento: string; telefono_contacto: string; estado: string; especialidad: string; clinica: string | null; created_at: string | null;
  }>,
  filtros: { especialidades: [] as string[], eps: [] as string[] },
  tendencia: [] as Array<{ fecha: string; total: number }>,
  top_especialidades: [] as Array<{ especialidad: string; total: number }>,
  tasa_aceptacion: 0,
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

const statCards = computed(() => [
  {
    label: 'Solicitudes totales',
    value: String(displayStats.value[0]),
    icon: ClipboardListIcon,
    color: '#2563c4',
    color2: '#60a5fa',
    iconBg: '#dbe1ff',
    delta: `${stats.value.solicitudes.pendientes} pend.`,
    deltaBg: '#dbeafe',
    deltaColor: '#2563c4',
    percent: stats.value.solicitudes.total ? Math.round((stats.value.solicitudes.aceptadas / stats.value.solicitudes.total) * 100) : 0,
  },
  {
    label: 'Clínicas registradas',
    value: String(displayStats.value[1]),
    icon: HospitalIcon,
    color: '#15966a',
    color2: '#4ade80',
    iconBg: '#d3f9d8',
    delta: `${stats.value.clinicas.activas} activas`,
    deltaBg: '#dcfce7',
    deltaColor: '#15966a',
    percent: stats.value.clinicas.total ? Math.round((stats.value.clinicas.activas / stats.value.clinicas.total) * 100) : 0,
  },
  {
    label: 'Solicitudes pendientes',
    value: String(displayStats.value[2]),
    icon: ClockIcon,
    color: '#e67700',
    color2: '#fbbf24',
    iconBg: '#fff3cd',
    delta: `${stats.value.solicitudes.aceptadas} aceptadas`,
    deltaBg: '#fef3c7',
    deltaColor: '#e67700',
    percent: stats.value.solicitudes.total ? Math.round((stats.value.solicitudes.pendientes / stats.value.solicitudes.total) * 100) : 0,
  },
  {
    label: 'Usuarios activos',
    value: String(displayStats.value[3]),
    icon: UsersIcon,
    color: '#7048e8',
    color2: '#c4b5fd',
    iconBg: '#ede9fe',
    delta: `${stats.value.usuarios.total} total`,
    deltaBg: '#ede9fe',
    deltaColor: '#7048e8',
    percent: stats.value.usuarios.total ? Math.round((stats.value.usuarios.activos / stats.value.usuarios.total) * 100) : 0,
  },
]);

const donutChartSeries = computed(() => {
  const s = stats.value.solicitudes;
  return [s.pendientes, s.en_espera, s.aceptadas, s.negadas].filter(v => v > 0);
});

const donutChartOptions = computed(() => ({
  chart: {
    type: 'donut' as const,
    fontFamily: 'inherit',
    toolbar: { show: false },
    animations: {
      enabled: true,
      easing: 'easeinout' as const,
      speed: 800,
      animateGradually: { enabled: true, delay: 150 },
      dynamicAnimation: { enabled: true, speed: 400 },
    },
    dropShadow: {
      enabled: true,
      top: 2,
      left: 0,
      blur: 8,
      opacity: 0.15,
    },
  },
  labels: ['Pendientes', 'En espera', 'Aceptadas', 'Negadas'].filter((_, i) => {
    const s = stats.value.solicitudes;
    return [s.pendientes, s.en_espera, s.aceptadas, s.negadas][i] > 0;
  }),
  colors: ['#f59e0b', '#3b82f6', '#10b981', '#ef4444'].filter((_, i) => {
    const s = stats.value.solicitudes;
    return [s.pendientes, s.en_espera, s.aceptadas, s.negadas][i] > 0;
  }),
  fill: {
    type: 'gradient',
    gradient: {
      shade: 'light',
      type: 'vertical',
      shadeIntensity: 0.3,
      gradientToColors: ['#fbbf24', '#60a5fa', '#34d399', '#f87171'],
      inverseColors: false,
      opacityFrom: 1,
      opacityTo: 0.85,
    },
  },
  legend: { show: false },
  dataLabels: {
    enabled: true,
    formatter: (val: number) => `${Math.round(val)}%`,
    style: { fontSize: '11px', fontWeight: 700, colors: ['#fff'] },
    dropShadow: { enabled: false },
  },
  tooltip: {
    y: { formatter: (val: number) => `${val} solicitudes` },
    style: { fontSize: '12px', fontFamily: 'inherit' },
    fillSeriesColor: false,
  },
  plotOptions: {
    pie: {
      donut: {
        size: '65%',
        labels: {
          show: true,
          name: { fontSize: '10px', color: '#8a9ab5', fontWeight: 600 },
          value: { fontSize: '20px', fontWeight: 800, color: '#1e2d55', formatter: (val: string) => val },
          total: {
            show: true,
            label: 'Total',
            fontSize: '9px',
            color: '#8a9ab5',
            fontWeight: 600,
            formatter: () => String(stats.value.solicitudes.total),
          },
        },
      },
    },
  },
  stroke: { width: 2, colors: ['#fff'] },
}));

const donutBars = computed(() => {
  const s = stats.value.solicitudes;
  const total = Math.max(1, s.total);
  return [
    { label: 'Pendientes', value: s.pendientes, color: '#f59e0b', color2: '#fbbf24', pct: Math.round((s.pendientes / total) * 100) },
    { label: 'En espera', value: s.en_espera, color: '#3b82f6', color2: '#60a5fa', pct: Math.round((s.en_espera / total) * 100) },
    { label: 'Aceptadas', value: s.aceptadas, color: '#10b981', color2: '#34d399', pct: Math.round((s.aceptadas / total) * 100) },
    { label: 'Negadas', value: s.negadas, color: '#ef4444', color2: '#f87171', pct: Math.round((s.negadas / total) * 100) },
  ];
});

const tendenciaChartSeries = computed(() => [{
  name: 'Solicitudes',
  data: stats.value.tendencia.map(t => t.total),
}]);

const tendenciaChartOptions = computed(() => ({
  chart: {
    type: 'area' as const,
    fontFamily: 'inherit',
    background: 'transparent',
    toolbar: { show: false },
    zoom: { enabled: false },
    animations: {
      enabled: true,
      easing: 'easeinout' as const,
      speed: 900,
      animateGradually: { enabled: true, delay: 100 },
      dynamicAnimation: { enabled: true, speed: 400 },
    },
    dropShadow: {
      enabled: true,
      top: 6,
      left: 0,
      blur: 6,
      opacity: 0.15,
      color: '#2563c4',
    },
  },
  stroke: { curve: 'smooth' as const, width: 3, colors: ['#2563c4'] },
  colors: ['#2563c4'],
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.45,
      opacityTo: 0.02,
      stops: [0, 90, 100],
      colorStops: [
        { offset: 0, color: '#2563c4', opacity: 0.4 },
        { offset: 100, color: '#2563c4', opacity: 0.02 },
      ],
    },
  },
  markers: {
    size: 0,
    hover: { size: 6 },
    strokeWidth: 2,
    strokeColors: '#fff',
    colors: ['#2563c4'],
  },
  xaxis: {
    categories: stats.value.tendencia.map(t =>
      new Intl.DateTimeFormat('es-CO', { day: '2-digit', month: 'short' }).format(new Date(t.fecha + 'T00:00:00'))
    ),
    labels: { style: { fontSize: '10px', fontWeight: 600, colors: '#94a3b8' } },
    axisBorder: { show: false },
    axisTicks: { show: false },
    tooltip: { enabled: false },
  },
  yaxis: {
    labels: { style: { fontSize: '10px', colors: '#94a3b8' } },
  },
  grid: {
    borderColor: isDark.value ? '#1e293b' : '#f1f5f9',
    strokeDashArray: 4,
    xaxis: { lines: { show: false } },
    padding: { top: 0, right: 8, bottom: 0, left: 0 },
  },
  dataLabels: { enabled: false },
  tooltip: {
    y: { formatter: (val: number) => `${val} solicitud${val === 1 ? '' : 'es'}` },
    style: { fontSize: '12px', fontFamily: 'inherit', background: isDark.value ? '#1e293b' : '#fff' },
    theme: isDark.value ? 'dark' : 'light',
    x: { show: true },
  },
}));

const tendenciaTotal = computed(() => stats.value.tendencia.reduce((acc, t) => acc + t.total, 0));

const tendenciaCambioPct = computed(() => {
  const dias = stats.value.tendencia;
  const mitad = Math.floor(dias.length / 2);
  if (!mitad) return 0;
  const primeraMitad = dias.slice(0, mitad).reduce((acc, t) => acc + t.total, 0);
  const segundaMitad = dias.slice(mitad).reduce((acc, t) => acc + t.total, 0);
  if (primeraMitad === 0) return segundaMitad > 0 ? 100 : 0;
  return Math.round(((segundaMitad - primeraMitad) / primeraMitad) * 100);
});

const topEspecialidadesSeries = computed(() => [{
  name: 'Solicitudes',
  data: stats.value.top_especialidades.map(e => e.total),
}]);

const topEspecialidadesOptions = computed(() => ({
  chart: {
    type: 'bar' as const,
    fontFamily: 'inherit',
    background: 'transparent',
    toolbar: { show: false },
    animations: {
      enabled: true,
      easing: 'easeinout' as const,
      speed: 700,
      animateGradually: { enabled: true, delay: 90 },
      dynamicAnimation: { enabled: true, speed: 350 },
    },
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
  colors: ['#2563c4', '#3b82c9', '#4f9dcf', '#5cb8d1', '#7dd3c0'],
  fill: {
    type: 'gradient',
    gradient: {
      shade: 'light',
      type: 'horizontal',
      shadeIntensity: 0.3,
      gradientToColors: ['#60a5fa'],
      inverseColors: false,
      opacityFrom: 1,
      opacityTo: 0.85,
    },
  },
  xaxis: {
    categories: stats.value.top_especialidades.map(e => e.especialidad),
    labels: { style: { fontSize: '10px', colors: isDark.value ? '#94a3b8' : '#94a3b8' } },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: {
    labels: {
      style: { fontSize: '9px', fontWeight: 600, colors: isDark.value ? '#cbd5e1' : '#475569' },
      maxWidth: 280,
      trim: true,
    },
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
    style: { fontSize: '12px', fontFamily: 'inherit', background: isDark.value ? '#1e293b' : '#fff' },
    theme: isDark.value ? 'dark' : 'light',
  },
  legend: { show: false },
}));

// ── Últimas 5 referencias (bar chart horizontal) ───────────────────────────
const estadoColorMap: Record<string, string> = {
  pendiente: '#f59e0b',
  aceptado: '#22c55e',
  en_espera: '#3b82f6',
  completado: '#6366f1',
  negado: '#ef4444',
};

const estadoGradientMap: Record<string, string[]> = {
  pendiente: ['#fbbf24', '#f59e0b'],
  aceptado: ['#34d399', '#22c55e'],
  en_espera: ['#60a5fa', '#3b82f6'],
  completado: ['#818cf8', '#6366f1'],
  negado: ['#f87171', '#ef4444'],
};

function estadoLabel(estado: string): string {
  return { pendiente: 'Pendiente', aceptado: 'Aceptada', en_espera: 'En espera', completado: 'Completada', negado: 'Negada' }[estado] ?? estado;
}

function estadoLabelShort(estado: string): string {
  return { pendiente: 'Pend', aceptado: 'Acept', en_espera: 'Espera', completado: 'Compl', negado: 'Negada' }[estado] ?? estado;
}

const ultimas5Ref = computed(() => stats.value.solicitudes_recientes.slice(0, 5));

const ultimasRefSeries = computed(() => [{
  name: 'Referencias',
  data: ultimas5Ref.value.map(() => 1),
}]);

const ultimasRefOptions = computed(() => ({
  chart: {
    type: 'bar' as const,
    fontFamily: 'inherit',
    background: 'transparent',
    toolbar: { show: false },
    animations: {
      enabled: true,
      easing: 'easeinout' as const,
      speed: 800,
      animateGradually: { enabled: true, delay: 60 },
      dynamicAnimation: { enabled: true, speed: 350 },
    },
    events: {
      click: () => { router.push('/solicitudes-referencia'); },
    },
    dropShadow: {
      enabled: true,
      top: 2,
      left: 0,
      blur: 6,
      opacity: 0.12,
    },
  },
  plotOptions: {
    bar: {
      horizontal: true,
      barHeight: '68%',
      borderRadius: 10,
      borderRadiusApplication: 'end' as const,
      distributed: true,
    },
  },
  fill: {
    type: 'solid',
    opacity: 1,
  },
  colors: ultimas5Ref.value.map(s => estadoColorMap[s.estado] ?? '#94a3b8'),
  stroke: {
    show: true,
    width: 0,
  },
  dataLabels: {
    enabled: true,
    textAnchor: 'start' as const,
    offsetX: 14,
    style: { fontSize: '10px', fontWeight: 700, colors: ['#fff'] },
    formatter: (_val: number, opts: any) => {
      const s = ultimas5Ref.value[opts.dataPointIndex];
      if (!s) return '';
      return estadoLabelShort(s.estado);
    },
    dropShadow: { enabled: false },
  },
  xaxis: {
    categories: ultimas5Ref.value.map(s => s.paciente),
    labels: { show: false },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: {
    show: true,
    labels: {
      show: true,
      align: 'right' as const,
      minWidth: 0,
      maxWidth: 160,
      style: {
        fontSize: '11px',
        fontWeight: 600,
        colors: isDark.value ? ['#cbd5e1'] : ['#334155'],
      },
      formatter: (_val: number, index: number) => {
        const s = ultimas5Ref.value[index];
        if (!s) return '';
        const nombre = s.paciente;
        return nombre.length > 18 ? nombre.slice(0, 18) + '…' : nombre;
      },
    },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  grid: { show: false, padding: { left: 0, right: 0, top: -6, bottom: -6 } },
  legend: { show: false },
  tooltip: {
    enabled: true,
    custom: ({ dataPointIndex }: { dataPointIndex: number }) => {
      const s = ultimas5Ref.value[dataPointIndex];
      if (!s) return '';
      const nombre = s.nombre_completo || s.paciente;
      const doc = `${s.tipo_documento ?? ''} ${s.numero_documento ?? ''}`.trim() || '—';
      const tel = s.telefono_contacto || '—';
      const estado = estadoLabel(s.estado);
      const estadoShort = estadoLabelShort(s.estado);
      const color = estadoColorMap[s.estado] ?? '#94a3b8';
      const grad = estadoGradientMap[s.estado] ?? ['#94a3b8', '#94a3b8'];
      return `
        <div style="padding:0; border-radius:12px; font-family:inherit; min-width:230px; overflow:hidden; box-shadow:0 8px 28px rgba(0,0,0,0.16); border:1px solid ${isDark.value ? '#334155' : '#e2e8f0'}; background:${isDark.value ? '#1e293b' : '#fff'};">
          <div style="background:linear-gradient(135deg, ${grad[0]}, ${grad[1]}); padding:10px 14px; display:flex; align-items:center; gap:8px;">
            <div style="width:28px; height:28px; border-radius:8px; background:rgba(255,255,255,0.22); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <div style="flex:1; min-width:0;">
              <p style="font-size:12px; font-weight:800; color:#fff; margin:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${nombre}</p>
              <p style="font-size:9px; font-weight:600; color:rgba(255,255,255,0.75); margin:2px 0 0; text-transform:uppercase; letter-spacing:0.05em;">${estadoShort}</p>
            </div>
          </div>
          <div style="padding:10px 14px;">
            <div style="display:flex; align-items:center; gap:8px; padding:5px 0; border-bottom:1px solid ${isDark.value ? '#334155' : '#f1f5f9'};">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="${isDark.value ? '#94a3b8' : '#64748b'}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><rect width="18" height="14" x="3" y="5" rx="2"/><path d="M3 10h18"/></svg>
              <span style="font-size:11px; color:${isDark.value ? '#94a3b8' : '#64748b'};"><strong style="color:${isDark.value ? '#cbd5e1' : '#475569'};">Doc:</strong> ${doc}</span>
            </div>
            <div style="display:flex; align-items:center; gap:8px; padding:5px 0; border-bottom:1px solid ${isDark.value ? '#334155' : '#f1f5f9'};">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="${isDark.value ? '#94a3b8' : '#64748b'}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
              <span style="font-size:11px; color:${isDark.value ? '#94a3b8' : '#64748b'};"><strong style="color:${isDark.value ? '#cbd5e1' : '#475569'};">Tel:</strong> ${tel}</span>
            </div>
            <div style="display:flex; align-items:center; gap:8px; padding:5px 0;">
              <div style="width:13px; height:13px; border-radius:50%; background:${color}; flex-shrink:0; box-shadow:0 0 0 3px ${color}25;"></div>
              <span style="font-size:11px; color:${isDark.value ? '#94a3b8' : '#64748b'};"><strong style="color:${isDark.value ? '#cbd5e1' : '#475569'};">Estado:</strong> <span style="color:${color}; font-weight:700;">${estado}</span></span>
            </div>
          </div>
        </div>
      `;
    },
  },
  states: {
    hover: { filter: { type: 'darken' as const, value: 0.88 } },
    active: { filter: { type: 'darken' as const, value: 0.82 } },
  },
}));

const services = computed(() => {
  const total = Math.max(1, stats.value.solicitudes.total);
  const pctPend = Math.round((stats.value.solicitudes.pendientes / total) * 100);
  const pctAcept = Math.round((stats.value.solicitudes.aceptadas / total) * 100);
  const pctNeg = Math.round((stats.value.solicitudes.negadas / total) * 100);
  return [
    { name: 'Pendientes', value: pctPend, count: stats.value.solicitudes.pendientes, color: 'linear-gradient(90deg, #e67700, #ffa94d)', solid: '#e67700', tag: stats.value.solicitudes.pendientes > 0 ? 'En espera' : 'Sin pendientes', tagBg: '#fef3c7', tagColor: '#e67700' },
    { name: 'Aceptadas', value: pctAcept, count: stats.value.solicitudes.aceptadas, color: 'linear-gradient(90deg, #15966a, #5ac996)', solid: '#15966a', tag: 'Aprobadas', tagBg: '#dcfce7', tagColor: '#15966a' },
    { name: 'Negadas', value: pctNeg, count: stats.value.solicitudes.negadas, color: 'linear-gradient(90deg, #c92a2a, #ff8787)', solid: '#c92a2a', tag: 'Rechazadas', tagBg: '#fee2e2', tagColor: '#c92a2a' },
  ];
});

const activities = computed(() => {
  const recientes = stats.value.solicitudes_recientes;
  return recientes.map(s => {
    const tone = s.estado === 'aceptado' ? 'activity-success' : s.estado === 'negado' ? 'activity-warning' : 'activity-primary';
    const icon = s.estado === 'aceptado' ? CheckCircleIcon : s.estado === 'negado' ? AlertTriangleIcon : FileTextIcon;
    const estadoLabel = s.estado === 'aceptado' ? 'aceptada' : s.estado === 'negado' ? 'negada' : 'recibida';
    return {
      title: `Solicitud de referencia ${estadoLabel}`,
      description: `${s.paciente} · ${s.especialidad} · ${s.clinica ?? '—'}`,
      time: s.created_at ? timeAgo(s.created_at) : '',
      icon,
      tone,
    };
  });
});

function timeAgo(iso: string): string {
  const diff = Date.now() - new Date(iso).getTime();
  const mins = Math.floor(diff / 60000);
  if (mins < 1) return 'Hace un momento';
  if (mins < 60) return `Hace ${mins} min`;
  const hours = Math.floor(mins / 60);
  if (hours < 24) return `Hace ${hours} h`;
  const days = Math.floor(hours / 24);
  return `Hace ${days} d`;
}

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
    animateCounters([
      stats.value.solicitudes.total,
      stats.value.clinicas.total,
      stats.value.solicitudes.pendientes,
      stats.value.usuarios.activos,
    ]);
  } catch {
    // silent fail
  } finally {
    cargando.value = false;
  }
}

onMounted(cargarStats);
</script>

<style scoped>
/* ── Background ── */
.dashboard-bg {
  background:
    radial-gradient(ellipse at 90% 0%, rgba(188, 218, 255, 0.35), transparent 30rem),
    radial-gradient(ellipse at 10% 100%, rgba(208, 242, 226, 0.25), transparent 28rem);
}

/* ── Filter bar ── */
.filter-bar {
  background: linear-gradient(135deg, #f0f6ff 0%, #e6efff 50%, #f0f9ff 100%);
  border: 1px solid #b8c8e0;
  box-shadow: 0 4px 20px rgba(13, 45, 107, 0.08), inset 0 1px 0 rgba(255,255,255,0.6);
  position: relative;
  overflow: hidden;
  transition: box-shadow .3s ease, transform .3s ease;
}
.filter-bar:hover {
  box-shadow: 0 6px 28px rgba(13, 45, 107, 0.12), inset 0 1px 0 rgba(255,255,255,0.6);
  transform: translateY(-1px);
}
.filter-bar::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: linear-gradient(90deg, #0D2D6B, #16468E, #2563eb, #16468E, #0D2D6B);
  background-size: 200% 100%;
  animation: filterBarShimmer 3s linear infinite;
}
@keyframes filterBarShimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
.filter-bar-icon {
  width: 32px; height: 32px;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  background: linear-gradient(135deg, #0D2D6B, #16468E);
  color: #fff;
  box-shadow: 0 3px 10px rgba(13, 45, 107, 0.25);
  animation: filterIconPulse 2.5s ease-in-out infinite;
}
@keyframes filterIconPulse {
  0%, 100% { box-shadow: 0 3px 10px rgba(13, 45, 107, 0.25); }
  50% { box-shadow: 0 3px 18px rgba(13, 45, 107, 0.40); }
}
.filter-bar-title {
  font-size: 13px;
  font-weight: 800;
  color: #0D2D6B;
  letter-spacing: 0.02em;
  white-space: nowrap;
}
.filter-divider {
  width: 1px; height: 24px;
  background: linear-gradient(180deg, transparent, #b8c8e0, transparent);
}
.filter-arrow {
  font-size: 14px;
  font-weight: 700;
  color: #94a3b8;
  animation: filterArrowBounce 2s ease-in-out infinite;
}
@keyframes filterArrowBounce {
  0%, 100% { transform: translateX(0); opacity: 0.5; }
  50% { transform: translateX(3px); opacity: 1; }
}
.filter-picker {
  width: 140px !important;
}
.filter-select {
  width: 150px !important;
}
.filter-bar :deep(.el-input__wrapper),
.filter-bar :deep(.el-select__wrapper) {
  background: rgba(255, 255, 255, 0.7) !important;
  border: 1px solid #d4deea !important;
  border-radius: 10px !important;
  transition: all .2s ease;
}
.filter-bar :deep(.el-input__wrapper:hover),
.filter-bar :deep(.el-select__wrapper:hover) {
  border-color: #16468E !important;
  box-shadow: 0 0 0 2px rgba(22, 70, 142, 0.08) !important;
}

/* ── Hero ── */
.hero-card {
  background: linear-gradient(135deg, #0a1f4d 0%, #0D2D6B 30%, #16468E 65%, #1e3a7a 100%);
  box-shadow: 0 10px 40px rgba(13, 45, 107, .35), inset 0 1px 0 rgba(255,255,255,0.08);
}
.hero-glow {
  position: absolute;
  top: -80px; right: -80px;
  width: 280px; height: 280px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(126,179,255,0.22), transparent 70%);
  pointer-events: none;
  animation: heroGlowFloat 8s ease-in-out infinite;
}
@keyframes heroGlowFloat {
  0%, 100% { transform: translate(0, 0) scale(1); opacity: 1; }
  50% { transform: translate(-12px, 8px) scale(1.1); opacity: 0.7; }
}
.hero-glow-2 {
  position: absolute;
  bottom: -60px; left: 30%;
  width: 200px; height: 200px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(74,222,128,0.08), transparent 70%);
  pointer-events: none;
}
.hero-pattern {
  position: absolute;
  inset: 0;
  background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.05) 1px, transparent 0);
  background-size: 24px 24px;
  pointer-events: none;
}
.hero-logo {
  width: 40px; height: 40px;
  border-radius: 12px;
  background: rgba(255,255,255,0.08);
  border: 1px solid rgba(255,255,255,0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 6px;
  backdrop-filter: blur(8px);
}
.hero-date-pill {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  padding: .4rem .9rem;
  border-radius: 999px;
  background: rgba(255,255,255,0.1);
  border: 1px solid rgba(255,255,255,0.12);
  color: #fff;
  font-size: 11px;
  font-weight: 600;
  white-space: nowrap;
}
.hero-mini-stats {
  display: flex;
  align-items: center;
  gap: .75rem;
  padding: .5rem .9rem;
  border-radius: 12px;
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.08);
}
.hero-mini-stat {
  display: flex;
  flex-direction: column;
  align-items: center;
}
.hero-mini-stat-num {
  font-size: 14px;
  font-weight: 800;
  color: #fff;
  line-height: 1;
  transition: transform .3s ease;
}
.hero-mini-stats:hover .hero-mini-stat-num {
  transform: scale(1.1);
}
.hero-mini-stat-label {
  font-size: 9px;
  color: rgba(255,255,255,0.5);
  margin-top: 3px;
  text-transform: uppercase;
  letter-spacing: .05em;
}
.hero-mini-divider {
  width: 1px; height: 24px;
  background: rgba(255,255,255,0.12);
}
.hero-live-dot {
  width: 7px; height: 7px;
  border-radius: 999px;
  background: #4ade80;
  box-shadow: 0 0 0 3px rgba(74, 222, 128, .25);
  animation: livePulse 2s ease-in-out infinite;
}
@keyframes livePulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: .5; transform: scale(.85); }
}
.hero-pill {
  position: absolute;
  border-radius: 999px;
  pointer-events: none;
  animation: floatPill 6s ease-in-out infinite;
}
@keyframes floatPill {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

/* ── Stat cards ── */
.stat-card {
  background: linear-gradient(145deg, #ffffff 0%, #fbfdff 100%);
  border: 1px solid color-mix(in srgb, var(--accent) 22%, #e2e8f0);
  box-shadow: 0 8px 24px color-mix(in srgb, var(--accent) 10%, transparent), 0 1px 0 rgba(255,255,255,0.8) inset;
  transition: transform .35s cubic-bezier(.22,1,.36,1), box-shadow .35s cubic-bezier(.22,1,.36,1), border-color .3s ease;
  position: relative;
  overflow: hidden;
}
.stat-card-mesh {
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at 100% 0%, color-mix(in srgb, var(--accent) 16%, transparent), transparent 55%);
  pointer-events: none;
}
.stat-card-glow {
  position: absolute;
  top: -50%; right: -30%;
  width: 140px; height: 140px;
  border-radius: 50%;
  background: radial-gradient(circle, color-mix(in srgb, var(--accent-2) 45%, transparent), transparent 70%);
  filter: blur(20px);
  opacity: .7;
  transition: opacity .3s ease, transform .5s ease;
  pointer-events: none;
}
.stat-card:hover {
  transform: translateY(-6px) scale(1.015);
  box-shadow: 0 20px 40px color-mix(in srgb, var(--accent) 24%, transparent);
  border-color: color-mix(in srgb, var(--accent) 45%, #e2e8f0);
}
.stat-card:hover .stat-card-glow {
  opacity: 1;
  transform: scale(1.15);
}

/* Circular progress ring */
.stat-ring {
  position: relative;
  width: 58px; height: 58px;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
}
.stat-ring-svg {
  position: absolute;
  inset: 0;
  width: 100%; height: 100%;
  transform: rotate(-90deg);
}
.stat-ring-track {
  fill: none;
  stroke: color-mix(in srgb, var(--accent) 14%, #eef2f7);
  stroke-width: 5;
}
.stat-ring-fill {
  fill: none;
  stroke: var(--accent);
  stroke-width: 5;
  stroke-linecap: round;
  stroke-dasharray: 169.6;
  stroke-dashoffset: 169.6;
  filter: drop-shadow(0 0 4px color-mix(in srgb, var(--accent) 55%, transparent));
  transition: stroke-dashoffset 1.1s cubic-bezier(.22,1,.36,1);
}
.stat-ring-icon {
  position: relative;
  z-index: 1;
  width: 34px; height: 34px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--icon-bg);
  color: var(--icon-color);
  box-shadow: 0 3px 10px color-mix(in srgb, var(--icon-color) 25%, transparent);
  transition: transform .3s cubic-bezier(.22,1,.36,1);
}
.stat-card:hover .stat-ring-icon {
  transform: scale(1.12) rotate(-6deg);
}

.stat-delta-badge {
  display: inline-block;
  font-size: 9.5px;
  font-weight: 800;
  padding: 3px 9px;
  border-radius: 999px;
  white-space: nowrap;
  background: var(--delta-bg);
  color: var(--delta-color);
  margin-top: 4px;
  position: relative;
  z-index: 10;
}
.stat-value {
  font-size: 22px;
  font-weight: 900;
  line-height: 1.1;
  letter-spacing: -.03em;
  color: #1e293b;
  position: relative;
  z-index: 10;
}
.stat-label {
  font-size: 10.5px;
  font-weight: 700;
  color: #64748b;
  margin-top: 1px;
  text-transform: uppercase;
  letter-spacing: .04em;
  position: relative;
  z-index: 10;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

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

/* ── Chart header icon ── */
.chart-header-icon {
  width: 24px; height: 24px;
  border-radius: 7px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
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

/* ── Trend badge ── */
.trend-badge {
  display: inline-flex;
  align-items: center;
  gap: .25rem;
  padding: .3rem .6rem;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 800;
  white-space: nowrap;
}
.trend-badge-up { background: #dcfce7; color: #15966a; }
.trend-badge-down { background: #fee2e2; color: #c92a2a; }

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
