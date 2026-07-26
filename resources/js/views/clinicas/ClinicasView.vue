<template>
  <div class="min-h-full p-4 sm:p-6 lg:p-8 clinic-page">

    <!-- Encabezado -->
    <div class="flex flex-col xl:flex-row xl:items-end justify-between gap-5 mb-7">
      <div>
        <p class="text-xs font-semibold tracking-[0.18em] uppercase text-[#4778b8] mb-2">Directorio institucional</p>
        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-[#0d2d5e]">Clínicas externas</h1>
        <p class="text-sm text-slate-500 mt-1">Gestión y aprobación de instituciones registradas</p>
      </div>
      <div class="grid grid-cols-3 gap-2 sm:gap-3 w-full xl:w-auto">
        <div class="metric-chip metric-chip-pending">
          <p class="text-xl font-bold text-yellow-700">{{ resumen.pendientes }}</p>
          <p class="text-[11px] font-medium text-yellow-700/80">Pendientes</p>
        </div>
        <div class="metric-chip metric-chip-active">
          <p class="text-xl font-bold text-emerald-700">{{ resumen.activas }}</p>
          <p class="text-[11px] font-medium text-emerald-700/80">Activas</p>
        </div>
        <div class="metric-chip metric-chip-rejected">
          <p class="text-xl font-bold text-rose-600">{{ resumen.rechazadas }}</p>
          <p class="text-[11px] font-medium text-rose-600/80">Rechazadas</p>
        </div>
      </div>
    </div>

    <!-- Filtros -->
    <div class="clinic-glass-panel p-3 sm:p-4 mb-6 flex flex-wrap gap-3 items-center">
      <el-input
        v-model="filtro.buscar"
        placeholder="Buscar por nombre, NIT o ciudad..."
        class="w-full sm:w-72"
        clearable
        size="small"
        :prefix-icon="SearchIcon"
      />
      <el-select v-model="filtro.estado" placeholder="Estado" size="small" class="w-full sm:w-44" clearable>
        <el-option label="Pendientes" value="pendiente" />
        <el-option label="Activas" value="activa" />
        <el-option label="Rechazadas" value="rechazada" />
      </el-select>
      <el-button size="small" @click="cargar">
        <component :is="RefreshIcon" class="w-3.5 h-3.5 mr-1" />
        Actualizar
      </el-button>
    </div>

    <!-- Loading -->
    <div v-if="cargando" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
      <div v-for="i in 3" :key="i" class="clinic-card rounded-2xl p-5">
        <div class="flex items-center gap-2 mb-3">
          <div class="shimmer-box" style="width:40px; height:40px; border-radius:10px;"></div>
          <div class="flex-1 space-y-1.5">
            <div class="shimmer-bar" style="width:70%; height:12px;"></div>
            <div class="shimmer-bar" style="width:50%; height:9px;"></div>
          </div>
          <div class="shimmer-box" style="width:50px; height:20px; border-radius:999px;"></div>
        </div>
        <div class="space-y-2">
          <div class="shimmer-bar" style="width:90%; height:9px;"></div>
          <div class="shimmer-bar" style="width:75%; height:9px;"></div>
          <div class="shimmer-bar" style="width:60%; height:9px;"></div>
          <div class="shimmer-bar" style="width:85%; height:9px;"></div>
        </div>
      </div>
    </div>

    <!-- Vacío -->
    <div v-else-if="clinicasFiltradas.length === 0" class="clinic-empty-wrap">
      <div class="clinic-empty-glow"></div>
      <div class="clinic-empty-icon w-16 h-16 rounded-2xl flex items-center justify-center mb-4 relative z-10">
        <component :is="BuildingIcon" class="w-8 h-8" />
      </div>
      <p class="font-bold text-base mb-1.5 relative z-10" style="color:#0d2d5e;">No se encontraron clínicas</p>
      <p class="text-xs max-w-[300px] mb-5 relative z-10" style="color:#64748b;">Ajuste los filtros de búsqueda o espere a que nuevas instituciones se registren en el sistema.</p>
      <button class="clinic-empty-btn relative z-10" @click="limpiarFiltros">
        <component :is="RefreshIcon" class="w-3.5 h-3.5" />
        <span>Limpiar filtros</span>
      </button>
    </div>

    <!-- Tarjetas -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
      <div
        v-for="(clinica, idx) in clinicasFiltradas"
        :key="clinica.id"
        class="clinic-card flex flex-col anim-card-in"
        :style="{ animationDelay: (idx * 0.06) + 's' }"
        :class="{
          'border-yellow-200': clinica.estado === 'pendiente',
          'border-green-200': clinica.estado === 'activa',
          'border-red-200': clinica.estado === 'rechazada',
        }"
      >
        <!-- Cabecera de tarjeta -->
        <div class="px-5 pt-5 pb-3 flex items-start justify-between gap-2">
          <div class="min-w-0">
            <p class="text-sm font-bold text-gray-800 leading-snug truncate">{{ clinica.nombre }}</p>
            <p class="text-xs text-gray-400 truncate">{{ clinica.razon_social }}</p>
          </div>
          <span
            class="shrink-0 inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold"
            :class="{
              'bg-yellow-100 text-yellow-700': clinica.estado === 'pendiente',
              'bg-green-100 text-green-700': clinica.estado === 'activa',
              'bg-red-100 text-red-600': clinica.estado === 'rechazada',
            }"
          >
            <span class="w-1.5 h-1.5 rounded-full"
              :class="{
                'bg-yellow-500': clinica.estado === 'pendiente',
                'bg-green-500': clinica.estado === 'activa',
                'bg-red-500': clinica.estado === 'rechazada',
              }"
            />
            {{ clinica.estado === 'pendiente' ? 'Pendiente' : clinica.estado === 'activa' ? 'Activa' : 'Rechazada' }}
          </span>
        </div>

        <!-- Datos -->
        <div class="px-5 pb-4 space-y-2 flex-1">
          <div class="flex items-center gap-2 text-xs text-gray-500">
            <component :is="HashIcon" class="w-3.5 h-3.5 text-gray-300 shrink-0" />
            <span class="font-mono">{{ clinica.nit }}</span>
          </div>
          <div class="flex items-center gap-2 text-xs text-gray-500">
            <component :is="MapPinIcon" class="w-3.5 h-3.5 text-gray-300 shrink-0" />
            <span class="truncate">{{ clinica.ciudad }}, {{ clinica.departamento }}</span>
          </div>
          <div class="flex items-center gap-2 text-xs text-gray-500">
            <component :is="UserIcon" class="w-3.5 h-3.5 text-gray-300 shrink-0" />
            <span class="truncate">{{ clinica.representante_legal }}</span>
          </div>
          <div class="flex items-center gap-2 text-xs text-gray-500">
            <component :is="MailIcon" class="w-3.5 h-3.5 text-gray-300 shrink-0" />
            <span class="truncate">{{ clinica.email }}</span>
          </div>
          <div class="flex items-center gap-2 text-xs text-gray-400">
            <component :is="CalendarIcon" class="w-3.5 h-3.5 text-gray-300 shrink-0" />
            <span>Solicitud: {{ formatFecha(clinica.created_at) }}</span>
          </div>
          <div v-if="clinica.motivo_rechazo" class="mt-2 bg-red-50 rounded-lg px-3 py-2">
            <p class="text-xs text-red-500 font-semibold mb-0.5">Motivo de rechazo</p>
            <p class="text-xs text-red-600 line-clamp-2">{{ clinica.motivo_rechazo }}</p>
          </div>
        </div>

        <!-- Acciones -->
        <div class="px-5 pb-5 flex gap-2 border-t border-gray-50 pt-3">
          <el-button
            v-if="clinica.estado !== 'activa'"
            size="small"
            type="success"
            :loading="procesando === clinica.id + '_aprobar'"
            @click="aprobar(clinica)"
            class="flex-1"
          >
            <component :is="CheckIcon" class="w-3.5 h-3.5 mr-1" />
            Aprobar
          </el-button>
          <el-button
            v-if="clinica.estado !== 'rechazada'"
            size="small"
            type="danger"
            :loading="procesando === clinica.id + '_rechazar'"
            @click="abrirRechazo(clinica)"
            class="flex-1"
          >
            <component :is="XIcon" class="w-3.5 h-3.5 mr-1" />
            Rechazar
          </el-button>
          <el-button size="small" @click="verDetalle(clinica)">
            <component :is="EyeIcon" class="w-3.5 h-3.5" />
          </el-button>
        </div>
      </div>
    </div>

    <!-- Modal: Motivo de rechazo -->
    <el-dialog v-model="modalRechazo" title="Rechazar clínica" width="420px" :close-on-click-modal="false" class="rounded-2xl">
      <div class="mb-2">
        <p class="text-sm text-gray-600 mb-1">
          Indique el motivo de rechazo para <strong>{{ clinicaSeleccionada?.nombre }}</strong>:
        </p>
        <el-input
          v-model="motivoRechazo"
          type="textarea"
          :rows="3"
          placeholder="Ej: La institución no cuenta con convenio vigente con la Clínica Santa Bárbara..."
        />
      </div>
      <template #footer>
        <el-button @click="modalRechazo = false">Cancelar</el-button>
        <el-button type="danger" :loading="procesando !== null" @click="rechazar">
          Confirmar rechazo
        </el-button>
      </template>
    </el-dialog>

    <!-- Modal: Detalle -->
    <el-dialog v-model="modalDetalle" width="540px" class="detalle-clinica-dialog" :show-close="true" align-center>
      <template v-if="clinicaSeleccionada">
        <div class="detalle-clinica-content">
          <!-- Header con gradiente -->
          <div class="detalle-clinica-head">
            <div class="detalle-clinica-head-glow"></div>
            <div class="detalle-clinica-head-icon"
              :style="{
                background: clinicaSeleccionada.estado === 'pendiente' ? 'rgba(245,158,11,0.2)' : clinicaSeleccionada.estado === 'activa' ? 'rgba(34,197,94,0.2)' : 'rgba(239,68,68,0.2)',
                color: clinicaSeleccionada.estado === 'pendiente' ? '#fbbf24' : clinicaSeleccionada.estado === 'activa' ? '#22c55e' : '#ef4444',
              }">
              <component :is="clinicaSeleccionada.estado === 'activa' ? CheckIcon : clinicaSeleccionada.estado === 'rechazada' ? XIcon : ClockIcon" class="w-7 h-7" />
            </div>
            <div class="z-10">
              <p class="detalle-clinica-head-title">{{ clinicaSeleccionada.nombre }}</p>
              <p class="detalle-clinica-head-sub">{{ clinicaSeleccionada.razon_social }}</p>
            </div>
            <span class="detalle-clinica-head-badge"
              :style="{
                background: clinicaSeleccionada.estado === 'pendiente' ? 'rgba(245,158,11,0.2)' : clinicaSeleccionada.estado === 'activa' ? 'rgba(34,197,94,0.2)' : 'rgba(239,68,68,0.2)',
                color: clinicaSeleccionada.estado === 'pendiente' ? '#fbbf24' : clinicaSeleccionada.estado === 'activa' ? '#22c55e' : '#ef4444',
              }">
              {{ clinicaSeleccionada.estado === 'pendiente' ? 'Pendiente' : clinicaSeleccionada.estado === 'activa' ? 'Activa' : 'Rechazada' }}
            </span>
          </div>

          <!-- Datos -->
          <div class="detalle-clinica-body">
            <div class="detalle-clinica-grid">
              <div class="detalle-clinica-item">
                <p class="detalle-clinica-label">NIT</p>
                <p class="detalle-clinica-value font-mono">{{ clinicaSeleccionada.nit }}</p>
              </div>
              <div class="detalle-clinica-item">
                <p class="detalle-clinica-label">Ciudad</p>
                <p class="detalle-clinica-value">{{ clinicaSeleccionada.ciudad }}, {{ clinicaSeleccionada.departamento }}</p>
              </div>
              <div class="detalle-clinica-item">
                <p class="detalle-clinica-label">Dirección</p>
                <p class="detalle-clinica-value">{{ clinicaSeleccionada.direccion }}</p>
              </div>
              <div class="detalle-clinica-item">
                <p class="detalle-clinica-label">Teléfono</p>
                <p class="detalle-clinica-value">{{ clinicaSeleccionada.telefono }}</p>
              </div>
              <div class="detalle-clinica-item col-span-2">
                <p class="detalle-clinica-label">Correo</p>
                <p class="detalle-clinica-value">{{ clinicaSeleccionada.email }}</p>
              </div>
              <div class="detalle-clinica-item">
                <p class="detalle-clinica-label">Representante legal</p>
                <p class="detalle-clinica-value">{{ clinicaSeleccionada.representante_legal }}</p>
              </div>
              <div class="detalle-clinica-item">
                <p class="detalle-clinica-label">Cédula</p>
                <p class="detalle-clinica-value font-mono">{{ clinicaSeleccionada.cedula_representante }}</p>
              </div>
            </div>

            <div v-if="clinicaSeleccionada.observaciones" class="detalle-clinica-section">
              <p class="detalle-clinica-section-title">Observaciones</p>
              <p class="detalle-clinica-section-text">{{ clinicaSeleccionada.observaciones }}</p>
            </div>

            <div v-if="clinicaSeleccionada.motivo_rechazo" class="detalle-clinica-rechazo">
              <p class="detalle-clinica-rechazo-title">Motivo de rechazo</p>
              <p class="detalle-clinica-rechazo-text">{{ clinicaSeleccionada.motivo_rechazo }}</p>
            </div>
          </div>
        </div>
      </template>
    </el-dialog>

  </div>
