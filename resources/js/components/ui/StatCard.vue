<script setup lang="ts">
import { type HTMLAttributes, type Component, computed } from 'vue';
import { cn } from '@/lib/utils';
import Sparkline from '@/components/ui/Sparkline.vue';

export type StatCardTone = 'info' | 'success' | 'warning' | 'danger' | 'violet';

/**
 * Un solo valor por tono: color de acento, su variante clara para el glow, y
 * los tintes suaves del icono y del badge de delta. Antes esto vivía
 * duplicado —hex por hex— dentro de los arrays `statCards` de
 * DashboardView.vue y ClinicaDashboardView.vue.
 */
const TONE_MAP: Record<StatCardTone, { accent: string; accent2: string; iconBg: string; deltaBg: string }> = {
  info: { accent: 'var(--color-info)', accent2: 'var(--color-info-2)', iconBg: '#dbe1ff', deltaBg: '#dbeafe' },
  success: { accent: 'var(--color-success)', accent2: 'var(--color-success-2)', iconBg: '#d3f9d8', deltaBg: '#dcfce7' },
  warning: { accent: 'var(--color-warning)', accent2: 'var(--color-warning-2)', iconBg: '#fff3cd', deltaBg: '#fef3c7' },
  danger: { accent: 'var(--color-danger)', accent2: 'var(--color-danger-2)', iconBg: '#ffe3e3', deltaBg: '#fee2e2' },
  violet: { accent: 'var(--color-violet)', accent2: 'var(--color-violet-2)', iconBg: '#ede9fe', deltaBg: '#ede9fe' },
};

/** Paleta clara (`--rf-*`, ver app.scss) para la variante `pastel`. */
const PASTEL_TONE_MAP: Record<StatCardTone, { tint: string; solid: string }> = {
  info: { tint: 'var(--rf-info-tint)', solid: 'var(--rf-info)' },
  success: { tint: 'var(--rf-success-tint)', solid: 'var(--rf-success)' },
  warning: { tint: 'var(--rf-warning-tint)', solid: 'var(--rf-warning)' },
  danger: { tint: 'var(--rf-danger-tint)', solid: 'var(--rf-danger)' },
  violet: { tint: '#f3e8ff', solid: '#9333ea' },
};

interface StatCardProps {
  class?: HTMLAttributes['class'];
  value: string | number;
  label: string;
  icon: Component;
  tone?: StatCardTone;
  /** 0–100, cuánto se llena el anillo de progreso (solo variante `ring`). */
  percent?: number;
  delta?: string;
  /** Variante compacta (anillo/tipografía más chicos, franja superior de acento). */
  dense?: boolean;
  /** `ring` (glassmorphism con anillo de progreso) o `pastel` (tarjeta plana con ícono en cuadrado, tema claro). */
  variant?: 'ring' | 'pastel';
  /** Últimos N valores para el mini-gráfico de tendencia (solo variante `pastel`). */
  sparkline?: number[];
  /** Texto de comparación contra el periodo anterior, ej. "↑12.5% vs periodo anterior" (solo variante `pastel`). */
  comparacion?: string;
  /** En modo oscuro la variante `pastel` usa una tarjeta neutra oscura en vez del tinte pastel (que es ilegible sobre fondo oscuro). */
  dark?: boolean;
}

const props = withDefaults(defineProps<StatCardProps>(), {
  tone: 'info',
  percent: 0,
  dense: false,
  variant: 'ring',
  dark: false,
});

const tone = computed(() => TONE_MAP[props.tone]);
const pastelTone = computed(() => PASTEL_TONE_MAP[props.tone]);
const pastelCardBg = computed(() => (props.dark ? '#161b28' : pastelTone.value.tint));

const cardStyle = computed(() => ({
  '--accent': tone.value.accent,
  '--accent-2': tone.value.accent2,
  '--icon-bg': tone.value.iconBg,
  '--icon-color': tone.value.accent,
  '--delta-bg': tone.value.deltaBg,
  '--delta-color': tone.value.accent,
}));

const ringOffset = computed(() => 169.6 - (169.6 * props.percent) / 100);
</script>

<template>
  <div
    v-if="variant === 'pastel'"
    :class="cn('stat-card-pastel rounded-2xl p-3.5 flex items-center gap-3 relative overflow-hidden', props.class)"
    :style="{ background: pastelCardBg, borderColor: dark ? 'rgba(255,255,255,0.06)' : undefined }"
  >
    <div class="stat-pastel-icon shrink-0" :style="{ background: pastelTone.solid }">
      <component :is="icon" class="w-5 h-5 text-white" />
    </div>
    <div class="flex-1 min-w-0">
      <p class="stat-pastel-value">{{ value }}</p>
      <p class="stat-pastel-label">{{ label }}</p>
      <span v-if="delta" class="stat-pastel-delta" :style="{ color: pastelTone.solid }">{{ delta }}</span>
      <p v-if="comparacion" class="stat-pastel-comparacion">{{ comparacion }}</p>
    </div>
    <Sparkline v-if="sparkline && sparkline.length > 1" :valores="sparkline" :color="pastelTone.solid" class="stat-pastel-sparkline" />
  </div>

  <div v-else :class="cn('stat-card rounded-2xl p-3.5 flex items-center gap-3', dense && 'stat-card--dense p-3', props.class)" :style="cardStyle">
    <div class="stat-card-mesh"></div>
    <div class="stat-card-glow"></div>

    <div class="stat-ring shrink-0">
      <svg viewBox="0 0 64 64" class="stat-ring-svg">
        <circle cx="32" cy="32" r="27" class="stat-ring-track" />
        <circle cx="32" cy="32" r="27" class="stat-ring-fill" :style="{ strokeDashoffset: ringOffset }" />
      </svg>
      <div class="stat-ring-icon">
        <component :is="icon" class="w-[18px] h-[18px]" />
      </div>
    </div>

    <div class="flex-1 min-w-0 relative z-10">
      <p class="stat-value">{{ value }}</p>
      <p class="stat-label">{{ label }}</p>
      <span v-if="delta" class="stat-delta-badge">{{ delta }}</span>
    </div>
  </div>
