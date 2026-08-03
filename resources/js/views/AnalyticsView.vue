<template>
  <div class="h-full flex flex-col gap-2 p-2 sm:p-3 overflow-y-auto analytics-bg">

    <!-- ── Hero ── -->
    <div class="hero-card rounded-2xl p-3 sm:p-4 flex items-center gap-4 relative overflow-hidden shrink-0 animate-fade-in-down"
      style="animation-duration: 0.4s; animation-fill-mode: both;">
      <div class="hero-glow"></div>
      <div class="hero-pattern"></div>
      <div class="hero-icon-wrap z-10 shrink-0">
        <component :is="FlameIcon" class="w-6 h-6" />
      </div>
      <div class="flex-1 min-w-0 z-10">
        <div class="flex items-center gap-2 mb-0.5">
          <span class="hero-live-dot"></span>
          <p class="text-[9px] font-semibold uppercase tracking-[0.12em]" style="color:rgba(255,255,255,0.55);">Analítica en vivo</p>
        </div>
        <h1 class="text-sm sm:text-lg font-bold leading-tight text-white tracking-tight">
          Analítica avanzada
        </h1>
        <p class="text-[10px] sm:text-xs font-semibold mt-0.5" style="color:#7eb3ff;">Patrones de demanda, mapas de calor y tendencias</p>
      </div>
    </div>

    <!-- ── KPI cards ── -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 shrink-0 animate-fade-in-up"
      style="animation-duration: 0.4s; animation-delay: 0.08s; animation-fill-mode: both;">
      <Card v-for="(kpi, i) in kpiCards" :key="i" class="kpi-card anim-slide-up"
        :style="{ animationDelay: (i * 0.06) + 's', '--accent': kpi.color }">
        <div class="kpi-glow"></div>
        <CardContent class="p-3.5 flex items-center gap-3">
          <div class="kpi-icon-wrap">
            <component :is="kpi.icon" class="w-[18px] h-[18px]" />
          </div>
          <div class="flex-1 min-w-0 relative z-10">
            <p class="kpi-value">{{ kpi.value }}</p>
            <p class="kpi-label">{{ kpi.label }}</p>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- ── Heatmap día × hora ── -->
    <Card class="chart-card rounded-xl shrink-0 animate-fade-in-up"
      style="animation-duration: 0.4s; animation-delay: 0.14s; animation-fill-mode: both;">
      <CardHeader class="p-3 pb-1">
        <div class="flex items-center gap-2 mb-1">
          <div class="chart-header-icon" style="background: linear-gradient(135deg,#eaf4ff,#dbeafe); color:#16468E;">
            <component :is="CalendarClockIcon" class="w-3.5 h-3.5" />
          </div>
          <div>
            <CardTitle class="text-xs font-bold" style="color:#1e2d55;">Mapa de calor: día × hora</CardTitle>
            <CardDescription class="text-[10px]" style="color:#8a9ab5;">Patrón de llegada de solicitudes durante la semana</CardDescription>
          </div>
        </div>
      </CardHeader>
      <CardContent class="p-3 pt-0">
        <div class="heatmap-wrap">
          <apexchart
            v-if="hasHeatmapDiaHora"
            type="heatmap"
            height="260"
            :options="heatmapDiaHoraOptions"
            :series="stats.heatmap_dia_hora"
          />
          <div v-else class="chart-empty"><span>Sin datos suficientes</span></div>
        </div>
      </CardContent>
    </Card>

    <!-- ── Heatmap clínica × especialidad ── -->
    <Card class="chart-card rounded-xl shrink-0 animate-fade-in-up"
      style="animation-duration: 0.4s; animation-delay: 0.2s; animation-fill-mode: both;">
      <CardHeader class="p-3 pb-1">
        <div class="flex items-center gap-2 mb-1">
          <div class="chart-header-icon" style="background: linear-gradient(135deg,#dcfce7,#bbf7d0); color:#15966a;">
            <component :is="HospitalIcon" class="w-3.5 h-3.5" />
          </div>
          <div>
            <CardTitle class="text-xs font-bold" style="color:#1e2d55;">Mapa de calor: clínica × especialidad</CardTitle>
            <CardDescription class="text-[10px]" style="color:#8a9ab5;">Qué clínicas solicitan más cada especialidad</CardDescription>
          </div>
        </div>
      </CardHeader>
      <CardContent class="p-3 pt-0">
        <div class="heatmap-wrap">
          <apexchart
            v-if="hasHeatmapClinica"
            type="heatmap"
            height="260"
            :options="heatmapClinicaOptions"
            :series="stats.heatmap_clinica_especialidad.series"
          />
          <div v-else class="chart-empty"><span>Sin datos suficientes</span></div>
        </div>
      </CardContent>
    </Card>

    <!-- ── Tendencia mensual + Top EPS ── -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-2 shrink-0 animate-fade-in-up"
      style="animation-duration: 0.4s; animation-delay: 0.26s; animation-fill-mode: both;">
      <Card class="chart-card rounded-xl flex flex-col">
        <CardHeader class="p-3 pb-1">
          <div class="flex items-center gap-2 mb-1">
            <div class="chart-header-icon" style="background: linear-gradient(135deg,#ede9fe,#ddd6fe); color:#7048e8;">
              <component :is="TrendingUpIcon" class="w-3.5 h-3.5" />
            </div>
            <CardTitle class="text-xs font-bold" style="color:#1e2d55;">Tendencia mensual</CardTitle>
          </div>
        </CardHeader>
        <CardContent class="p-3 pt-0 flex-1 min-h-0">
          <apexchart
            type="area"
            height="180"
            :options="tendenciaMensualOptions"
            :series="tendenciaMensualSeries"
          />
        </CardContent>
      </Card>

      <Card class="chart-card rounded-xl flex flex-col">
        <CardHeader class="p-3 pb-1">
          <div class="flex items-center gap-2 mb-1">
            <div class="chart-header-icon" style="background: linear-gradient(135deg,#fff3cd,#fde68a); color:#e67700;">
              <component :is="Building2Icon" class="w-3.5 h-3.5" />
            </div>
            <CardTitle class="text-xs font-bold" style="color:#1e2d55;">Top EPS</CardTitle>
          </div>
        </CardHeader>
        <CardContent class="p-3 pt-0 flex-1 min-h-0">
          <apexchart
            v-if="topEpsSeries[0]?.data?.length"
            type="bar"
            height="180"
            :options="topEpsOptions"
            :series="topEpsSeries"
          />
          <div v-else class="chart-empty" style="height:180px;"><span>Sin datos suficientes</span></div>
        </CardContent>
      </Card>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import {
  Flame as FlameIcon,
  CalendarClock as CalendarClockIcon,
  Hospital as HospitalIcon,
  TrendingUp as TrendingUpIcon,
  Building2 as Building2Icon,
  Gauge as GaugeIcon,
  CalendarDays as CalendarDaysIcon,
  Clock3 as ClockIcon,
} from '@lucide/vue';
import http from '@/plugins/axios';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardContent from '@/components/ui/card/CardContent.vue';