</template>

<style scoped>
.clinic-page {
  background:
    radial-gradient(circle at 95% 0%, rgba(188, 218, 255, 0.45), transparent 24rem),
    radial-gradient(circle at 5% 100%, rgba(208, 242, 226, 0.35), transparent 22rem);
}

.clinic-glass-panel {
  background: rgba(255, 255, 255, 0.66);
  border: 1px solid rgba(255, 255, 255, 0.8);
  border-radius: 20px;
  box-shadow: 0 14px 30px rgba(50, 77, 116, 0.08), inset 0 1px 0 rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(16px);
}

/* ── Metric chips ── */
.metric-chip {
  min-width: 92px;
  padding: 11px 14px;
  text-align: center;
  border: 1px solid rgba(255, 255, 255, 0.8);
  border-radius: 16px;
  box-shadow: 5px 5px 12px rgba(70, 91, 125, 0.1), -4px -4px 10px rgba(255, 255, 255, 0.85);
  transition: transform .3s cubic-bezier(.22,1,.36,1), box-shadow .3s cubic-bezier(.22,1,.36,1);
}
.metric-chip:hover {
  transform: translateY(-4px) scale(1.03);
  box-shadow: 0 12px 24px rgba(70, 91, 125, 0.15), -4px -4px 10px rgba(255, 255, 255, 0.9);
}
.metric-chip-pending { background: linear-gradient(145deg, rgba(255, 251, 224, 0.9), rgba(255, 244, 193, 0.72)); }
.metric-chip-active { background: linear-gradient(145deg, rgba(236, 253, 245, 0.9), rgba(209, 250, 229, 0.7)); }
.metric-chip-rejected { background: linear-gradient(145deg, rgba(255, 241, 242, 0.9), rgba(255, 222, 226, 0.72)); }

