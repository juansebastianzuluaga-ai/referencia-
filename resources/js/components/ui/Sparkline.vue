<script setup lang="ts">
import { computed } from 'vue';

/**
 * Mini-gráfico de línea sin librería: un `<polyline>` normalizado al
 * viewBox. Pensado para vivir dentro de una tarjeta chica (stat card), no
 * para reemplazar los gráficos reales (ApexCharts/Highcharts) que ya tiene
 * el dashboard.
 */
const props = withDefaults(defineProps<{
  valores: number[];
  color?: string;
  ancho?: number;
  alto?: number;
}>(), {
  color: '#6366f1',
  ancho: 64,
  alto: 24,
});

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
</script>

<template>
  <svg :viewBox="`0 0 ${ancho} ${alto}`" :width="ancho" :height="alto" preserveAspectRatio="none" class="shrink-0">
    <polyline :points="puntos" fill="none" :stroke="color" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
  </svg>
</template>
