<template>
  <div class="mapa-clinicas-wrap rounded-2xl overflow-hidden relative">
    <!-- ── Header ── -->
    <div class="mapa-header">
      <p class="mapa-title">Mapa de clínicas</p>
      <div class="flex items-center gap-2">
        <el-select v-model="ciudadFiltro" size="small" class="mapa-ciudad-select">
          <el-option value="" label="Todas las ciudades" />
          <el-option v-for="c in ciudadesDisponibles" :key="c" :label="c" :value="c" />
        </el-select>
      </div>
    </div>

    <!-- ── Mapa ── -->
    <div ref="mapEl" class="mapa-canvas"></div>

    <!-- ── Leyenda ── -->
    <div class="mapa-legend">
      <p class="mapa-legend-title">Estado</p>
      <div v-for="item in leyenda" :key="item.estado" class="mapa-legend-row">
        <span class="mapa-legend-dot" :style="{ background: item.color }"></span>
        <span>{{ item.label }}</span>
      </div>
    </div>

    <div v-if="!gruposConCoordenadas.length" class="mapa-empty">
      <component :is="MapPinOffIcon" class="w-6 h-6" />
      <span>Sin clínicas geocodificadas todavía</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { MapPinOff as MapPinOffIcon } from '@lucide/vue';
import { useLayoutStore } from '@/stores/layout';

interface ClinicaMapa {
  id: number;
  nombre: string;
  ciudad: string | null;
  estado: 'pendiente' | 'activa' | 'rechazada';
  latitud?: number | null;
  longitud?: number | null;
}

const props = defineProps<{ clinicas: ClinicaMapa[] }>();

const layout = useLayoutStore();
const mapEl = ref<HTMLElement | null>(null);
const ciudadFiltro = ref('');

const ESTADO_COLOR: Record<string, string> = {
  activa: '#22c55e',
  pendiente: '#f59e0b',
  rechazada: '#ef4444',
};

const ESTADO_LABEL: Record<string, string> = {
  activa: 'Activas',
  pendiente: 'Pendientes',
  rechazada: 'Rechazadas',
};

const leyenda = computed(() => (['activa', 'pendiente', 'rechazada'] as const).map(estado => ({
  estado,
  label: ESTADO_LABEL[estado],
  color: ESTADO_COLOR[estado],
})));

const conCoordenadas = computed(() => props.clinicas.filter(c => c.latitud != null && c.longitud != null));

const ciudadesDisponibles = computed(() => {
  const set = new Set<string>();
  conCoordenadas.value.forEach(c => { if (c.ciudad) set.add(c.ciudad); });
  return Array.from(set).sort();
});

/** Agrupa por ciudad+estado: una burbuja por combinación, con el conteo real. */
const gruposConCoordenadas = computed(() => {
  const filtradas = ciudadFiltro.value
    ? conCoordenadas.value.filter(c => c.ciudad === ciudadFiltro.value)
    : conCoordenadas.value;

  const grupos = new Map<string, { ciudad: string; estado: string; lat: number; lon: number; count: number }>();

  filtradas.forEach(c => {
    const key = `${c.ciudad}__${c.estado}`;
    const existente = grupos.get(key);
    if (existente) {
      existente.count++;
    } else {
      grupos.set(key, { ciudad: c.ciudad ?? '', estado: c.estado, lat: c.latitud as number, lon: c.longitud as number, count: 1 });
    }
  });

  return Array.from(grupos.values());
});

let map: L.Map | null = null;
let tileLayer: L.TileLayer | null = null;
let markersLayer: L.LayerGroup | null = null;
let focoMarker: L.Marker | null = null;

const TILE_DARK = 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png';
const TILE_LIGHT = 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png';
const ATTRIBUTION = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>';

function crearIcono(color: string, count: number): L.DivIcon {
  const size = Math.min(56, 30 + count * 4);
  return L.divIcon({
    className: '',
    html: `<div class="mapa-bubble" style="width:${size}px;height:${size}px;background:${color};box-shadow:0 0 0 4px ${color}33, 0 4px 14px rgba(0,0,0,.35);">${count}</div>`,
    iconSize: [size, size],
    iconAnchor: [size / 2, size / 2],
  });
}