/* ── Cards ── */
.clinic-card {
  overflow: hidden;
  background: rgba(255, 255, 255, 0.84);
  border-width: 1px;
  border-radius: 20px;
  box-shadow: 0 10px 24px rgba(50, 77, 116, 0.09), inset 0 1px 0 rgba(255, 255, 255, 0.9);
  transition: transform .3s cubic-bezier(.22,1,.36,1), box-shadow .3s cubic-bezier(.22,1,.36,1), border-color .3s ease;
  backdrop-filter: blur(10px);
  position: relative;
}
.clinic-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  opacity: .8;
  transition: opacity .28s ease, height .28s ease;
}
.clinic-card.border-yellow-200::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.clinic-card.border-green-200::before { background: linear-gradient(90deg, #22c55e, #4ade80); }
.clinic-card.border-red-200::before { background: linear-gradient(90deg, #ef4444, #f87171); }
.clinic-card:hover {
  transform: translateY(-6px) scale(1.02);
  box-shadow: 0 20px 38px rgba(31, 69, 118, 0.16), inset 0 1px 0 rgba(255, 255, 255, 0.95);
}
.clinic-card:hover::before {
  opacity: 1;
  height: 5px;
}

/* ── Animaciones ── */
.anim-card-in { animation: cardIn 0.5s cubic-bezier(.22,1,.36,1) both; }
@keyframes cardIn {
  from { opacity: 0; transform: translateY(16px); }
  to { opacity: 1; transform: none; }
}

/* ── Shimmer ── */
.shimmer-box, .shimmer-bar {
  position: relative;
  overflow: hidden;
  background: #e6ebf3;
}
.shimmer-box { border-radius: 6px; }
.shimmer-bar { border-radius: 4px; }
.shimmer-box::after, .shimmer-bar::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.6) 50%, transparent 100%);
  animation: shimmer 1.8s ease-in-out infinite;
}
@keyframes shimmer {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}

/* ── Empty state ── */
.clinic-empty-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 3rem 1rem;
  position: relative;
  background: #fff;
  border: 1px solid #d4deea;
  border-radius: 18px;
  box-shadow: 0 4px 16px rgba(22, 70, 142, .08);
  overflow: hidden;
}
.clinic-empty-glow {
  position: absolute;
  top: -40px; left: 50%;
  transform: translateX(-50%);
  width: 200px; height: 200px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(126,179,255,0.1), transparent 70%);
  pointer-events: none;
}
.clinic-empty-icon {
  color: #2f70bb;
  background: linear-gradient(135deg, #e4f0ff, #dbeafe);
  box-shadow: 0 8px 20px rgba(47, 112, 187, .15);
}
.clinic-empty-btn {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  padding: .5rem 1rem;
  border-radius: 10px;
  background: linear-gradient(135deg, #16468e, #0d2d6b);
  color: #fff;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: transform .2s ease, box-shadow .2s ease;
  box-shadow: 0 4px 12px rgba(13, 45, 107, .25);
}
.clinic-empty-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(13, 45, 107, .35);
}

/* ── Modal Detalle ── */
:deep(.detalle-clinica-dialog) {
  border-radius: 22px;
  overflow: hidden;
  box-shadow: 0 32px 80px rgba(11, 35, 73, .4), 0 0 0 1px rgba(255,255,255,.08);
}
:deep(.detalle-clinica-dialog .el-dialog__header) { display: none; }
:deep(.detalle-clinica-dialog .el-dialog__body) { padding: 0; }
.detalle-clinica-content { overflow: hidden; }
.detalle-clinica-head {
  position: relative;
  background: linear-gradient(135deg, #0D2D6B 0%, #16468E 55%, #1e3a7a 100%);
  padding: 28px 28px 22px;
  display: flex;
  align-items: center;
  gap: 14px;
  overflow: hidden;
}
.detalle-clinica-head-glow {
  position: absolute;
  top: -30px; right: -30px;
  width: 140px; height: 140px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(126,179,255,0.2), transparent 70%);
  pointer-events: none;
}
.detalle-clinica-head-icon {
  width: 48px; height: 48px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  z-index: 10;
}
.detalle-clinica-head-title {
  margin: 0;
  color: #fff;
  font-size: 18px;
  font-weight: 800;
}
.detalle-clinica-head-sub {
  margin: 2px 0 0;
  color: rgba(255,255,255,0.6);
  font-size: 12px;
}
.detalle-clinica-head-badge {
  margin-left: auto;
  padding: 4px 12px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
  flex-shrink: 0;
  z-index: 10;
}
.detalle-clinica-body { padding: 24px 28px; }
.detalle-clinica-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}
.detalle-clinica-item { display: flex; flex-direction: column; }
.detalle-clinica-label {
  margin: 0 0 4px;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .06em;
  color: #94a3b8;
}
.detalle-clinica-value {
  margin: 0;
  font-size: 13px;
  font-weight: 600;
  color: #1e293b;
}
.detalle-clinica-section {
  margin-top: 18px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 12px 16px;
}
.detalle-clinica-section-title {
  margin: 0 0 6px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .04em;
  color: #64748b;
}
.detalle-clinica-section-text {
  margin: 0;
  font-size: 13px;
  color: #475569;
  line-height: 1.6;
}
.detalle-clinica-rechazo {
  margin-top: 14px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 10px;
  padding: 12px 16px;
}
.detalle-clinica-rechazo-title {
  margin: 0 0 6px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .04em;
  color: #ef4444;
}
.detalle-clinica-rechazo-text {
  margin: 0;
  font-size: 13px;
  color: #dc2626;
  line-height: 1.6;
}
</style>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import {
  Search as SearchIcon,
  RefreshCw as RefreshIcon,
  Check as CheckIcon,
  X as XIcon,
  Eye as EyeIcon,
  Building2 as BuildingIcon,
  Hash as HashIcon,
  MapPin as MapPinIcon,
  User as UserIcon,
  Mail as MailIcon,
  Calendar as CalendarIcon,
  Clock as ClockIcon,
} from '@lucide/vue';
import http from '@/plugins/axios';

