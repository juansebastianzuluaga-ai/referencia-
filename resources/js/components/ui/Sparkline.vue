<script setup lang="ts">
import { computed } from 'vue';

/**
 * Mini-gráfico de línea sin librería: un `<polyline>` normalizado al
 * viewBox. Pensado para vivir dentro de una tarjeta chica (stat card), no
 * para reemplazar los gráficos reales (ApexCharts/Highcharts) que ya tiene
 * el dashboard. `relleno` pinta un área degradada bajo la línea.
 */
const props = withDefaults(defineProps<{
  valores: number[];
  color?: string;
  ancho?: number;
  alto?: number;
  relleno?: boolean;
}>(), {
  color: '#6366f1',
  ancho: 64,
  alto: 24,
  relleno: false,
});

const gradId = `sg-${Math.random().toString(36).slice(2, 8)}`;

const puntos = computed(() => {
  const vals = props.valores.length ? props.valores : [0];
  const max = Math.max(...vals, 1);
  const min = Math.min(...vals, 0);
  const rango = Math.max(max - min, 1);
  const paso = vals.length > 1 ? props.ancho / (vals.length - 1) : props.ancho;

  return vals
    .map((v, i) => {
      const x = i * paso;
      const y = props.alto - ((v - min) / rango) * props.alto;
      return `${x.toFixed(1)},${y.toFixed(1)}`;
    })
    .join(' ');
});

const puntosArea = computed(() => `0,${props.alto} ${puntos.value} ${props.ancho},${props.alto}`);
</script>

<template>
  <svg :viewBox="`0 0 ${ancho} ${alto}`" :width="ancho" :height="alto" preserveAspectRatio="none" class="shrink-0">
    <defs>
      <linearGradient :id="gradId" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0%" :stop-color="color" stop-opacity="0.35" />
        <stop offset="100%" :stop-color="color" stop-opacity="0.02" />
      </linearGradient>
    </defs>
    <polygon v-if="relleno" :points="puntosArea" :fill="`url(#${gradId})`" />
    <polyline :points="puntos" fill="none" :stroke="color" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
  </svg>
</template>