function pintarMarcadores() {
  if (!map || !markersLayer) return;
  markersLayer.clearLayers();

  gruposConCoordenadas.value.forEach(g => {
    const marker = L.marker([g.lat, g.lon], { icon: crearIcono(ESTADO_COLOR[g.estado] ?? '#94a3b8', g.count) });
    marker.bindTooltip(`<strong>${g.ciudad}</strong><br>${ESTADO_LABEL[g.estado] ?? g.estado}: ${g.count}`, { direction: 'top', offset: [0, -6] });
    markersLayer?.addLayer(marker);
  });

  if (gruposConCoordenadas.value.length) {
    const bounds = L.latLngBounds(gruposConCoordenadas.value.map(g => [g.lat, g.lon] as [number, number]));
    map.fitBounds(bounds, { padding: [40, 40], maxZoom: 11 });
  }
}

function aplicarTile() {
  if (!map) return;
  if (tileLayer) {
    map.removeLayer(tileLayer);
  }
  tileLayer = L.tileLayer(layout.isDarkMode ? TILE_DARK : TILE_LIGHT, {
    attribution: ATTRIBUTION,
    subdomains: 'abcd',
    maxZoom: 19,
  });
  tileLayer.addTo(map);
}

onMounted(async () => {
  await nextTick();
  if (!mapEl.value) return;

  map = L.map(mapEl.value, {
    zoomControl: false,
    center: [4.0, -76.0],
    zoom: 7,
    attributionControl: false,
  });
  L.control.attribution({ position: 'bottomright', prefix: false }).addTo(map);
  L.control.zoom({ position: 'bottomright' }).addTo(map);

  markersLayer = L.layerGroup().addTo(map);
  aplicarTile();
  pintarMarcadores();
});

onBeforeUnmount(() => {
  map?.remove();
  map = null;
});

watch(() => layout.isDarkMode, aplicarTile);
watch(gruposConCoordenadas, pintarMarcadores);

function crearIconoFoco(): L.DivIcon {
  return L.divIcon({
    className: '',
    html: '<div class="mapa-foco-pulso"></div><div class="mapa-foco-punto"></div>',
    iconSize: [1, 1],
    iconAnchor: [0, 0],
  });
}

/** Centra el mapa en una clínica puntual (ej: al seleccionarla en la tabla), sin importar el filtro de ciudad activo. */
function enfocarClinica(clinica: { latitud?: number | null; longitud?: number | null; ciudad?: string | null }): void {
  if (!map || clinica.latitud == null || clinica.longitud == null) return;

  if (ciudadFiltro.value && clinica.ciudad !== ciudadFiltro.value) {
    ciudadFiltro.value = '';
  }

  if (focoMarker) {
    map.removeLayer(focoMarker);
    focoMarker = null;
  }

  const punto: [number, number] = [clinica.latitud, clinica.longitud];
  focoMarker = L.marker(punto, { icon: crearIconoFoco(), interactive: false, zIndexOffset: 1000 }).addTo(map);
  map.flyTo(punto, 13, { duration: 1.1 });
}

/** Vuelve a encuadrar el mapa para mostrar todas las clínicas visibles (ej: al deseleccionar). */
function restablecerVista(): void {
  if (!map) return;
  if (focoMarker) {
    map.removeLayer(focoMarker);
    focoMarker = null;
  }
  if (!gruposConCoordenadas.value.length) return;
  const bounds = L.latLngBounds(gruposConCoordenadas.value.map(g => [g.lat, g.lon] as [number, number]));
  map.flyToBounds(bounds, { padding: [40, 40], maxZoom: 11, duration: 1.1 });
}

defineExpose({ enfocarClinica, restablecerVista });
</script>