interface Clinica {
  id: number;
  nit: string;
  nombre: string;
  razon_social: string;
  email: string;
  telefono: string;
  ciudad: string;
  departamento: string;
  direccion: string;
  representante_legal: string;
  cedula_representante: string;
  observaciones?: string;
  estado: 'pendiente' | 'activa' | 'rechazada';
  motivo_rechazo?: string;
  created_at: string;
}

const clinicas = ref<Clinica[]>([]);
const cargando = ref(false);
const procesando = ref<string | null>(null);
const filtro = ref({ buscar: '', estado: '' });
const modalRechazo = ref(false);
const modalDetalle = ref(false);
const clinicaSeleccionada = ref<Clinica | null>(null);
const motivoRechazo = ref('');

const resumen = computed(() => ({
  pendientes: clinicas.value.filter(c => c.estado === 'pendiente').length,
  activas: clinicas.value.filter(c => c.estado === 'activa').length,
  rechazadas: clinicas.value.filter(c => c.estado === 'rechazada').length,
}));

const clinicasFiltradas = computed(() => {
  return clinicas.value.filter(c => {
    const texto = filtro.value.buscar.toLowerCase();
    const coincideTexto = !texto ||
      c.nombre.toLowerCase().includes(texto) ||
      c.nit.toLowerCase().includes(texto) ||
      c.ciudad?.toLowerCase().includes(texto);
    const coincideEstado = !filtro.value.estado || c.estado === filtro.value.estado;
    return coincideTexto && coincideEstado;
  });
});