const cargando = ref(false);

const stats = ref({
  heatmap_dia_hora: [] as Array<{ name: string; data: Array<{ x: string; y: number }> }>,
  heatmap_clinica_especialidad: {
    clinicas: [] as string[],
    especialidades: [] as string[],
    series: [] as Array<{ name: string; data: Array<{ x: string; y: number }> }>,
  },
  tendencia_mensual: [] as Array<{ mes: string; label: string; total: number }>,
  top_eps: [] as Array<{ eps: string; total: number }>,
  kpis: {
    promedio_diario: 0,
    dia_pico: '—',
    hora_pico: '—',
    clinica_mas_activa: '—',
  },
});

const hasHeatmapDiaHora = computed(() =>
  stats.value.heatmap_dia_hora.some(row => row.data.some(d => d.y > 0)),
);

const hasHeatmapClinica = computed(() =>
  stats.value.heatmap_clinica_especialidad.series.some(row => row.data.some(d => d.y > 0)),
);

const kpiCards = computed(() => [
  {
    label: 'Promedio diario',
    value: String(stats.value.kpis.promedio_diario),
    icon: GaugeIcon,
    color: '#2563c4',
  },
  {
    label: 'Día pico de demanda',
    value: stats.value.kpis.dia_pico,
    icon: CalendarDaysIcon,
    color: '#15966a',
  },
  {
    label: 'Hora pico',
    value: stats.value.kpis.hora_pico,
    icon: ClockIcon,
    color: '#e67700',
  },
  {
    label: 'Clínica más activa',
    value: stats.value.kpis.clinica_mas_activa,
    icon: HospitalIcon,
    color: '#7048e8',
  },
]);

const heatmapColorScale = {
  ranges: [
    { from: 0, to: 0, color: '#eef2f7', name: 'Sin datos' },
    { from: 1, to: 2, color: '#bfdbfe', name: 'Bajo' },
    { from: 3, to: 5, color: '#60a5fa', name: 'Medio' },
    { from: 6, to: 10, color: '#2563c4', name: 'Alto' },
    { from: 11, to: 9999, color: '#0D2D6B', name: 'Muy alto' },
  ],
};

