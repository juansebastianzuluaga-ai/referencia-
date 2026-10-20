<template>
  <div ref="wrap" class="periodo-seg" :class="{ 'periodo-seg--inv': inverso }">
    <button
      v-for="o in opciones" :key="o[0]"
      type="button"
      class="periodo-seg-btn" :class="{ 'periodo-seg-btn--on': modelValue === o[0] }"
      :title="o[2]"
      @click="elegir(o[0])"
    >{{ o[1] }}</button>
    <!-- Chip con la fecha ancla elegida — abre el popover para cambiarla -->
    <button
      v-if="modelValue !== 'todo'"
      type="button"
      class="periodo-seg-btn periodo-seg-fecha"
      :class="{ 'periodo-seg-btn--on': abierto }"
      :title="'Cambiar ' + pickerPlaceholder.toLowerCase()"
      @click="togglePop()"
    >
      <component :is="CalendarIcon" class="periodo-seg-cal" />
      {{ fechaTexto }}
    </button>
    <Teleport to="body">
      <div v-if="abierto" ref="pop" class="periodo-pop" :style="popStyle">
        <p class="periodo-pop-label">Elige {{ pickerPlaceholder }}</p>
        <el-date-picker
          ref="pickerEl"
          :model-value="fecha || null"
          :type="pickerTipo"
          size="small"
          :clearable="false"
          :editable="false"
          :format="pickerFormato"
          value-format="YYYY-MM-DD"
          :placeholder="pickerPlaceholder"
          style="width: 100%"
          @update:model-value="onFecha"
        />
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, nextTick } from 'vue';
import { onClickOutside } from '@vueuse/core';
import { Calendar as CalendarIcon } from '@lucide/vue';

const props = defineProps<{ modelValue: string; fecha?: string; sinTodo?: boolean; inverso?: boolean }>();
const emit = defineEmits<{ 'update:modelValue': [v: string]; 'update:fecha': [v: string] }>();

const opcionesBase = [
  ['dia', 'Día', 'Un día específico'],
  ['semana', 'Sem', 'Una semana específica'],
  ['mes', 'Mes', 'Un mes específico'],
  ['anio', 'Año', 'Un año específico'],
  ['todo', 'Todo', 'Todo el histórico'],
] as const;

const opciones = computed(() => (props.sinTodo ? opcionesBase.slice(0, 4) : opcionesBase));

const pickerTipo = computed(() =>
  ({ dia: 'date', semana: 'week', mes: 'month', anio: 'year' } as Record<string, string>)[props.modelValue] ?? 'date');
const pickerFormato = computed(() =>
  ({ dia: 'DD/MM/YYYY', semana: '[Sem] ww', mes: 'MM/YYYY', anio: 'YYYY' } as Record<string, string>)[props.modelValue] ?? 'DD/MM/YYYY');
const pickerPlaceholder = computed(() =>
  ({ dia: 'el día', semana: 'la semana', mes: 'el mes', anio: 'el año' } as Record<string, string>)[props.modelValue] ?? 'la fecha');

/** Texto corto del chip: 19/10/26 · 12–18/10 · 10/2026 · 2026 */
const fechaTexto = computed(() => {
  if (!props.fecha) return 'Fecha';
  const [y, m, d] = props.fecha.split('-').map(Number);
  if (props.modelValue === 'anio') return String(y);
  if (props.modelValue === 'mes') return `${String(m).padStart(2, '0')}/${y}`;
  if (props.modelValue === 'semana') {
    const fin = new Date(y, m - 1, d + 6);
    return `${String(d).padStart(2, '0')}/${String(m).padStart(2, '0')}–${String(fin.getDate()).padStart(2, '0')}/${String(fin.getMonth() + 1).padStart(2, '0')}`;
  }
  return `${String(d).padStart(2, '0')}/${String(m).padStart(2, '0')}/${String(y).slice(2)}`;
});

const hoyIso = () => {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
};

