<template>
  <div class="h-full flex flex-col gap-2.5 p-2 sm:p-3 overflow-y-auto lg:overflow-hidden dashboard-bg">

    <!-- ── Saludo + filtros ── -->
    <div class="flex items-center justify-between gap-3 flex-wrap shrink-0">
      <div>
        <h1 class="dash-title">¡Hola, {{ nombreSaludo }}!</h1>
        <p class="dash-subtitle">Aquí tienes el resumen actual del sistema de referencias.</p>
      </div>
      <div class="flex items-center gap-2 flex-wrap">
        <DateRangeFilter v-model="rangoFechas" @change="aplicarFiltros" />
        <el-select v-model="filtros.clinica" size="small" style="width:170px" clearable placeholder="Todas las clínicas" @change="aplicarFiltros">
          <el-option v-for="c in clinicasOpciones" :key="c.id" :value="c.id" :label="c.nombre" />
        </el-select>
        <el-select v-model="filtros.especialidad" size="small" style="width:165px" clearable placeholder="Todas las especialidades" @change="aplicarFiltros">
          <el-option v-for="esp in especialidadesOpciones" :key="esp" :value="esp" :label="esp" />
        </el-select>
        <el-select v-model="filtros.estado" size="small" style="width:135px" @change="aplicarFiltros">
          <el-option value="todas" label="Todos los estados" />
          <el-option value="pendiente" label="Pendientes" />
          <el-option value="en_espera" label="En espera" />
          <el-option value="completado" label="Completadas" />
          <el-option value="negado" label="Negadas" />
        </el-select>
        <button class="dash-clear-btn" @click="limpiarFiltros">
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

    <!-- ── Distribución por estado + Solicitudes por día + Top clínicas ── -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-2.5 lg:flex-1 lg:min-h-0 animate-fade-in-up"
      style="animation-duration: 0.4s; animation-delay: 0.18s; animation-fill-mode: both;">

      <div class="panel-card-ui p-3.5 flex flex-col">
        <div class="panel-head">
          <div class="panel-head-icon">
            <component :is="PieChartIcon" class="w-3.5 h-3.5" />
          </div>
          <p class="panel-head-title">Distribución por estado</p>
          <span class="panel-head-pill">Total</span>
        </div>
        <div class="flex-1 flex items-center gap-3 min-h-0">
          <div class="estado-donut-wrap">
            <apexchart type="donut" height="112" width="112" :options="estadoDonutOptions" :series="estadoDonutSeries" />
            <div class="estado-donut-center">
              <span class="estado-donut-num">{{ stats.solicitudes.total }}</span>
              <span class="estado-donut-label">Solicitudes</span>
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

      <div class="panel-card-ui p-3.5 flex flex-col lg:col-span-2">
        <div class="panel-head">
          <div class="panel-head-icon">
            <component :is="BarChart3Icon" class="w-3.5 h-3.5" />
          </div>
          <p class="panel-head-title">Solicitudes por día</p>
          <span class="panel-head-pill">Últimos 14 días</span>
        </div>
        <div class="flex-1 min-h-0">
          <apexchart type="bar" height="100%" :options="diaChartOptions" :series="diaChartSeries" />
        </div>
      </div>

      <div class="panel-card-ui p-3.5 flex flex-col">
        <div class="panel-head">
          <div class="panel-head-icon">
            <component :is="HospitalIcon" class="w-3.5 h-3.5" />
          </div>
          <p class="panel-head-title">Top clínicas emisoras</p>
          <span class="panel-head-pill">Más solicitudes</span>
        </div>
        <div class="flex-1 w-full flex flex-col gap-2 min-h-0 overflow-hidden">
          <div v-for="(item, i) in clinicaTop" :key="item.clinica" class="top-row">
            <span class="top-rank" :class="`top-rank-${i + 1}`">{{ i + 1 }}</span>
            <div class="flex-1 min-w-0">
              <div class="flex items-baseline justify-between gap-2">
                <span class="top-name" :title="item.clinica">{{ item.clinica }}</span>
                <span class="top-value">{{ item.total }}</span>
              </div>
              <div class="top-track">
                <div class="top-fill" :style="{ width: item.pct + '%' }"></div>
              </div>
            </div>
          </div>
          <div v-if="!clinicaTop.length" class="panel-empty">
            <component :is="HospitalIcon" class="w-6 h-6" />
            <span>Sin datos de clínicas</span>
          </div>
        </div>
        <router-link to="/clinicas" class="panel-link">
          Ver todas las clínicas
          <component :is="ArrowRightIcon" class="w-3.5 h-3.5" />
        </router-link>
      </div>
    </div>

    <!-- ── Especialidad + SLA + Actividad ── -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-2.5 lg:flex-1 lg:min-h-0 animate-fade-in-up"
      style="animation-duration: 0.4s; animation-delay: 0.25s; animation-fill-mode: both;">

      <div class="panel-card-ui p-3.5 flex flex-col">
        <div class="panel-head">
          <div class="panel-head-icon">
            <component :is="StethoscopeIcon" class="w-3.5 h-3.5" />
          </div>
          <p class="panel-head-title">Solicitudes por especialidad</p>
        </div>
        <div class="flex-1 w-full flex flex-col gap-2 min-h-0 overflow-hidden py-0.5">
          <div v-for="(item, i) in especialidadBars" :key="item.especialidad" class="esp-row">
            <span class="esp-icon" :style="{ background: item.tint, color: item.color }">
              <component :is="item.icon" class="w-3.5 h-3.5" />
            </span>
            <div class="flex-1 min-w-0">
              <div class="flex items-baseline justify-between gap-2">
                <span class="esp-name" :title="item.especialidad">{{ item.especialidad }}</span>
                <span class="esp-value" :style="{ color: item.color }">{{ item.total }}</span>
              </div>
              <div class="esp-track">
                <div class="esp-fill" :style="{ width: item.pct + '%', background: `linear-gradient(90deg, ${item.color}, ${item.color}cc)` }"></div>
              </div>
            </div>
          </div>
          <div v-if="!especialidadBars.length" class="panel-empty">
            <component :is="StethoscopeIcon" class="w-6 h-6" />
            <span>Sin datos de especialidades</span>
          </div>
        </div>
      </div>

      <div class="panel-card-ui p-3.5 flex flex-col">
        <div class="panel-head">
          <div class="panel-head-icon">
            <component :is="TimerIcon" class="w-3.5 h-3.5" />
          </div>
          <p class="panel-head-title">Tiempo de respuesta (SLA)</p>
          <span class="panel-head-pill">Histórico</span>
        </div>
        <div class="flex-1 flex items-center gap-3 min-h-0 overflow-hidden">
          <div class="sla-donut-wrap">
            <apexchart type="donut" height="112" width="112" :options="slaDonutOptions" :series="slaDonutSeries" />
            <div class="estado-donut-center">
              <span class="sla-donut-num">{{ stats.sla.dentro_pct }}%</span>
              <span class="estado-donut-label">Dentro del SLA</span>
            </div>
          </div>
          <div class="flex-1 min-w-0 flex flex-col gap-1.5">
            <div v-for="(banda, i) in stats.sla.bandas" :key="banda.etiqueta" class="estado-legend-row">
              <span class="estado-legend-dot" :style="{ background: slaColors[i] }"></span>
              <span class="estado-legend-label">{{ banda.etiqueta }}</span>
              <span class="estado-legend-value" :style="{ color: slaColors[i] }">{{ banda.total }}</span>
              <span class="estado-legend-pct">{{ banda.pct }}%</span>
            </div>
          </div>
        </div>
      </div>

      <div class="panel-card-ui p-3.5 flex flex-col">
        <div class="panel-head">
          <div class="panel-head-icon">
            <component :is="ActivityIcon" class="w-3.5 h-3.5" />
          </div>
          <p class="panel-head-title">Actividad reciente</p>
          <router-link to="/historico" class="panel-link-sm">Ver todo</router-link>
        </div>
        <div class="flex-1 w-full flex flex-col gap-1 min-h-0 overflow-y-auto activity-list">
          <div v-for="(ev, i) in stats.actividad" :key="i" class="activity-row">
            <span class="activity-icon" :style="{ background: actividadTono(ev.tipo).bg, color: actividadTono(ev.tipo).color }">
              <component :is="actividadTono(ev.tipo).icon" class="w-3.5 h-3.5" />
            </span>
            <div class="flex-1 min-w-0">
              <p class="activity-title">{{ ev.titulo }}</p>
              <p class="activity-detail" :title="ev.detalle">{{ ev.detalle }}</p>
            </div>
            <span class="activity-time">{{ haceTexto(ev.at) }}</span>
          </div>
          <div v-if="!stats.actividad.length" class="panel-empty">
            <component :is="ActivityIcon" class="w-6 h-6" />
            <span>Sin actividad reciente</span>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useLayoutStore } from '@/stores/layout';