const heatmapDiaHoraOptions = computed(() => ({
  chart: {
    type: 'heatmap' as const,
    fontFamily: 'inherit',
    toolbar: { show: false },
    animations: {
      enabled: true,
      easing: 'easeinout' as const,
      speed: 700,
      dynamicAnimation: { enabled: true, speed: 350 },
    },
  },
  dataLabels: { enabled: false },
  plotOptions: {
    heatmap: {
      radius: 3,
      useFillColorAsStroke: false,
      colorScale: heatmapColorScale,
    },
  },
  stroke: { width: 2, colors: ['#fff'] },
  xaxis: {
    labels: { style: { fontSize: '9px', colors: '#94a3b8' }, rotate: 0 },
    axisBorder: { show: false },
    axisTicks: { show: false },
    tickAmount: 12,
  },
  yaxis: {
    labels: { style: { fontSize: '10.5px', fontWeight: 700, colors: '#475569' } },
  },
  grid: { padding: { left: 4, right: 4, top: -8, bottom: -8 } },
  tooltip: {
    y: { formatter: (val: number) => `${val} solicitud${val === 1 ? '' : 'es'}` },
    style: { fontSize: '12px', fontFamily: 'inherit' },
  },
  legend: { show: false },
}));

const heatmapClinicaOptions = computed(() => ({
  chart: {
    type: 'heatmap' as const,
    fontFamily: 'inherit',
    toolbar: { show: false },
    animations: {
      enabled: true,
      easing: 'easeinout' as const,
      speed: 700,
      dynamicAnimation: { enabled: true, speed: 350 },
    },
  },
  dataLabels: { enabled: false },
  plotOptions: {
    heatmap: {
      radius: 3,
      useFillColorAsStroke: false,
      colorScale: heatmapColorScale,
    },
  },
  stroke: { width: 2, colors: ['#fff'] },
  xaxis: {
    labels: { style: { fontSize: '9px', colors: '#94a3b8' }, rotate: -35, trim: false },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: {
    labels: { style: { fontSize: '10px', fontWeight: 700, colors: '#475569' } },
  },
  grid: { padding: { left: 4, right: 4, top: -4, bottom: 0 } },
  tooltip: {
    y: { formatter: (val: number) => `${val} solicitud${val === 1 ? '' : 'es'}` },
    style: { fontSize: '12px', fontFamily: 'inherit' },
  },
  legend: { show: false },
}));

const tendenciaMensualSeries = computed(() => [{
  name: 'Solicitudes',
  data: stats.value.tendencia_mensual.map(t => t.total),
}]);

const tendenciaMensualOptions = computed(() => ({
  chart: {
    type: 'area' as const,
    fontFamily: 'inherit',
    toolbar: { show: false },
    zoom: { enabled: false },
    animations: {
      enabled: true,
      easing: 'easeinout' as const,
      speed: 900,
      animateGradually: { enabled: true, delay: 100 },
      dynamicAnimation: { enabled: true, speed: 400 },
    },
  },
  stroke: { curve: 'smooth' as const, width: 3, colors: ['#7048e8'] },
  colors: ['#7048e8'],
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.45,
      opacityTo: 0.02,
      stops: [0, 90, 100],
    },
  },
  markers: { size: 0, hover: { size: 6 }, strokeWidth: 2, strokeColors: '#fff', colors: ['#7048e8'] },
  xaxis: {
    categories: stats.value.tendencia_mensual.map(t => t.label),
    labels: { style: { fontSize: '10px', fontWeight: 600, colors: '#94a3b8' } },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: { labels: { style: { fontSize: '10px', colors: '#94a3b8' } } },
  grid: {
    borderColor: '#f1f5f9',
    strokeDashArray: 4,
    xaxis: { lines: { show: false } },
    padding: { top: 0, right: 8, bottom: 0, left: 0 },
  },
  dataLabels: { enabled: false },
  tooltip: {
    y: { formatter: (val: number) => `${val} solicitud${val === 1 ? '' : 'es'}` },
    style: { fontSize: '12px', fontFamily: 'inherit' },
  },
}));

const topEpsSeries = computed(() => [{
  name: 'Solicitudes',
  data: stats.value.top_eps.map(e => e.total),
}]);

