<template>
  <ContentCard title="Centro de control" subtitle="Resumen operativo de Clínica Santa Bárbara">

    <section class="dashboard-welcome mb-6">
      <div>
        <p class="dashboard-eyebrow">Operación clínica</p>
        <h2>Todo bajo control</h2>
        <p>Consulta los indicadores esenciales y el estado de atención de hoy.</p>
      </div>
      <div class="dashboard-date">
        <CalendarDaysIcon class="w-4 h-4" />
        <span>{{ today }}</span>
      </div>
    </section>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <StatCard 
        value="124" 
        label="Pacientes Activos" 
        icon="Users" 
        color="blue" 
        delta="12 hoy" 
        delta-type="up" 
      />
      <StatCard 
        value="45" 
        label="Altas Programadas" 
        icon="Activity" 
        color="green" 
        delta="5 vs ayer" 
        delta-type="up" 
      />
      <StatCard 
        value="8" 
        label="Camas Disponibles" 
        icon="BedDouble" 
        color="amber" 
        delta="2 menos" 
        delta-type="down" 
      />
      <StatCard 
        value="3" 
        label="Alertas Críticas" 
        icon="AlertTriangle" 
        color="red" 
        delta="Sin cambio" 
        delta-type="neutral" 
      />
    </div>

    <!-- Puedes agregar más secciones del dashboard aquí -->
    <section class="grid grid-cols-1 xl:grid-cols-5 gap-5">
      <div class="dashboard-panel xl:col-span-3">
        <div class="flex items-center justify-between gap-3 mb-5">
          <div>
            <p class="dashboard-panel-label">Capacidad asistencial</p>
            <h3>Estado de servicios</h3>
          </div>
          <span class="dashboard-status"><span></span> Operación estable</span>
        </div>

        <div class="space-y-4">
          <div v-for="service in services" :key="service.name">
            <div class="flex items-center justify-between text-sm mb-2">
              <span class="font-medium text-slate-700">{{ service.name }}</span>
              <span class="font-semibold text-[#0d2d5e]">{{ service.value }}%</span>
            </div>
            <div class="capacity-track">
              <div class="capacity-bar" :style="{ width: `${service.value}%`, background: service.color }"></div>
            </div>
          </div>
        </div>
      </div>

      <div class="dashboard-panel xl:col-span-2">
        <div class="mb-5">
          <p class="dashboard-panel-label">Actualizaciones</p>
          <h3>Actividad reciente</h3>
        </div>
        <div class="space-y-4">
          <div v-for="activity in activities" :key="activity.title" class="activity-item">
            <div class="activity-icon" :class="activity.tone">
              <component :is="activity.icon" class="w-4 h-4" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-medium text-slate-700">{{ activity.title }}</p>
              <p class="text-xs text-slate-400 mt-0.5">{{ activity.description }}</p>
            </div>
            <span class="text-[11px] text-slate-400 whitespace-nowrap">{{ activity.time }}</span>
          </div>
        </div>
      </div>
    </section>

  </ContentCard>
</template>

<script setup lang="ts">
import { CalendarDays as CalendarDaysIcon, CheckCircle2 as CheckCircleIcon, Clock3 as ClockIcon, Stethoscope as StethoscopeIcon } from '@lucide/vue';
import ContentCard from '@/components/ui/ContentCard.vue';
import StatCard from '@/components/ui/StatCard.vue';

const today = new Intl.DateTimeFormat('es-CO', {
  weekday: 'long',
  day: 'numeric',
  month: 'long',
}).format(new Date());

const services = [
  { name: 'Urgencias', value: 78, color: 'linear-gradient(90deg, #2563c4, #65a1e9)' },
  { name: 'Hospitalización', value: 64, color: 'linear-gradient(90deg, #15966a, #5ac996)' },
  { name: 'Unidad de cuidados intensivos', value: 86, color: 'linear-gradient(90deg, #d9951b, #f0c35f)' },
];

const activities = [
  { title: 'Ronda de enfermería completada', description: 'Hospitalización · Piso 3', time: 'Hace 12 min', icon: CheckCircleIcon, tone: 'activity-success' },
  { title: 'Nueva admisión registrada', description: 'Urgencias · Triage prioritario', time: 'Hace 28 min', icon: StethoscopeIcon, tone: 'activity-primary' },
  { title: 'Actualización de disponibilidad', description: '8 camas disponibles', time: 'Hace 45 min', icon: ClockIcon, tone: 'activity-warning' },
];
</script>

<style scoped>
.dashboard-welcome {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.4rem 1.5rem;
  overflow: hidden;
  background:
    radial-gradient(circle at right top, rgba(151, 199, 255, 0.52), transparent 15rem),
    linear-gradient(135deg, rgba(255, 255, 255, 0.88), rgba(237, 246, 255, 0.72));
  border: 1px solid rgba(255, 255, 255, 0.88);
  border-radius: 20px;
  box-shadow: 10px 10px 20px rgba(61, 83, 116, 0.1), -7px -7px 16px rgba(255, 255, 255, 0.85);
}

.dashboard-eyebrow,
.dashboard-panel-label {
  margin: 0 0 0.3rem;
  color: #4778b8;
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.13em;
  text-transform: uppercase;
}

.dashboard-welcome h2,
.dashboard-panel h3 {
  margin: 0;
  color: #0d2d5e;
  font-size: 1.25rem;
  font-weight: 700;
}

.dashboard-welcome > div > p:last-child {
  margin: 0.35rem 0 0;
  color: #64748b;
  font-size: 0.86rem;
}

.dashboard-date,
.dashboard-status {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.55rem 0.75rem;
  color: #315f9e;
  background: rgba(255, 255, 255, 0.62);
  border: 1px solid rgba(255, 255, 255, 0.9);
  border-radius: 12px;
  box-shadow: inset 2px 2px 5px rgba(92, 119, 155, 0.08), inset -2px -2px 5px rgba(255, 255, 255, 0.9);
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: capitalize;
  white-space: nowrap;
}

.dashboard-panel {
  padding: 1.35rem;
  background: rgba(255, 255, 255, 0.68);
  border: 1px solid rgba(255, 255, 255, 0.86);
  border-radius: 20px;
  box-shadow: 8px 8px 20px rgba(61, 83, 116, 0.08), -6px -6px 16px rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(14px);
}

.dashboard-status {
  color: #18794e;
  background: rgba(236, 253, 245, 0.74);
  text-transform: none;
}

.dashboard-status span {
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 999px;
  background: #22a06b;
  box-shadow: 0 0 0 4px rgba(34, 160, 107, 0.12);
}

.capacity-track {
  height: 0.6rem;
  overflow: hidden;
  background: #e8edf4;
  border-radius: 999px;
  box-shadow: inset 2px 2px 4px rgba(74, 98, 135, 0.13), inset -1px -1px 3px #ffffff;
}

.capacity-bar {
  height: 100%;
  border-radius: inherit;
  box-shadow: 0 1px 2px rgba(12, 45, 94, 0.2);
}

.activity-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.activity-icon {
  display: flex;
  width: 2.25rem;
  height: 2.25rem;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border-radius: 12px;
  box-shadow: inset 2px 2px 5px rgba(33, 64, 107, 0.1), inset -2px -2px 5px rgba(255, 255, 255, 0.82);
}

.activity-success { color: #168759; background: #dff7ea; }
.activity-primary { color: #2766b8; background: #e2efff; }
.activity-warning { color: #b7750a; background: #fff3d5; }

@media (max-width: 640px) {
  .dashboard-welcome { align-items: flex-start; flex-direction: column; }
  .dashboard-date { width: 100%; justify-content: center; }
}
</style>