import { useAuthStore } from '@/stores/auth';
import { usePolling } from '@/lib/usePolling';
import {
  ArrowRight as ArrowRightIcon,
  Clock3 as ClockIcon,
  Users as UsersIcon,
  ClipboardList as ClipboardListIcon,
  Hospital as HospitalIcon,
  PieChart as PieChartIcon,
  BarChart3 as BarChart3Icon,
  Stethoscope as StethoscopeIcon,
  Timer as TimerIcon,
  Activity as ActivityIcon,
  FilePlus2 as FilePlusIcon,
  CheckCircle2 as CheckCircleIcon,
  XCircle as XCircleIcon,
  UserPlus as UserPlusIcon,
  Brain as BrainIcon,
  Heart as HeartIcon,
  Bone as BoneIcon,
  Ribbon as RibbonIcon,
  BedDouble as BedDoubleIcon,
  MoreHorizontal as MoreIcon,
  X as XIcon,
  AlertTriangle as AlertTriangleIcon,
} from '@lucide/vue';
import http from '@/plugins/axios';
import StatCard, { type StatCardTone } from '@/components/ui/StatCard.vue';
import DateRangeFilter from '@/components/ui/DateRangeFilter.vue';

const router = useRouter();
const layout = useLayoutStore();
const auth = useAuthStore();
const isDark = computed(() => layout.isDarkMode);