const topEpsOptions = computed(() => ({
  chart: {
    type: 'bar' as const,
    fontFamily: 'inherit',
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
  colors: ['#e67700', '#f08c00', '#f59f00', '#fcc419', '#ffd43b', '#ffe066'],
  xaxis: {
    categories: stats.value.top_eps.map(e => e.eps),
    labels: { style: { fontSize: '10px', colors: '#94a3b8' } },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: {
    labels: { style: { fontSize: '10px', fontWeight: 600, colors: '#475569' }, maxWidth: 200 },
  },
  grid: {
    borderColor: '#f1f5f9',
    strokeDashArray: 4,
    yaxis: { lines: { show: false } },
    padding: { top: -8, right: 20, bottom: -8, left: 12 },
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
    style: { fontSize: '12px', fontFamily: 'inherit' },
  },
  legend: { show: false },
}));

async function cargarStats() {
  cargando.value = true;
  try {
    const { data } = await http.get('/api/analytics/stats');
    stats.value = data.data;
  } catch {
    // silent fail
  } finally {
    cargando.value = false;
  }
}

onMounted(cargarStats);
</script>

<style scoped>
.analytics-bg {
  background:
    radial-gradient(ellipse at 90% 0%, rgba(188, 218, 255, 0.35), transparent 30rem),
    radial-gradient(ellipse at 10% 100%, rgba(208, 242, 226, 0.25), transparent 28rem);
}

@keyframes fade-in-down {
  from { opacity: 0; transform: translateY(-12px); }
  to { opacity: 1; transform: translateY(0); }
}
@keyframes fade-in-up {
  from { opacity: 0; transform: translateY(12px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-down { animation-name: fade-in-down; }
.animate-fade-in-up { animation-name: fade-in-up; }
@keyframes slideUp {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
.anim-slide-up { animation: slideUp .5s cubic-bezier(.22,1,.36,1) both; }

/* ── Hero ── */
.hero-card {
  background: linear-gradient(135deg, #0D2D6B 0%, #16468E 60%, #1e56a8 100%);
  box-shadow: 0 10px 30px rgba(13, 45, 107, 0.25);
}
.hero-glow {
  position: absolute;
  top: -60px; right: -40px;
  width: 220px; height: 220px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(126,179,255,0.35), transparent 70%);
  pointer-events: none;
}
.hero-pattern {
  position: absolute;
  inset: 0;
  background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.08) 1px, transparent 0);
  background-size: 18px 18px;
  pointer-events: none;
}
.hero-icon-wrap {
  width: 46px; height: 46px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255,255,255,0.12);
  color: #ffd43b;
  box-shadow: 0 4px 14px rgba(255,212,59,0.25) inset;
}
.hero-live-dot {
  width: 6px; height: 6px;
  border-radius: 999px;
  background: #4ade80;
  box-shadow: 0 0 0 3px rgba(74,222,128,0.25);
  animation: pulseDot 2s ease-in-out infinite;
}
@keyframes pulseDot { 0%, 100% { opacity: 1; } 50% { opacity: .4; } }

/* ── KPI cards ── */
.kpi-card {
  background: linear-gradient(145deg, #ffffff 0%, #fbfdff 100%);
  border: 1px solid color-mix(in srgb, var(--accent) 22%, #e2e8f0);
  box-shadow: 0 8px 24px color-mix(in srgb, var(--accent) 10%, transparent), 0 1px 0 rgba(255,255,255,0.8) inset;
  transition: transform .3s cubic-bezier(.22,1,.36,1), box-shadow .3s cubic-bezier(.22,1,.36,1);
  position: relative;
  overflow: hidden;
}
.kpi-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 16px 34px color-mix(in srgb, var(--accent) 22%, transparent);
}
.kpi-glow {
  position: absolute;
  top: -50%; right: -30%;
  width: 130px; height: 130px;
  border-radius: 50%;
  background: radial-gradient(circle, color-mix(in srgb, var(--accent) 35%, transparent), transparent 70%);
  filter: blur(20px);
  opacity: .6;
  pointer-events: none;
}
.kpi-icon-wrap {
  width: 40px; height: 40px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  background: color-mix(in srgb, var(--accent) 14%, white);
  color: var(--accent);
  position: relative;
  z-index: 1;
  box-shadow: 0 3px 10px color-mix(in srgb, var(--accent) 25%, transparent);
}
.kpi-value {
  font-size: 18px;
  font-weight: 900;
  color: #1e293b;
  line-height: 1.1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  position: relative;
  z-index: 1;
}
.kpi-label {
  font-size: 10px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: .03em;
  margin-top: 1px;
  position: relative;
  z-index: 1;
}

/* ── Chart cards ── */
.chart-card {
  background: rgba(255,255,255,0.94);
  border: 1px solid rgba(212, 222, 234, 0.6);
  box-shadow: 0 4px 20px rgba(22, 70, 142, .07);
  backdrop-filter: blur(10px);
  transition: box-shadow .3s ease;
}
.chart-card:hover {
  box-shadow: 0 10px 28px rgba(22, 70, 142, .12);
}
.chart-header-icon {
  width: 24px; height: 24px;
  border-radius: 7px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.heatmap-wrap {
  position: relative;
  z-index: 1;
}
.chart-empty {
  height: 260px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  font-size: 11px;
  font-weight: 600;
}
</style>
