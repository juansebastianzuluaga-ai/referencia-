<template>
  <div ref="wrap" class="drf">
    <button type="button" class="drf-trigger" :class="{ 'drf-trigger-active': !!modelValue }" @click="abierto = !abierto">
      <component :is="CalendarIcon" class="w-3.5 h-3.5" />
      <span class="drf-label">{{ etiqueta }}</span>
      <component :is="ChevronDownIcon" class="w-3.5 h-3.5 drf-caret" :class="{ 'rotate-180': abierto }" />
    </button>

    <transition name="drf-drop">
      <div v-if="abierto" class="drf-panel">
        <button
          v-for="p in presets"
          :key="p.label"
          type="button"
          class="drf-preset"
          :class="{ 'drf-preset-active': presetActivo === p.label }"
          @click="aplicarPreset(p)"
        >
          {{ p.label }}
        </button>

        <div class="drf-divider"></div>

        <div class="drf-custom">
          <p class="drf-custom-label">Rango personalizado</p>
          <el-date-picker
            v-model="custom"
            type="daterange"
            unlink-panels
            range-separator="–"
            start-placeholder="Desde"
            end-placeholder="Hasta"
            size="small"
            format="DD/MM/YYYY"
            value-format="YYYY-MM-DD"
            style="width: 100%"
            @change="onCustom"
          />
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { onClickOutside } from '@vueuse/core';
import { Calendar as CalendarIcon, ChevronDown as ChevronDownIcon } from '@lucide/vue';

const props = defineProps<{ modelValue: [string, string] | null }>();
const emit = defineEmits<{
  'update:modelValue': [val: [string, string] | null];
  change: [];
}>();

const abierto = ref(false);
const wrap = ref<HTMLElement | null>(null);
const custom = ref<[string, string] | null>(props.modelValue);
const presetActivo = ref('');

onClickOutside(wrap, () => { abierto.value = false; });

watch(() => props.modelValue, (val) => {
  custom.value = val;
  if (!val) presetActivo.value = '';
});

type Preset = { label: string; desde: () => Date; hasta: () => Date };

const hoy = () => { const d = new Date(); d.setHours(0, 0, 0, 0); return d; };

const presets: Preset[] = [
  { label: 'Hoy', desde: hoy, hasta: () => new Date() },
  { label: 'Últimos 7 días', desde: () => diasAtras(6), hasta: () => new Date() },
  { label: 'Últimos 14 días', desde: () => diasAtras(13), hasta: () => new Date() },
  { label: 'Últimos 30 días', desde: () => diasAtras(29), hasta: () => new Date() },
  { label: 'Este mes', desde: () => new Date(new Date().getFullYear(), new Date().getMonth(), 1), hasta: () => new Date() },
  { label: 'Mes anterior', desde: () => new Date(new Date().getFullYear(), new Date().getMonth() - 1, 1), hasta: () => new Date(new Date().getFullYear(), new Date().getMonth(), 0) },
];

function diasAtras(n: number): Date {
  const d = hoy();
  d.setDate(d.getDate() - n);
  return d;
}

const iso = (d: Date) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
const corto = (s: string) => {
  const [y, m, d] = s.split('-');
  return `${d}/${m}/${y.slice(2)}`;
};

const etiqueta = computed(() => {
  if (!props.modelValue) return 'Todo el período';
  return `${corto(props.modelValue[0])} – ${corto(props.modelValue[1])}`;
});

function aplicarPreset(p: Preset) {
  presetActivo.value = p.label;
  emitRange([iso(p.desde()), iso(p.hasta())]);
}

function onCustom(val: [string, string] | null) {
  presetActivo.value = '';
  emitRange(val);
}

function emitRange(val: [string, string] | null) {
  custom.value = val;
  emit('update:modelValue', val);
  emit('change');
  abierto.value = false;
}
</script>

<style scoped>
.drf { position: relative; }
.drf-trigger {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  height: 32px;
  padding: 0 12px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #64748b;
  font-size: 12px;
  font-weight: 600;
  font-family: inherit;
  cursor: pointer;
  white-space: nowrap;
  transition: all .15s ease;
}
.drf-trigger:hover { border-color: #16468e; color: #16468e; }
.drf-trigger-active { border-color: #c7d8f5; background: #f0f6ff; color: #16468e; }
.drf-label { max-width: 160px; overflow: hidden; text-overflow: ellipsis; }
.drf-caret { transition: transform .15s ease; opacity: .6; }
.dark .drf-trigger { background: #161b28; border-color: rgba(255,255,255,0.08); color: #cbd5e1; }
.dark .drf-trigger:hover { border-color: #4f46e5; color: #a5b4fc; }
.dark .drf-trigger-active { background: rgba(99,102,241,0.12); border-color: rgba(99,102,241,0.35); color: #a5b4fc; }

.drf-panel {
  position: absolute;
  top: calc(100% + 6px);
  left: 0;
  min-width: 210px;
  background: #fff;
  border: 1px solid #e8edf4;
  border-radius: 12px;
  box-shadow: 0 14px 36px rgba(15, 23, 42, 0.14);
  padding: 6px;
  z-index: 200;
}
.dark .drf-panel { background: #161b28; border-color: rgba(255,255,255,0.08); }

.drf-preset {
  display: block;
  width: 100%;
  text-align: left;
  padding: 7px 10px;
  border: none;
  border-radius: 8px;
  background: transparent;
  font-size: 12px;
  font-weight: 600;
  color: #475569;
  font-family: inherit;
  cursor: pointer;
  transition: background .12s ease, color .12s ease;
}
.drf-preset:hover { background: #f1f5f9; color: #1e293b; }
.drf-preset-active { background: #e0ecff; color: #16468e; }
.dark .drf-preset { color: #cbd5e1; }
.dark .drf-preset:hover { background: rgba(255,255,255,0.05); color: #fff; }
.dark .drf-preset-active { background: rgba(99,102,241,0.15); color: #a5b4fc; }

.drf-divider { height: 1px; background: #eef2f7; margin: 6px 4px; }
.dark .drf-divider { background: rgba(255,255,255,0.07); }

.drf-custom { padding: 2px 4px 4px; }
.drf-custom-label {
  font-size: 9.5px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .07em;
  color: #94a3b8;
  margin: 0 0 5px 6px;
}

.drf-drop-enter-active { transition: opacity .15s ease, transform .15s ease; }
.drf-drop-leave-active { transition: opacity .1s ease; }
.drf-drop-enter-from { opacity: 0; transform: translateY(-4px); }
.drf-drop-leave-to { opacity: 0; }
</style>