const nombreSaludo = computed(() => auth.user?.first_name || auth.user?.full_name?.split(' ')[0] || 'usuario');

const cargando = ref(true);
const primeraCarga = ref(true);
const errorCarga = ref(false);

type ActividadEvento = { tipo: string; titulo: string; detalle: string; at: string | null };

const stats = ref({
  solicitudes: { total: 0, pendientes: 0, negadas: 0, en_espera: 0, completadas: 0 },
  clinicas: { total: 0, activas: 0, pendientes: 0 },
  usuarios: { total: 0, activos: 0 },
  solicitudes_recientes: [] as any[],
  filtros: { especialidades: [] as string[], eps: [] as string[], clinicas: [] as Array<{ id: number; nombre: string }> },
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
  sla: { bandas: [] as Array<{ etiqueta: string; total: number; pct: number }>, dentro_pct: 0, total: 0 },
  actividad: [] as ActividadEvento[],
});

const filtros = ref<{ desde: string; hasta: string; estado: string; especialidad: string; clinica: number | '' }>({
  desde: '',
  hasta: '',
  estado: 'todas',
  especialidad: '',
  clinica: '',
});

const especialidadesOpciones = computed(() => stats.value.filtros?.especialidades ?? []);
const clinicasOpciones = computed(() => stats.value.filtros?.clinicas ?? []);

const rangoFechas = computed<[string, string] | null>({
  get: (): [string, string] | null => (filtros.value.desde && filtros.value.hasta) ? [filtros.value.desde, filtros.value.hasta] : null,
  set: (val: [string, string] | null) => {
    filtros.value.desde = val?.[0] ?? '';
    filtros.value.hasta = val?.[1] ?? '';
  },
});

function limpiarFiltros() {
  filtros.value = { desde: '', hasta: '', estado: 'todas', especialidad: '', clinica: '' };
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
    label: 'Pendientes de gestión',
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
    { label: 'Completadas', value: s.completadas, color: '#22c55e', pct: Math.round((s.completadas / total) * 100) },
    { label: 'En espera', value: s.en_espera, color: '#3b82f6', pct: Math.round((s.en_espera / total) * 100) },
    { label: 'Pendientes', value: s.pendientes, color: '#f59e0b', pct: Math.round((s.pendientes / total) * 100) },
    { label: 'Negadas', value: s.negadas, color: '#ef4444', pct: Math.round((s.negadas / total) * 100) },
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
    // Fijo en vez de seguir el cursor — en un donut chico el tooltip
    // "flotante" se salía de la tarjeta y quedaba recortado.
    fixed: { enabled: true, position: 'topRight' },
  },
}));