<style scoped>
.mapa-clinicas-wrap {
  background: #fff;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 16px rgba(22,70,142,.08);
  display: flex;
  flex-direction: column;
}
.dark .mapa-clinicas-wrap { background: #161b28; border-color: rgba(255,255,255,0.06); }

.mapa-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 12px 14px 8px;
}
.mapa-title { font-size: 13px; font-weight: 800; color: #1e293b; }
.dark .mapa-title { color: #e2e8f0; }
.mapa-ciudad-select { width: 160px; }

.mapa-canvas {
  flex: 1;
  min-height: 320px;
  background: #0b0e17;
}

.mapa-empty {
  position: absolute;
  inset: 0;
  top: 44px;
  display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px;
  color: #94a3b8;
  font-size: 12px; font-weight: 600;
  background: rgba(11,14,23,0.85);
  pointer-events: none;
}

.mapa-legend {
  position: absolute;
  left: 12px; bottom: 12px;
  background: rgba(255,255,255,0.92);
  backdrop-filter: blur(6px);
  border: 1px solid rgba(15,23,42,0.08);
  box-shadow: 0 4px 14px rgba(15,23,42,.12);
  border-radius: 12px;
  padding: 8px 12px;
  z-index: 500;
}
.dark .mapa-legend {
  background: rgba(15,20,32,0.88);
  border-color: rgba(255,255,255,0.1);
  box-shadow: none;
}
.mapa-legend-title {
  font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em;
  color: rgba(15,23,42,0.5); margin-bottom: 5px;
}
.dark .mapa-legend-title { color: rgba(255,255,255,0.5); }
.mapa-legend-row {
  display: flex; align-items: center; gap: 6px;
  font-size: 11px; font-weight: 600; color: #1e293b;
  padding: 1.5px 0;
}
.dark .mapa-legend-row { color: #fff; }
.mapa-legend-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }

:deep(.mapa-bubble) {
  display: flex; align-items: center; justify-content: center;
  border-radius: 50%;
  color: #fff;
  font-size: 11px; font-weight: 800;
  border: 2px solid rgba(255,255,255,0.85);
}

:deep(.mapa-foco-pulso) {
  position: absolute;
  width: 34px; height: 34px;
  left: -17px; top: -17px;
  border-radius: 50%;
  background: rgba(59,130,246,.35);
  animation: mapa-foco-ping 1.4s cubic-bezier(0,0,.2,1) infinite;
}
:deep(.mapa-foco-punto) {
  position: absolute;
  width: 12px; height: 12px;
  left: -6px; top: -6px;
  border-radius: 50%;
  background: #3b82f6;
  border: 2px solid #fff;
  box-shadow: 0 0 0 2px #3b82f6, 0 2px 8px rgba(0,0,0,.4);
}
@keyframes mapa-foco-ping {
  0% { transform: scale(.4); opacity: 1; }
  100% { transform: scale(1.9); opacity: 0; }
}
:deep(.leaflet-control-zoom) {
  border: none !important;
  box-shadow: 0 4px 14px rgba(0,0,0,.35) !important;
}
:deep(.leaflet-control-zoom a) {
  background: rgba(15,20,32,0.9) !important;
  color: #fff !important;
  border-color: rgba(255,255,255,0.1) !important;
}
:deep(.leaflet-control-zoom a:hover) {
  background: rgba(15,20,32,1) !important;
}
:deep(.leaflet-control-attribution) {
  background: rgba(255,255,255,0.55) !important;
  color: rgba(15,23,42,0.4) !important;
  font-size: 8px !important;
  line-height: 1.4 !important;
  padding: 0 4px !important;
  border-radius: 4px 0 0 0 !important;
}
:deep(.leaflet-control-attribution a) { color: rgba(15,23,42,0.5) !important; }
.dark :deep(.leaflet-control-attribution) {
  background: rgba(15,20,32,0.55) !important;
  color: rgba(255,255,255,0.35) !important;
}
.dark :deep(.leaflet-control-attribution a) { color: rgba(255,255,255,0.5) !important; }
:deep(.leaflet-tooltip) {
  background: #1e293b !important;
  color: #fff !important;
  border: 1px solid rgba(255,255,255,0.12) !important;
  border-radius: 8px !important;
  font-size: 11px !important;
  padding: 6px 10px !important;
}
:deep(.leaflet-tooltip-top:before) { border-top-color: #1e293b !important; }
</style>
