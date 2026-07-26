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
            <span class="hero-mini-stat-num">98.2%</span>
            <span class="hero-mini-stat-label">Disponibilidad</span>
          </div>
          <div class="hero-mini-divider"></div>
          <div class="hero-mini-stat">
            <span class="hero-mini-stat-num">4.7★</span>
            <span class="hero-mini-stat-label">Satisfacción</span>
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
        <div class="flex items-center justify-between">
          <div class="stat-icon-wrap" :style="{ background: card.iconBg, color: card.color }">
            <component :is="card.icon" class="w-4 h-4" />
          </div>
          <span class="stat-delta-badge" :style="{ background: card.deltaBg, color: card.deltaColor }">
            {{ card.delta }}
          </span>
        </div>
        <div class="mt-auto">
          <p class="stat-value" :style="{ color: card.color }">{{ card.value }}</p>
          <p class="stat-label">{{ card.label }}</p>
        </div>
        <div class="stat-progress-track">
          <div class="stat-progress-fill" :style="{ width: card.percent + '%', background: card.color }"></div>
        </div>
      </div>
    </div>

    <!-- ── Panels ── -->
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-3 flex-1 min-h-0 overflow-hidden">

      <!-- Capacidad asistencial -->
      <div class="panel-card rounded-2xl p-5 xl:col-span-3 flex flex-col anim-slide-up" style="animation-delay:0.2s">
        <div class="panel-top-bar"></div>
        <div class="flex items-center justify-between mb-5 shrink-0">
          <div class="flex items-center gap-3">
            <div class="panel-icon-wrap">
              <component :is="ActivityIcon" class="w-4 h-4" />
            </div>
            <div>
              <p class="panel-eyebrow">Capacidad asistencial</p>
              <h3 class="panel-title">Estado de servicios</h3>
            </div>
          </div>
          <span class="status-pill">
            <span></span> Operación estable
          </span>
        </div>
        <div class="space-y-4 flex-1 overflow-y-auto custom-scrollbar pr-1">
          <div v-for="service in services" :key="service.name" class="service-row">
            <div class="flex items-center justify-between mb-2">
              <div class="flex items-center gap-2.5">
                <span class="service-dot" :style="{ background: service.solid }"></span>
                <span class="service-name">{{ service.name }}</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="service-percent" :style="{ color: service.solid }">{{ service.value }}%</span>
                <span class="service-status-tag" :style="{ background: service.tagBg, color: service.tagColor }">{{ service.tag }}</span>
              </div>
            </div>
            <div class="capacity-track">
              <div class="capacity-bar" :style="{ width: `${service.value}%`, background: service.color }"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Actividad reciente -->
      <div class="panel-card rounded-2xl p-5 xl:col-span-2 flex flex-col anim-slide-up" style="animation-delay:0.3s">
        <div class="panel-top-bar"></div>
        <div class="flex items-center justify-between mb-5 shrink-0">
          <div class="flex items-center gap-3">
            <div class="panel-icon-wrap">
              <component :is="ClockIcon" class="w-4 h-4" />
            </div>
            <div>
              <p class="panel-eyebrow">Actualizaciones</p>
              <h3 class="panel-title">Actividad reciente</h3>
            </div>
          </div>
        </div>
        <div class="flex-1 overflow-y-auto custom-scrollbar pr-1">
          <div class="timeline">
            <div v-for="(activity, idx) in activities" :key="activity.title" class="timeline-item">
              <div class="timeline-line" v-if="idx < activities.length - 1"></div>
              <div class="timeline-dot" :class="activity.tone">
                <component :is="activity.icon" class="w-3.5 h-3.5" />
              </div>
              <div class="timeline-content">
                <p class="timeline-title">{{ activity.title }}</p>
                <p class="timeline-desc">{{ activity.description }}</p>
                <span class="timeline-time">{{ activity.time }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>

  </div>
</template>

<script setup lang="ts">
import {
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
} from '@lucide/vue';

const today = new Intl.DateTimeFormat('es-CO', {
  weekday: 'long',
  day: 'numeric',
  month: 'long',
}).format(new Date());

const todayShort = new Intl.DateTimeFormat('es-CO', {
  day: '2-digit',
  month: 'short',
}).format(new Date());

const statCards = [
  {
    label: 'Pacientes Activos',
    value: '124',
    icon: UsersIcon,
    color: '#2563c4',
    iconBg: '#dbe1ff',
    delta: '12 hoy',
    deltaBg: '#dbeafe',
    deltaColor: '#2563c4',
    percent: 72,
  },
  {
    label: 'Altas Programadas',
    value: '45',
    icon: ActivityIcon,
    color: '#15966a',
    iconBg: '#d3f9d8',
    delta: '↑ 5 vs ayer',
    deltaBg: '#dcfce7',
    deltaColor: '#15966a',
    percent: 58,
  },
  {
    label: 'Camas Disponibles',
    value: '8',
    icon: BedDoubleIcon,
    color: '#e67700',
    iconBg: '#fff3cd',
    delta: '↓ 2 menos',
    deltaBg: '#fef3c7',
    deltaColor: '#e67700',
    percent: 22,
  },
  {
    label: 'Alertas Críticas',
    value: '3',
    icon: AlertTriangleIcon,
    color: '#c92a2a',
    iconBg: '#ffe0e0',
    delta: 'Sin cambio',
    deltaBg: '#fee2e2',
    deltaColor: '#c92a2a',
    percent: 12,
  },
];

const services = [
  { name: 'Urgencias', value: 78, color: 'linear-gradient(90deg, #2563c4, #65a1e9)', solid: '#2563c4', tag: 'Alto', tagBg: '#dbeafe', tagColor: '#2563c4' },
  { name: 'Hospitalización', value: 64, color: 'linear-gradient(90deg, #15966a, #5ac996)', solid: '#15966a', tag: 'Normal', tagBg: '#dcfce7', tagColor: '#15966a' },
  { name: 'UCI', value: 91, color: 'linear-gradient(90deg, #c92a2a, #ff8787)', solid: '#c92a2a', tag: 'Crítico', tagBg: '#fee2e2', tagColor: '#c92a2a' },
  { name: 'Quirófano', value: 45, color: 'linear-gradient(90deg, #e67700, #ffa94d)', solid: '#e67700', tag: 'Óptimo', tagBg: '#fef3c7', tagColor: '#e67700' },
  { name: 'Consulta Externa', value: 52, color: 'linear-gradient(90deg, #7048e8, #b197fc)', solid: '#7048e8', tag: 'Normal', tagBg: '#ede9fe', tagColor: '#7048e8' },
];

const activities = [
  { title: 'Nueva solicitud de referencia', description: 'Clínica San Rafael envió remisión de paciente', time: 'Hace 5 min', icon: FileTextIcon, tone: 'activity-primary' },
  { title: 'Alta médica registrada', description: 'Paciente J. Pérez dado de alta de Hospitalización', time: 'Hace 22 min', icon: UserCheckIcon, tone: 'activity-success' },
  { title: 'Resultado de laboratorio', description: 'Examen de sangre completado para 3 pacientes', time: 'Hace 1 h', icon: FlaskIcon, tone: 'activity-warning' },
  { title: 'Solicitud de referencia aceptada', description: 'Centro Médico del Norte aprobó remisión', time: 'Hace 2 h', icon: CheckCircleIcon, tone: 'activity-success' },
  { title: 'Cama liberada en UCI', description: 'Cama #4 ahora disponible', time: 'Hace 3 h', icon: BedDoubleIcon, tone: 'activity-primary' },
];
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
.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 16px 36px rgba(22, 70, 142, .14);
}
.stat-card:hover .stat-card-top-bar {
  height: 5px;
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

/* ── Scrollbar ── */
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #c5c9d0; border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }

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