/** Barras verticales: solicitudes creadas por día, últimos 14 días. */
const diaChartSeries = computed(() => [{
  name: 'Solicitudes',
  data: stats.value.tendencia.map(t => t.total),
}]);
const diaChartOptions = computed(() => ({
  chart: { type: 'bar' as const, fontFamily: 'inherit', toolbar: { show: false }, animations: { enabled: true, speed: 600 } },
  plotOptions: {
    bar: {
      columnWidth: '58%',
      borderRadius: 4,
      borderRadiusApplication: 'end' as const,
      distributed: false,
    },
  },
  colors: ['#5b8def'],
  fill: {
    type: 'gradient',
    gradient: { shade: 'light', type: 'vertical', gradientToColors: ['#3b63d8'], opacityFrom: 0.95, opacityTo: 0.85, stops: [0, 100] },
  },
  dataLabels: { enabled: false },
  xaxis: {
    categories: stats.value.tendencia.map(t => {
      const d = new Date(t.fecha + 'T00:00:00');
      return d.toLocaleDateString('es-CO', { day: '2-digit', month: 'short' }).replace('.', '');
    }),
    labels: { style: { fontSize: '9px', colors: '#94a3b8' }, rotate: -45 },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: { labels: { style: { fontSize: '9px', colors: '#94a3b8' } } },
  grid: {
    borderColor: isDark.value ? '#1e293b' : '#f1f5f9',
    strokeDashArray: 4,
    padding: { top: 0, right: 0, bottom: 0, left: 4 },
  },
  tooltip: {
    theme: isDark.value ? 'dark' : 'light',
    y: { formatter: (val: number) => `${val} solicitudes` },
  },
  legend: { show: false },
}));

/** Top 5 clínicas emisoras con barra proporcional. */
const clinicaTop = computed(() => {
  const filas = stats.value.por_clinica.slice(0, 5);
  const max = Math.max(1, ...filas.map(f => f.total));
  return filas.map(f => ({ ...f, pct: Math.round((f.total / max) * 100) }));
});

/** Top 7 especialidades con color propio por fila, como en el mockup. */
const ESP_PALETA = [
  { color: '#4f46e5', tint: '#e0e7ff', icon: StethoscopeIcon },
  { color: '#2563eb', tint: '#dbeafe', icon: HeartIcon },
  { color: '#0ea5e9', tint: '#e0f2fe', icon: BrainIcon },
  { color: '#10b981', tint: '#d1fae5', icon: BoneIcon },
  { color: '#8b5cf6', tint: '#ede9fe', icon: RibbonIcon },
  { color: '#f59e0b', tint: '#fef3c7', icon: BedDoubleIcon },
  { color: '#ec4899', tint: '#fce7f3', icon: MoreIcon },
];
const especialidadBars = computed(() => {
  const filas = stats.value.top_especialidades.slice(0, 5);
  const max = Math.max(1, ...filas.map(f => f.total));
  return filas.map((f, i) => ({
    ...f,
    pct: Math.round((f.total / max) * 100),
    ...ESP_PALETA[i % ESP_PALETA.length],
  }));
});

/** Dona SLA: ≤2h verde, 2–4h ámbar, >4h rojo. */
const slaColors = ['#22c55e', '#f59e0b', '#ef4444'];
const slaDonutSeries = computed(() => stats.value.sla.bandas.map(b => b.total));
const slaDonutOptions = computed(() => ({
  chart: { type: 'donut' as const, fontFamily: 'inherit', animations: { enabled: true, speed: 700 }, sparkline: { enabled: true } },
  labels: stats.value.sla.bandas.map(b => b.etiqueta),
  colors: slaColors,
  legend: { show: false },
  dataLabels: { enabled: false },
  stroke: { width: 2, colors: [isDark.value ? '#0f172a' : '#fff'] },
  plotOptions: { pie: { offsetY: 0, customScale: 1, donut: { size: '74%', labels: { show: false } } } },
  grid: { padding: { top: 0, right: 0, bottom: 0, left: 0 } },
  tooltip: {
    theme: isDark.value ? 'dark' : 'light',
    y: { formatter: (val: number) => `${val} solicitudes` },
  },
}));

/** Icono/colores por tipo de evento del feed de actividad. */
function actividadTono(tipo: string): { icon: typeof ActivityIcon; color: string; bg: string } {
  switch (tipo) {
    case 'aceptada': return { icon: CheckCircleIcon, color: '#16a34a', bg: '#dcfce7' };
    case 'rechazada': return { icon: XCircleIcon, color: '#dc2626', bg: '#fee2e2' };
    case 'en_espera': return { icon: ClockIcon, color: '#d97706', bg: '#fef3c7' };
    case 'usuario': return { icon: UserPlusIcon, color: '#7c3aed', bg: '#ede9fe' };
    default: return { icon: FilePlusIcon, color: '#0d9488', bg: '#ccfbf1' };
  }
}

/** "Hace 5 min" / "Hace 2 horas" / "Hace 3 días" a partir de un ISO string. */
function haceTexto(iso: string | null): string {
  if (!iso) return '';
  const minutos = Math.floor((Date.now() - new Date(iso).getTime()) / 60000);
  if (minutos < 1) return 'Ahora';
  if (minutos < 60) return `Hace ${minutos} min`;
  const horas = Math.floor(minutos / 60);
  if (horas < 24) return `Hace ${horas} ${horas === 1 ? 'hora' : 'horas'}`;
  const dias = Math.floor(horas / 24);
  return `Hace ${dias} ${dias === 1 ? 'día' : 'días'}`;
}

/** % de cambio entre la primera y segunda mitad de una serie diaria. */
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

async function cargarStats() {
  try {
    cargando.value = true;
    const params: Record<string, string> = {};
    if (filtros.value.desde) params.desde = filtros.value.desde;
    if (filtros.value.hasta) params.hasta = filtros.value.hasta;
    if (filtros.value.estado && filtros.value.estado !== 'todas') params.estado = filtros.value.estado;
    if (filtros.value.especialidad) params.especialidad = filtros.value.especialidad;
    if (filtros.value.clinica) params.clinica = String(filtros.value.clinica);
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

// Refresco silencioso cada 30 s sin mostrar el esqueleto de carga.
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

/* ── Saludo ── */
.dash-title { font-size: 20px; font-weight: 800; color: #1e293b; letter-spacing: -0.02em; }
.dark .dash-title { color: #e2e8f0; }
.dash-subtitle { font-size: 12px; color: #94a3b8; margin-top: 2px; }
.dark .dash-subtitle { color: #64748b; }

/* ── Filtros ── */
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

/* ── Panel cards (estilo del mockup) ── */
.panel-card-ui {
  background: rgba(255,255,255,0.95);
  border: 1px solid rgba(212, 222, 234, 0.6);
  border-radius: 14px;
  box-shadow: 0 4px 20px rgba(22, 70, 142, .07);
  transition: transform .3s cubic-bezier(.22,1,.36,1), box-shadow .3s cubic-bezier(.22,1,.36,1);
  backdrop-filter: blur(10px);
  position: relative;
  overflow: hidden;
  min-height: 0;
}
.panel-card-ui::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: linear-gradient(90deg, #0D2D6B 0%, #16468E 50%, #3b82f6 100%);
  opacity: 0.8;
}
.panel-card-ui:hover {
  transform: translateY(-3px);
  box-shadow: 0 14px 30px rgba(22,70,142,.12);
}
.dark .panel-card-ui {
  background: #161b28;
  border-color: rgba(255,255,255,0.07);
}

.panel-head {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 10px;
  position: relative;
  z-index: 1;
}
.panel-head-icon {
  width: 24px; height: 24px;
  border-radius: 7px;
  display: flex; align-items: center; justify-content: center;
  background: linear-gradient(135deg, #eaf4ff, #dbeafe);
  color: #16468E;
  flex-shrink: 0;
}
.panel-head-title {
  font-size: 12.5px;
  font-weight: 800;
  color: #1e2d55;
  margin: 0;
  flex: 1;
  min-width: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.dark .panel-head-title { color: #e2e8f0; }
.panel-head-pill {
  font-size: 9px;
  font-weight: 700;
  padding: 3px 9px;
  border-radius: 999px;
  background: #e0ecff;
  color: #16468e;
  border: 1px solid #d3e3fb;
  flex-shrink: 0;
}
.dark .panel-head-pill { background: rgba(99,102,241,0.15); color: #a5b4fc; border-color: rgba(99,102,241,0.25); }
.panel-link, .panel-link-sm {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 10.5px;
  font-weight: 700;
  color: #16468e;
  text-decoration: none;
  transition: color .15s ease, gap .15s ease;
}
.panel-link {
  margin-top: 10px;
  padding-top: 9px;
  border-top: 1px solid #f1f5f9;
  justify-content: center;
  position: relative;
  z-index: 1;
}
.panel-link-sm { flex-shrink: 0; }
.panel-link:hover, .panel-link-sm:hover { color: #2563c4; gap: 8px; }
.dark .panel-link { border-top-color: rgba(255,255,255,0.07); color: #a5b4fc; }
.dark .panel-link-sm { color: #a5b4fc; }

.panel-empty {
  height: 100%;
  min-height: 80px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: .4rem;
  color: #94a3b8;
  font-size: 11px;
  font-weight: 600;
}

/* ── Dona de estados ── */
.estado-donut-wrap { position: relative; flex-shrink: 0; width: 112px; height: 112px; }
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

/* ── Top clínicas (ranking) ── */
.top-row { display: flex; align-items: center; gap: 9px; position: relative; z-index: 1; }
.top-rank {
  width: 20px; height: 20px;
  border-radius: 7px;
  display: flex; align-items: center; justify-content: center;
  font-size: 10px; font-weight: 800;
  flex-shrink: 0;
  background: #e0ecff;
  color: #16468e;
}
.top-rank-1 { background: linear-gradient(135deg, #fde68a, #fbbf24); color: #92400e; }
.top-rank-2 { background: linear-gradient(135deg, #e2e8f0, #cbd5e1); color: #475569; }
.top-rank-3 { background: linear-gradient(135deg, #fed7aa, #fdba74); color: #9a3412; }
.top-name {
  font-size: 11px;
  font-weight: 700;
  color: #334155;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
  flex: 1; min-width: 0;
}
.dark .top-name { color: #cbd5e1; }
.top-value { font-size: 11px; font-weight: 800; color: #1e293b; flex-shrink: 0; }
.dark .top-value { color: #e2e8f0; }
.top-track {
  height: 5px;
  border-radius: 999px;
  background: #eef2f7;
  overflow: hidden;
  margin-top: 3px;
}
.dark .top-track { background: rgba(255,255,255,0.06); }
.top-fill {
  height: 100%;
  border-radius: 999px;
  background: linear-gradient(90deg, #16468E, #3b82f6);
  transition: width .8s cubic-bezier(.22,1,.36,1);
}

/* ── Especialidades ── */
.esp-row { display: flex; align-items: center; gap: 9px; position: relative; z-index: 1; }
.esp-icon {
  width: 24px; height: 24px;
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.esp-name {
  font-size: 11px;
  font-weight: 700;
  color: #334155;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
  flex: 1; min-width: 0;
  text-transform: capitalize;
}
.dark .esp-name { color: #cbd5e1; }
.esp-value { font-size: 11.5px; font-weight: 800; flex-shrink: 0; }
.esp-track {
  height: 6px;
  border-radius: 999px;
  background: #eef2f7;
  overflow: hidden;
  margin-top: 3px;
}
.dark .esp-track { background: rgba(255,255,255,0.06); }
.esp-fill {
  height: 100%;
  border-radius: 999px;
  transition: width .8s cubic-bezier(.22,1,.36,1);
}

/* ── Dona SLA ── */
.sla-donut-wrap { position: relative; width: 112px; height: 112px; flex-shrink: 0; }
.sla-donut-num { font-size: 20px; font-weight: 800; color: #16a34a; }
.dark .sla-donut-num { color: #4ade80; }

/* ── Actividad reciente ── */
.activity-list { scrollbar-width: thin; }
.activity-list::-webkit-scrollbar { width: 5px; }
.activity-list::-webkit-scrollbar-thumb { background: #dbe3ee; border-radius: 999px; }
.activity-row {
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 6px 8px;
  border-radius: 10px;
  transition: background .15s ease;
  position: relative;
  z-index: 1;
}
.activity-row:hover { background: #f8fafc; }
.dark .activity-row:hover { background: rgba(255,255,255,0.04); }
.activity-icon {
  width: 28px; height: 28px;
  border-radius: 9px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.activity-title {
  font-size: 11px;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.dark .activity-title { color: #e2e8f0; }
.activity-detail {
  font-size: 10px;
  color: #94a3b8;
  margin: 1px 0 0;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.activity-time {
  font-size: 9.5px;
  font-weight: 600;
  color: #b0bccd;
  flex-shrink: 0;
  white-space: nowrap;
}
.dark .activity-time { color: #64748b; }

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
.anim-slide-up { animation: slideUp .5s cubic-bezier(.22,1,.36,1) both; }
@keyframes slideUp {
  from { opacity: 0; transform: translateY(14px); }
  to { opacity: 1; transform: none; }
}
</style>
