<template>
  <div class="h-full flex flex-col gap-3 p-3 sm:p-5 overflow-hidden dashboard-bg">

    <!-- ── Hero banner ── -->
    <div class="hero-card rounded-2xl p-5 sm:p-6 flex items-center gap-5 relative overflow-hidden shrink-0 anim-fade-down">
      <div class="hero-glow"></div>
      <div class="hero-pattern"></div>
      <div class="hero-glow-2"></div>
      <div class="flex items-center gap-4 z-10 shrink-0">
        <div class="hero-logo">
          <img :src="'/images/logo-w.png'" alt="Logo" class="w-full h-full object-contain" />
        </div>
      </div>
      <div class="flex-1 min-w-0 z-10">
        <div class="flex items-center gap-2 mb-1.5">
          <span class="hero-live-dot"></span>
          <p class="text-[10px] font-semibold uppercase tracking-[0.12em]" style="color:rgba(255,255,255,0.55);">Sistema de referencia · En línea</p>
        </div>
        <h1 class="text-lg sm:text-2xl font-bold leading-tight text-white tracking-tight">
          Centro de control
        </h1>
        <p class="text-sm font-extrabold mt-0.5" style="color:#7eb3ff;">Clínica Santa Bárbara</p>
        <p class="text-[11px] sm:text-xs mt-2" style="color:rgba(255,255,255,0.5);">{{ today }} · Resumen operativo en tiempo real</p>
      </div>
      <div class="hero-right z-10 shrink-0 hidden sm:flex flex-col items-end gap-2">
        <div class="hero-date-pill">
          <component :is="CalendarDaysIcon" class="w-3.5 h-3.5" />
          <span>{{ todayShort }}</span>
        </div>
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
      <span class="hero-pill" style="top:18%; right:28%; background:rgba(255,255,255,0.12); width:8px; height:8px;"></span>
      <span class="hero-pill" style="top:62%; right:12%; background:rgba(126,179,255,0.3); width:16px; height:16px;"></span>
      <span class="hero-pill" style="bottom:20%; right:35%; background:rgba(255,255,255,0.08); width:10px; height:10px;"></span>
      <span class="hero-pill" style="top:35%; right:8%; background:rgba(126,179,255,0.15); width:12px; height:12px;"></span>
    </div>

    <!-- ── Stat cards ── -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 shrink-0">
      <div
        v-for="(card, i) in statCards" :key="i"
        class="stat-card rounded-2xl p-4 flex flex-col gap-2 anim-slide-up"
        :style="{ animationDelay: (i * 0.08) + 's', '--accent': card.color }"
      >
        <div class="stat-card-top-bar"></div>
        <div class="stat-card-glow" :style="{ background: 'radial-gradient(circle at 80% 20%, ' + card.color + '15, transparent 60%)' }"></div>
        <div class="flex items-center justify-between relative z-10">
          <div class="stat-icon-wrap" :style="{ background: card.iconBg, color: card.color }">
            <component :is="card.icon" class="w-4 h-4" />
          </div>
          <span class="stat-delta-badge" :style="{ background: card.deltaBg, color: card.deltaColor }">
            {{ card.delta }}
          </span>
        </div>
        <div class="mt-auto relative z-10">
          <p class="stat-value" :style="{ color: card.color }">{{ card.value }}</p>
          <p class="stat-label">{{ card.label }}</p>
        </div>
        <div class="stat-progress-track relative z-10">
          <div class="stat-progress-fill" :style="{ width: card.percent + '%', background: card.color }"></div>
        </div>
      </div>
    </div>

    <!-- ── Distribución + Donut ── -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 shrink-0">
      <!-- Barra de distribución -->
      <div class="distrib-card rounded-2xl p-4 flex items-center gap-4 anim-slide-up lg:col-span-2" style="animation-delay:0.16s">
        <div class="distrib-card-glow"></div>
        <div class="flex-1 relative z-10">
          <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-bold" style="color:#1e2d55;">Distribución de solicitudes</p>
            <p class="text-[10px]" style="color:#8a9ab5;">{{ stats.solicitudes.total }} en total</p>
          </div>
          <div class="flex h-3 rounded-full overflow-hidden" style="background:#edf2f7;">
            <div v-if="stats.solicitudes.pendientes" class="distrib-segment transition-all duration-700" :style="{ width: (stats.solicitudes.pendientes / Math.max(1, stats.solicitudes.total)) * 100 + '%', background: 'linear-gradient(90deg, #fbbf24, #f59e0b)' }"
              :data-tooltip="`${stats.solicitudes.pendientes} pendientes (${Math.round(stats.solicitudes.pendientes / Math.max(1, stats.solicitudes.total) * 100)}%)`"></div>
            <div v-if="stats.solicitudes.aceptadas" class="distrib-segment transition-all duration-700" :style="{ width: (stats.solicitudes.aceptadas / Math.max(1, stats.solicitudes.total)) * 100 + '%', background: 'linear-gradient(90deg, #4ade80, #22c55e)' }"
              :data-tooltip="`${stats.solicitudes.aceptadas} aceptadas (${Math.round(stats.solicitudes.aceptadas / Math.max(1, stats.solicitudes.total) * 100)}%)`"></div>
            <div v-if="stats.solicitudes.negadas" class="distrib-segment transition-all duration-700" :style="{ width: (stats.solicitudes.negadas / Math.max(1, stats.solicitudes.total)) * 100 + '%', background: 'linear-gradient(90deg, #f87171, #ef4444)' }"
              :data-tooltip="`${stats.solicitudes.negadas} negadas (${Math.round(stats.solicitudes.negadas / Math.max(1, stats.solicitudes.total) * 100)}%)`"></div>
          </div>
          <div class="flex items-center gap-4 mt-2.5">
            <div class="flex items-center gap-1.5">
              <span class="w-2.5 h-2.5 rounded-full" style="background:#fbbf24;"></span>
              <span class="text-[10px] font-medium" style="color:#8a9ab5;">Pendientes <strong style="color:#d97706;">{{ stats.solicitudes.pendientes }}</strong></span>
            </div>
            <div class="flex items-center gap-1.5">
              <span class="w-2.5 h-2.5 rounded-full" style="background:#22c55e;"></span>
              <span class="text-[10px] font-medium" style="color:#8a9ab5;">Aceptadas <strong style="color:#16a34a;">{{ stats.solicitudes.aceptadas }}</strong></span>
            </div>
            <div class="flex items-center gap-1.5">
              <span class="w-2.5 h-2.5 rounded-full" style="background:#f87171;"></span>
              <span class="text-[10px] font-medium" style="color:#8a9ab5;">Negadas <strong style="color:#dc2626;">{{ stats.solicitudes.negadas }}</strong></span>
            </div>
          </div>
        </div>
      </div>

      <!-- Donut chart CSS -->
      <div class="distrib-card rounded-2xl p-4 flex items-center justify-center gap-4 anim-slide-up" style="animation-delay:0.2s">
        <div class="donut-chart relative z-10" :style="donutStyle">
          <div class="donut-center">
            <span class="donut-num">{{ stats.solicitudes.total }}</span>
            <span class="donut-label">Total</span>
          </div>
        </div>
        <div class="flex flex-col gap-1.5 relative z-10">
          <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full" style="background:#fbbf24;"></span>
            <span class="text-[10px] font-semibold" style="color:#d97706;">{{ Math.round(donutPercents[0]) }}%</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full" style="background:#22c55e;"></span>
            <span class="text-[10px] font-semibold" style="color:#16a34a;">{{ Math.round(donutPercents[1]) }}%</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full" style="background:#f87171;"></span>
            <span class="text-[10px] font-semibold" style="color:#dc2626;">{{ Math.round(donutPercents[2]) }}%</span>
          </div>
        </div>
      </div>
    </div>

    <div class="operations-grid anim-slide-up">
      <section class="operations-card">
        <div class="operations-glow"></div>
        <div class="operations-heading">
          <div>
            <p class="panel-eyebrow">Navegación operativa</p>
            <h3 class="panel-title">Accesos rápidos</h3>
          </div>
          <span class="operations-hint">Gestione el sistema desde un solo lugar</span>
        </div>

        <div class="quick-actions">
          <router-link to="/solicitudes-referencia" class="quick-action quick-action-blue">
            <div class="quick-action-icon">
              <component :is="ClipboardListIcon" />
            </div>
            <div class="quick-action-copy">
              <strong>Solicitudes</strong>
              <span>Revisar y responder remisiones</span>
            </div>
            <component :is="ArrowRightIcon" class="quick-action-arrow" />
          </router-link>

          <router-link to="/clinicas" class="quick-action quick-action-green">
            <div class="quick-action-icon">
              <component :is="HospitalIcon" />
            </div>
            <div class="quick-action-copy">
              <strong>Clínicas</strong>
              <span>Consultar entidades registradas</span>
            </div>
            <component :is="ArrowRightIcon" class="quick-action-arrow" />
          </router-link>

          <router-link to="/usuarios" class="quick-action quick-action-purple">
            <div class="quick-action-icon">
              <component :is="UsersIcon" />
            </div>
            <div class="quick-action-copy">
              <strong>Usuarios</strong>
              <span>Administrar accesos internos</span>
            </div>
            <component :is="ArrowRightIcon" class="quick-action-arrow" />
          </router-link>
        </div>
      </section>

      <section class="flow-card">
        <div class="flow-orbit flow-orbit-one"></div>
        <div class="flow-orbit flow-orbit-two"></div>
        <div class="flow-content">
          <div class="flow-heading">
            <div>
              <p class="flow-eyebrow">Proceso asistencial</p>
              <h3>Flujo de referencia</h3>
            </div>
            <span class="flow-live"><i></i> En línea</span>
          </div>

          <div class="flow-steps">
            <div class="flow-step">
              <div class="flow-step-icon">
                <component :is="ClipboardListIcon" />
              </div>
              <div>
                <strong>Recepción</strong>
                <span>Ingreso de solicitud</span>
              </div>
            </div>
            <div class="flow-connector"><span></span></div>
            <div class="flow-step">
              <div class="flow-step-icon">
                <component :is="StethoscopeIcon" />
              </div>
              <div>
                <strong>Evaluación</strong>
                <span>Revisión clínica</span>
              </div>
            </div>
            <div class="flow-connector"><span></span></div>
            <div class="flow-step">
              <div class="flow-step-icon flow-step-success">
                <component :is="CheckCircleIcon" />
              </div>
              <div>
                <strong>Respuesta</strong>
                <span>Decisión asistencial</span>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
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
} from '@lucide/vue';
import http from '@/plugins/axios';

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
  solicitudes: { total: 0, pendientes: 0, aceptadas: 0, negadas: 0 },
  clinicas: { total: 0, activas: 0, pendientes: 0 },
  usuarios: { total: 0, activos: 0 },
  solicitudes_recientes: [] as Array<{
    id: number; paciente: string; estado: string; especialidad: string; clinica: string | null; created_at: string | null;
  }>,
});

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
    iconBg: '#ede9fe',
    delta: `${stats.value.usuarios.total} total`,
    deltaBg: '#ede9fe',
    deltaColor: '#7048e8',
    percent: stats.value.usuarios.total ? Math.round((stats.value.usuarios.activos / stats.value.usuarios.total) * 100) : 0,
  },
]);