</template>

<style scoped>
/* ── Stat cards ── */
.stat-card {
  background: linear-gradient(145deg, #ffffff 0%, #fbfdff 100%);
  border: 1px solid color-mix(in srgb, var(--accent) 22%, #e2e8f0);
  box-shadow: 0 8px 24px color-mix(in srgb, var(--accent) 10%, transparent), 0 1px 0 rgba(255, 255, 255, 0.8) inset;
  transition:
    transform 0.35s cubic-bezier(0.22, 1, 0.36, 1),
    box-shadow 0.35s cubic-bezier(0.22, 1, 0.36, 1),
    border-color 0.3s ease;
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
  top: -50%;
  right: -30%;
  width: 140px;
  height: 140px;
  border-radius: 50%;
  background: radial-gradient(circle, color-mix(in srgb, var(--accent-2) 45%, transparent), transparent 70%);
  filter: blur(20px);
  opacity: 0.7;
  transition:
    opacity 0.3s ease,
    transform 0.5s ease;
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
  width: 58px;
  height: 58px;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
}
.stat-ring-svg {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
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
  transition: stroke-dashoffset 1.1s cubic-bezier(0.22, 1, 0.36, 1);
}
.stat-ring-icon {
  position: relative;
  z-index: 1;
  width: 34px;
  height: 34px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--icon-bg);
  color: var(--icon-color);
  box-shadow: 0 3px 10px color-mix(in srgb, var(--icon-color) 25%, transparent);
  transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1);
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
  letter-spacing: -0.03em;
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
  letter-spacing: 0.04em;
  position: relative;
  z-index: 10;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/*
 * Variante `dense`: la que usaba ClinicaDashboardView.vue (dashboard del
 * portal externo). Mismo componente, solo overrides de tamaño/intensidad —
 * evita mantener una segunda copia completa del stat-card.
 */
.stat-card--dense {
  background: linear-gradient(145deg, #ffffff 0%, color-mix(in srgb, var(--accent) 6%, #ffffff) 100%);
  border: 1px solid color-mix(in srgb, var(--accent) 30%, #e2e8f0);
  border-top: 3px solid var(--accent);
  box-shadow: 0 8px 24px color-mix(in srgb, var(--accent) 15%, transparent), 0 1px 0 rgba(255, 255, 255, 0.8) inset;
}
.stat-card--dense .stat-card-mesh {
  background: radial-gradient(circle at 100% 0%, color-mix(in srgb, var(--accent) 18%, transparent), transparent 55%);
}
.stat-card--dense .stat-card-glow {
  background: radial-gradient(circle, color-mix(in srgb, var(--accent-2) 55%, transparent), transparent 70%);
  opacity: 0.8;
}
.stat-card--dense:hover {
  box-shadow: 0 20px 40px color-mix(in srgb, var(--accent) 30%, transparent);
  border-color: var(--accent);
}
.stat-card--dense .stat-ring {
  width: 52px;
  height: 52px;
}
.stat-card--dense .stat-ring-icon {
  width: 30px;
  height: 30px;
}
.stat-card--dense .stat-delta-badge {
  font-size: 8.5px;
  padding: 2px 7px;
  margin-top: 3px;
}
.stat-card--dense .stat-value {
  font-size: 18px;
}
.stat-card--dense .stat-label {
  font-size: 9.5px;
}

/* ── Variante pastel: tarjeta plana, tema claro ── */
.stat-card-pastel {
  border: 1px solid rgba(15, 23, 42, 0.04);
  transition: transform 0.25s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.25s ease;
}
.stat-card-pastel:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
}
.stat-card-pastel:hover .stat-pastel-icon {
  transform: scale(1.1) rotate(-4deg);
}
.stat-card-pastel:hover .stat-pastel-sparkline {
  opacity: 1;
}
.stat-pastel-icon {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1);
}
.stat-pastel-value {
  font-size: 22px;
  font-weight: 800;
  line-height: 1.15;
  color: #1e293b;
}
.stat-pastel-label {
  font-size: 10.5px;
  font-weight: 700;
  color: #64748b;
  margin-top: 1px;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.stat-pastel-delta {
  display: inline-block;
  font-size: 10px;
  font-weight: 700;
  margin-top: 3px;
}
.stat-pastel-comparacion {
  font-size: 9.5px;
  font-weight: 600;
  color: #94a3b8;
  margin-top: 2px;
}
.stat-pastel-sparkline {
  position: absolute;
  right: 10px;
  bottom: 10px;
  opacity: 0.6;
  pointer-events: none;
  transition: opacity 0.25s ease;
}

/* En modo oscuro (ver ClinicaDashboardView.vue), estas tarjetas viven sobre
   fondo oscuro con :style inline; el texto neutro necesita su propia rama. */
.dark .stat-pastel-value { color: #e2e8f0; }
.dark .stat-pastel-label { color: #94a3b8; }
.dark .stat-pastel-comparacion { color: #64748b; }
</style>