async function cargar() {
  try {
    cargando.value = true;
    const { data } = await http.get('/api/clinicas');
    clinicas.value = data.data;
  } catch {
    ElMessage.error('Error al cargar las clínicas');
  } finally {
    cargando.value = false;
  }
}

async function aprobar(clinica: Clinica) {
  try {
    await ElMessageBox.confirm(
      `¿Aprobar y activar la clínica ${clinica.nombre}? Se le notificará por correo.`,
      'Confirmar aprobación',
      { confirmButtonText: 'Aprobar', cancelButtonText: 'Cancelar', type: 'success' }
    );
    procesando.value = clinica.id + '_aprobar';
    await http.post(`/api/clinicas/${clinica.id}/aprobar`);
    ElMessage.success('Clínica aprobada correctamente');
    await cargar();
  } catch (e: any) {
    if (e !== 'cancel') ElMessage.error('Error al aprobar la clínica');
  } finally {
    procesando.value = null;
  }
}

function abrirRechazo(clinica: Clinica) {
  clinicaSeleccionada.value = clinica;
  motivoRechazo.value = '';
  modalRechazo.value = true;
}

async function rechazar() {
  if (!motivoRechazo.value.trim()) {
    ElMessage.warning('Ingrese el motivo de rechazo');
    return;
  }
  try {
    procesando.value = clinicaSeleccionada.value!.id + '_rechazar';
    await http.post(`/api/clinicas/${clinicaSeleccionada.value!.id}/rechazar`, {
      motivo: motivoRechazo.value,
    });
    ElMessage.success('Clínica rechazada');
    modalRechazo.value = false;
    await cargar();
  } catch {
    ElMessage.error('Error al rechazar la clínica');
  } finally {
    procesando.value = null;
  }
}

function verDetalle(clinica: Clinica) {
  clinicaSeleccionada.value = clinica;
  modalDetalle.value = true;
}

function limpiarFiltros() {
  filtro.value = { buscar: '', estado: '' };
  ElMessage.info('Filtros limpiados');
}

function formatFecha(fecha: string) {
  return new Date(fecha).toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' });
}

let pollTimer: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
  cargar();
  pollTimer = setInterval(() => {
    if (!cargando.value && !modalRechazo.value && !modalDetalle.value) cargar();
  }, 30000);
});

onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer);
});
</script>