function elegir(p: string) {
  emit('update:modelValue', p);
  if (p === 'todo') {
    abierto.value = false;
    return;
  }
  if (!props.fecha) emit('update:fecha', hoyIso());
  togglePop(true);
}

// ── Popover flotante con el picker ──
const wrap = ref<HTMLElement | null>(null);
const pop = ref<HTMLElement | null>(null);
const pickerEl = ref<{ focus?: () => void } | null>(null);
const abierto = ref(false);
const popStyle = ref<Record<string, string>>({});

// El calendario de Element Plus también se teleporta al body — ignorarlo
// para que elegir una fecha no cierre el popover antes de emitir.
onClickOutside(wrap, () => { abierto.value = false; }, { ignore: [pop, '.el-picker__popper'] });

function togglePop(force?: boolean) {
  abierto.value = force ?? !abierto.value;
  if (!abierto.value || !wrap.value) return;
  const r = wrap.value.getBoundingClientRect();
  const ancho = 172;
  popStyle.value = {
    top: `${r.bottom + 6}px`,
    left: `${Math.max(8, Math.min(r.left, window.innerWidth - ancho - 8))}px`,
    width: `${ancho}px`,
  };
  nextTick(() => pickerEl.value?.focus?.());
}

function onFecha(v: string | null) {
  if (v) emit('update:fecha', v);
  abierto.value = false;
}
</script>

<style scoped>
.periodo-seg {
  display: flex; align-items: center; gap: 2px;
  background: #eef2f8;
  border: 1px solid #dde6f2;
  border-radius: 999px;
  padding: 2px;
  flex-shrink: 0;
}
.periodo-seg-btn {
  font-size: 8.5px; font-weight: 800;
  padding: 2px 7px; border-radius: 999px;
  color: #64748b;
  background: transparent; border: none; cursor: pointer;
  transition: background .15s ease, color .15s ease;
  white-space: nowrap;
  line-height: 1.4;
  display: inline-flex; align-items: center; gap: 3px;
}
.periodo-seg-btn:hover { color: #16468e; }
.periodo-seg-btn--on { background: #16468e; color: #fff; box-shadow: 0 1px 4px rgba(22,70,142,.3); }
.periodo-seg-fecha { color: #16468e; }
.periodo-seg-cal { width: 9px; height: 9px; }
.dark .periodo-seg { background: rgba(255,255,255,.06); border-color: rgba(255,255,255,.1); }
.dark .periodo-seg-btn { color: #94a3b8; }
.dark .periodo-seg-btn:hover { color: #cbd5e1; }
.dark .periodo-seg-btn--on { background: #3b82f6; color: #fff; }
.dark .periodo-seg-fecha { color: #93c5fd; }

/* Variante sobre el encabezado navy */
.periodo-seg--inv { background: rgba(255,255,255,.12); border-color: rgba(255,255,255,.2); }
.periodo-seg--inv .periodo-seg-btn { color: rgba(255,255,255,.72); }
.periodo-seg--inv .periodo-seg-btn:hover { color: #fff; }
.periodo-seg--inv .periodo-seg-btn--on { background: #fff; color: #16468e; box-shadow: 0 1px 4px rgba(0,0,0,.18); }
.periodo-seg--inv .periodo-seg-fecha { color: #fff; }
</style>

<style>
/* El popover va teleportado al body — estilos globales */
.periodo-pop {
  position: fixed;
  z-index: 4000;
  background: #fff;
  border: 1px solid #e8edf4;
  border-radius: 10px;
  box-shadow: 0 14px 36px rgba(15, 23, 42, 0.16);
  padding: 8px;
}
.dark .periodo-pop { background: #161b28; border-color: rgba(255,255,255,0.1); }
.periodo-pop-label {
  font-size: 9px; font-weight: 800;
  text-transform: uppercase; letter-spacing: .07em;
  color: #94a3b8;
  margin: 0 0 5px 2px;
}
</style>
