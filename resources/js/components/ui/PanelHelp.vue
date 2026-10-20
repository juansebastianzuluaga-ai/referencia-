<script setup lang="ts">
import { ref } from 'vue';
import { CircleHelp } from '@lucide/vue';

const props = defineProps<{
  /** Texto que explica para qué sirve el panel. */
  texto: string;
  /** 'derecha' fuerza que el tooltip se ancle al borde derecho del icono. */
  alinear?: 'izquierda' | 'derecha';
  /** true cuando va dentro de un header navy/oscuro — el icono pasa a tono claro. */
  inverso?: boolean;
}>();

const icono = ref<HTMLElement | null>(null);
const tip = ref<{ x: number; y: number; arriba: boolean } | null>(null);

const ANCHO_TIP = 244;
const ALTO_TIP_EST = 130;

// El tooltip se teleporta a <body> con position:fixed — los paneles tienen
// overflow:hidden y un tooltip absoluto dentro quedaba recortado. Las
// coordenadas salen del getBoundingClientRect del icono, igual que el
// tooltip flotante del SLA en el dashboard.
function mostrar() {
  if (!icono.value) return;
  const r = icono.value.getBoundingClientRect();
  const alDerecha = props.alinear === 'derecha' || r.left + ANCHO_TIP > window.innerWidth - 8;
  const x = alDerecha
    ? Math.max(8, r.right - ANCHO_TIP)
    : Math.min(Math.max(8, r.left - 6), window.innerWidth - ANCHO_TIP - 8);
  const arriba = r.bottom + ALTO_TIP_EST > window.innerHeight;

  tip.value = { x, y: arriba ? r.top - 8 : r.bottom + 8, arriba };
}

function ocultar() {
  tip.value = null;
}
</script>

<template>
  <span
    ref="icono"
    class="panel-help"
    :class="{ 'panel-help--inverso': inverso }"
    tabindex="0"
    @mouseenter="mostrar"
    @mouseleave="ocultar"
    @focus="mostrar"
    @blur="ocultar"
  >
    <CircleHelp class="w-3.5 h-3.5" />
  </span>
  <Teleport to="body">
    <div
      v-if="tip"
      class="panel-help-tip"
      :class="{ 'panel-help-tip--arriba': tip.arriba }"
      :style="{ left: tip.x + 'px', top: tip.y + 'px' }"
      role="tooltip"
    >
      {{ texto }}
    </div>
  </Teleport>
</template>

<style scoped>
.panel-help {
  display: inline-flex;
  align-items: center;
  flex-shrink: 0;
  margin-left: 5px;
  color: #94a3b8;
  cursor: help;
  transition: color 0.15s;
}
.panel-help:hover,
.panel-help:focus-visible {
  color: #3b82f6;
}
.panel-help--inverso {
  color: rgba(255, 255, 255, 0.55);
}
.panel-help--inverso:hover,
.panel-help--inverso:focus-visible {
  color: #fff;
}

/* Al vivir en <body>, position:fixed usa coordenadas de viewport — las
   mismas que devuelve getBoundingClientRect. */
.panel-help-tip {
  position: fixed;
  z-index: 9999;
  width: max-content;
  max-width: 244px;
  padding: 8px 11px;
  border-radius: 10px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  color: #334155;
  font-size: 11px;
  font-weight: 400;
  line-height: 1.55;
  text-align: left;
  white-space: normal;
  pointer-events: none;
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.16), 0 2px 6px rgba(15, 23, 42, 0.08);
  animation: panel-help-in 0.14s ease;
}
.panel-help-tip--arriba {
  transform: translateY(-100%);
}
.dark .panel-help-tip {
  background: #1e293b;
  border-color: #334155;
  color: #e2e8f0;
  box-shadow: 0 12px 28px rgba(0, 0, 0, 0.45);
}

@keyframes panel-help-in {
  from { opacity: 0; }
  to { opacity: 1; }
}
</style>