const donutPercents = computed(() => {
  const total = Math.max(1, stats.value.solicitudes.total);
  return [
    (stats.value.solicitudes.pendientes / total) * 100,
    (stats.value.solicitudes.aceptadas / total) * 100,
    (stats.value.solicitudes.negadas / total) * 100,
  ];
});

const donutStyle = computed(() => {
  const [p, a, n] = donutPercents.value;
  return {
    background: `conic-gradient(
      #fbbf24 0% ${p}%,
      #22c55e ${p}% ${p + a}%,
      #f87171 ${p + a}% ${p + a + n}%,
      #edf2f7 ${p + a + n}% 100%
    )`,
  };
});

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
    const { data } = await http.get('/api/dashboard/stats');
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
  width: 52px; height: 52px;
  border-radius: 14px;
  background: rgba(255,255,255,0.08);
  border: 1px solid rgba(255,255,255,0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 8px;
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
  background: rgba(255,255,255,0.9);
  border: 1px solid rgba(212, 222, 234, 0.6);
  box-shadow: 0 4px 20px rgba(22, 70, 142, .07);
  transition: transform .3s cubic-bezier(.22,1,.36,1), box-shadow .3s cubic-bezier(.22,1,.36,1);
  position: relative;
  overflow: hidden;
  backdrop-filter: blur(10px);
}
.stat-card-top-bar {
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: var(--accent, #0D2D6B);
  opacity: .85;
  transition: height .28s ease, opacity .28s ease;
}
.stat-card-glow {
  position: absolute; inset: 0; pointer-events: none; opacity: .6;
  transition: opacity .3s ease;
}
.stat-card:hover {
  transform: translateY(-6px) scale(1.02);
  box-shadow: 0 20px 40px rgba(22, 70, 142, .16);
}
.stat-card:hover .stat-card-top-bar {
  height: 5px;
  opacity: 1;
}
.stat-card:hover .stat-card-glow {
  opacity: 1;
}
.stat-icon-wrap {
  width: 36px; height: 36px;
  border-radius: 11px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.stat-delta-badge {
  font-size: 10px;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 999px;
  white-space: nowrap;
}
.stat-value {
  font-size: 28px;
  font-weight: 800;
  line-height: 1.1;
  letter-spacing: -.02em;
}
.stat-label {
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
  margin-top: 2px;
}
.stat-progress-track {
  height: 4px;
  border-radius: 999px;
  background: #edf2f7;
  overflow: hidden;
}
.stat-progress-fill {
  height: 100%;
  border-radius: inherit;
  transition: width .8s cubic-bezier(.22,1,.36,1);
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
  background: rgba(255,255,255,0.92);
  border: 1px solid rgba(212, 222, 234, 0.6);
  box-shadow: 0 4px 20px rgba(22, 70, 142, .07);
  position: relative; overflow: hidden;
  transition: transform .3s cubic-bezier(.22,1,.36,1), box-shadow .3s cubic-bezier(.22,1,.36,1);
  backdrop-filter: blur(10px);
}
.distrib-card-glow {
  position: absolute; top: -40px; right: -40px; width: 140px; height: 140px;
  border-radius: 50%; pointer-events: none;
  background: radial-gradient(circle, rgba(126,179,255,0.10), transparent 70%);
}
.distrib-card:hover { transform: translateY(-4px); box-shadow: 0 14px 30px rgba(22,70,142,.12); }
.distrib-segment { position: relative; cursor: pointer; transition: opacity .2s ease; }
.distrib-segment:hover { opacity: .85; }
.distrib-segment::after {
  content: attr(data-tooltip); position: absolute; bottom: calc(100% + 6px); left: 50%;
  transform: translateX(-50%); background: #1e2d55; color: #fff;
  font-size: 10px; font-weight: 600; padding: 4px 8px; border-radius: 6px;
  white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity .2s ease; z-index: 20;
}
.distrib-segment:hover::after { opacity: 1; }

/* ── Donut chart ── */
.donut-chart {
  width: 72px; height: 72px; border-radius: 50%;
  position: relative;
  transition: transform .3s ease;
  box-shadow: 0 4px 14px rgba(22,70,142,.12);
}
.donut-chart:hover { transform: scale(1.08) rotate(5deg); }
.donut-center {
  position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
  width: 52px; height: 52px; border-radius: 50%; background: #fff;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  box-shadow: inset 0 2px 6px rgba(22,70,142,.08);
}
.donut-num { font-size: 16px; font-weight: 800; color: #1e2d55; line-height: 1; }
.donut-label { font-size: 8px; color: #8a9ab5; margin-top: 2px; text-transform: uppercase; letter-spacing: .05em; }

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
  grid-template-columns: minmax(0, 3fr) minmax(320px, 2fr);
  gap: .75rem;
  flex: 1;
  min-height: 150px;
}
.operations-card,
.flow-card {
  position: relative;
  overflow: hidden;
  border-radius: 1rem;
}
.operations-card {
  padding: 1rem;
  background: rgba(255, 255, 255, .92);
  border: 1px solid rgba(212, 222, 234, .65);
  box-shadow: 0 6px 24px rgba(22, 70, 142, .08);
  backdrop-filter: blur(10px);
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
}
.operations-heading,
.flow-heading {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: .8rem;
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
  gap: .65rem;
}
.quick-action {
  --quick-color: #2563c4;
  --quick-bg: #e7efff;
  position: relative;
  display: flex;
  align-items: center;
  min-width: 0;
  gap: .65rem;
  padding: .8rem;
  border: 1px solid rgba(226, 232, 240, .85);
  border-radius: .85rem;
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
  width: 36px;
  height: 36px;
  flex: 0 0 auto;
  display: grid;
  place-items: center;
  border-radius: 11px;
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
  min-height: 150px;
  padding: 1rem;
  background: rgba(255,255,255,0.92);
  border: 1px solid rgba(212, 222, 234, 0.6);
  box-shadow: 0 6px 24px rgba(22, 70, 142, .08);
  backdrop-filter: blur(10px);
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
}
.flow-orbit-one {
  top: -60px;
  right: -40px;
  width: 160px;
  height: 160px;
  background: radial-gradient(circle, rgba(74,222,128,.10), transparent 70%);
}
.flow-orbit-two {
  right: 30px;
  bottom: -70px;
  width: 120px;
  height: 120px;
  background: radial-gradient(circle, rgba(126,179,255,.08), transparent 70%);
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
  width: 34px;
  height: 34px;
  flex: 0 0 auto;
  display: grid;
  place-items: center;
  border-radius: 11px;
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
