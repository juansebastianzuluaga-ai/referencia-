<template>
  <div class="historial-page h-full overflow-y-auto p-4 sm:p-5">

    <!-- ── Header ── -->
    <div class="historial-header rounded-2xl p-4 sm:p-5 flex items-center justify-between mb-4 shrink-0 anim-fade-down">
      <div>
        <h1 class="text-lg sm:text-xl font-bold text-white">Historial de solicitudes</h1>
        <p class="text-xs mt-1" style="color:rgba(255,255,255,0.65);">Consulte y filtre todas las remisiones enviadas.</p>
      </div>
      <button @click="cargar" class="historial-refresh inline-flex items-center gap-1.5 text-xs font-medium transition-colors">
        <component :is="RefreshCwIcon" class="w-3.5 h-3.5" :class="{ 'animate-spin': cargando }" />
        Actualizar
      </button>
    </div>

    <!-- ── Filtros ── -->
    <div class="filtros-card rounded-2xl p-3 sm:p-4 mb-4 flex items-center gap-2 flex-wrap" style="overflow:hidden;">
      <div class="flex-1 min-w-[120px]">
        <el-input v-model="busqueda" placeholder="Buscar por paciente o documento…" :prefix-icon="SearchIcon" clearable size="default" />
      </div>
      <select v-model="filtroEstado" class="filter-select shrink-0">
        <option value="">Todos los estados</option>
        <option value="pendiente">Pendientes</option>
        <option value="aceptado">Aceptadas</option>
        <option value="en_espera">En espera</option>
        <option value="completado">Completadas</option>
        <option value="negado">Negadas</option>
      </select>
      <select v-model="filtroEspecialidad" class="filter-select shrink-0">
        <option value="">Todas las especialidades</option>
        <option v-for="e in ESPECIALIDADES" :key="e" :value="e">{{ e }}</option>
      </select>
      <select v-model="filtroEps" class="filter-select shrink-0">
        <option value="">Todas las EPS</option>
        <option v-for="e in EPS_LIST" :key="e" :value="e">{{ e }}</option>
      </select>
      <div class="filter-date-wrap shrink-0">
        <input type="date" v-model="filtroDesde" class="filter-date" placeholder="Desde" />
        <span class="filter-date-sep">—</span>
        <input type="date" v-model="filtroHasta" class="filter-date" placeholder="Hasta" />
      </div>
    </div>

    <!-- ── Resumen rápido ── -->
    <div class="grid grid-cols-3 gap-3 mb-4">
      <div class="resumen-card resumen-card--amber rounded-xl p-3 flex items-center gap-3 anim-slide-up" style="animation-delay:0.05s">
        <div class="resumen-icon">
          <component :is="ClockIcon" class="w-5 h-5" />
        </div>
        <div class="relative z-10">
          <p class="resumen-label">Pendientes</p>
          <p class="resumen-value">{{ resumen.pendientes }}</p>
        </div>
        <div class="resumen-bar ml-auto"><div class="h-full rounded-full" :style="{ width: Math.round(resumen.pendientes / Math.max(1, solicitudes.length) * 100) + '%' }"></div></div>
      </div>
      <div class="resumen-card resumen-card--green rounded-xl p-3 flex items-center gap-3 anim-slide-up" style="animation-delay:0.1s">
        <div class="resumen-icon">
          <component :is="CheckCircleIcon" class="w-5 h-5" />
        </div>
        <div class="relative z-10">
          <p class="resumen-label">Aceptadas</p>
          <p class="resumen-value">{{ resumen.aceptadas }}</p>
        </div>
        <div class="resumen-bar ml-auto"><div class="h-full rounded-full" :style="{ width: Math.round(resumen.aceptadas / Math.max(1, solicitudes.length) * 100) + '%' }"></div></div>
      </div>
      <div class="resumen-card resumen-card--red rounded-xl p-3 flex items-center gap-3 anim-slide-up" style="animation-delay:0.15s">
        <div class="resumen-icon">
          <component :is="XCircleIcon" class="w-5 h-5" />
        </div>
        <div class="relative z-10">
          <p class="resumen-label">Negadas</p>
          <p class="resumen-value">{{ resumen.negadas }}</p>
        </div>
        <div class="resumen-bar ml-auto"><div class="h-full rounded-full" :style="{ width: Math.round(resumen.negadas / Math.max(1, solicitudes.length) * 100) + '%' }"></div></div>
      </div>
    </div>

    <!-- ── Tabla ── -->
    <div v-if="cargando" class="space-y-2">
      <div v-for="i in 5" :key="i" class="skeleton-row rounded-xl px-4 py-3 flex items-center gap-3">
        <div class="skeleton-circle"></div>
        <div class="flex-1 space-y-1.5">
          <div class="skeleton-bar" style="width:30%; height:10px;"></div>
          <div class="skeleton-bar" style="width:50%; height:8px;"></div>
        </div>
        <div class="skeleton-bar" style="width:60px; height:18px; border-radius:999px;"></div>
      </div>
    </div>

    <div v-else-if="solicitudesFiltradas.length === 0" class="empty-state-wrap">
      <div class="empty-state-glow"></div>
      <div class="empty-state-icon w-16 h-16 rounded-2xl flex items-center justify-center mb-4 relative z-10">
        <component :is="ClipboardListIcon" class="w-8 h-8" />
      </div>
      <p class="font-bold text-base mb-1.5 relative z-10" style="color:#0d2d5e;">No se encontraron solicitudes</p>
      <p class="text-xs max-w-[300px] mb-5 relative z-10" style="color:#64748b;">Ajuste los filtros de búsqueda o cree una nueva solicitud desde el dashboard para empezar a gestionar sus remisiones.</p>
      <button class="empty-state-btn relative z-10" @click="limpiarFiltros">
        <component :is="RefreshCwIcon" class="w-3.5 h-3.5" />
        <span>Limpiar filtros</span>
      </button>
    </div>

    <div v-else>
      <!-- Tabla desktop -->
      <div class="tabla-card rounded-2xl overflow-hidden hidden sm:block">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-[10px] font-bold uppercase tracking-wider" style="color:#8a9ab5; background:#f8fafc;">
              <th class="px-4 py-3">Paciente</th>
              <th class="px-4 py-3 hidden sm:table-cell">Especialidad</th>
              <th class="px-4 py-3 hidden md:table-cell">EPS</th>
              <th class="px-4 py-3 hidden lg:table-cell">Fecha</th>
              <th class="px-4 py-3">Estado</th>
              <th class="px-4 py-3 text-right">Acción</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(sol, idx) in solicitudesPaginadas"
              :key="sol.id"
              class="table-row cursor-pointer transition-all anim-row-in"
              :style="{ animationDelay: (idx * 0.04) + 's' }"
              @click="verDetalle(sol)"
            >
              <td class="px-4 py-3">
                <div class="flex items-center gap-2.5">
                  <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 text-[10px] font-bold"
                    :style="{ background: sol.estado === 'pendiente' ? '#fef3c7' : sol.estado === 'aceptado' ? '#dcfce7' : '#fee2e2', color: sol.estado === 'pendiente' ? '#d97706' : sol.estado === 'aceptado' ? '#16a34a' : '#dc2626' }">
                    {{ initialesPaciente(sol) }}
                  </div>
                  <div class="min-w-0">
                    <p class="font-bold text-xs truncate" style="color:#1e2d55;">{{ sol.primer_nombre }} {{ sol.primer_apellido }}</p>
                    <p class="text-[10px] truncate" style="color:#8a9ab5;">{{ sol.tipo_documento }} {{ sol.numero_documento }}</p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3 hidden sm:table-cell text-xs" style="color:#475569;">{{ sol.especialidad_requerida }}</td>
              <td class="px-4 py-3 hidden md:table-cell">
                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md" style="background:#f1f5f9; color:#475569;">{{ sol.eps }}</span>
              </td>
              <td class="px-4 py-3 hidden lg:table-cell text-xs" style="color:#475569;">{{ formatFecha(sol.created_at) }}</td>
              <td class="px-4 py-3">
                <span class="estado-badge text-[10px] font-bold px-2.5 py-1 rounded-full"
                  :class="'estado-' + sol.estado"
                >
                  {{ estadoLabel(sol.estado) }}
                </span>
              </td>
              <td class="px-4 py-3 text-right">
                <component :is="ChevronRightIcon" class="w-4 h-4 inline-block transition-transform" style="color:#b0bccf;" />
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Paginación desktop -->
        <div v-if="totalPaginas > 1" class="flex items-center justify-between px-4 py-3 border-t" style="border-color:#edf1f7;">
          <p class="text-[10px]" style="color:#8a9ab5;">Mostrando {{ (pagina - 1) * porPagina + 1 }}–{{ Math.min(pagina * porPagina, solicitudesFiltradas.length) }} de {{ solicitudesFiltradas.length }}</p>
          <div class="flex items-center gap-1.5">
            <button @click="pagina = Math.max(1, pagina - 1)" :disabled="pagina === 1" class="pag-btn">Anterior</button>
            <span class="text-[10px] font-bold px-2" style="color:#16468e;">{{ pagina }} / {{ totalPaginas }}</span>
            <button @click="pagina = Math.min(totalPaginas, pagina + 1)" :disabled="pagina === totalPaginas" class="pag-btn">Siguiente</button>
          </div>
        </div>
      </div>

      <!-- Cards móvil -->
      <div class="sm:hidden space-y-2.5">
        <div
          v-for="(sol, idx) in solicitudesPaginadas"
          :key="sol.id"
          class="mobile-card rounded-xl p-3 cursor-pointer anim-row-in"
          :style="{ animationDelay: (idx * 0.04) + 's' }"
          @click="verDetalle(sol)"
        >
          <div class="flex items-center gap-2.5 mb-2">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 text-[10px] font-bold"
              :style="{ background: sol.estado === 'pendiente' ? '#fef3c7' : sol.estado === 'aceptado' ? '#dcfce7' : sol.estado === 'en_espera' ? '#dbeafe' : sol.estado === 'completado' ? '#e0e7ff' : '#fee2e2', color: sol.estado === 'pendiente' ? '#d97706' : sol.estado === 'aceptado' ? '#16a34a' : sol.estado === 'en_espera' ? '#2563eb' : sol.estado === 'completado' ? '#4f46e5' : '#dc2626' }">
              {{ initialesPaciente(sol) }}
            </div>
            <div class="min-w-0 flex-1">
              <p class="font-bold text-xs truncate" style="color:#1e2d55;">{{ sol.primer_nombre }} {{ sol.primer_apellido }}</p>
              <p class="text-[10px] truncate" style="color:#8a9ab5;">{{ sol.tipo_documento }} {{ sol.numero_documento }}</p>
            </div>
            <span class="estado-badge text-[10px] font-bold px-2.5 py-1 rounded-full shrink-0"
              :class="'estado-' + sol.estado"
            >
              {{ estadoLabel(sol.estado) }}
            </span>
          </div>
          <div class="flex items-center gap-3 text-[10px]" style="color:#64748b;">
            <span>{{ sol.especialidad_requerida }}</span>
            <span class="font-semibold px-1.5 py-0.5 rounded" style="background:#f1f5f9;">{{ sol.eps }}</span>
            <span class="ml-auto">{{ formatFecha(sol.created_at) }}</span>
          </div>
        </div>

        <!-- Paginación móvil -->
        <div v-if="totalPaginas > 1" class="flex items-center justify-between pt-2">
          <button @click="pagina = Math.max(1, pagina - 1)" :disabled="pagina === 1" class="pag-btn">Anterior</button>
          <span class="text-[10px] font-bold" style="color:#16468e;">{{ pagina }} / {{ totalPaginas }}</span>
          <button @click="pagina = Math.min(totalPaginas, pagina + 1)" :disabled="pagina === totalPaginas" class="pag-btn">Siguiente</button>
        </div>
      </div>
    </div>

    <!-- ── Modal: Detalle ──────────────────────────────────────────────── -->
    <el-dialog v-model="modalDetalle" width="640px" class="detalle-dialog" :show-close="true" align-center>
      <template v-if="solicitudSeleccionada">
        <div class="detalle-content">
          <!-- Header con gradiente azul -->
          <div class="detalle-head">
            <div class="detalle-head-glow"></div>
            <div class="detalle-head-icon">
              <component
                :is="solicitudSeleccionada.estado === 'aceptado' ? CheckCircleIcon : solicitudSeleccionada.estado === 'negado' ? XCircleIcon : solicitudSeleccionada.estado === 'en_espera' ? ClockIcon : solicitudSeleccionada.estado === 'completado' ? CheckCircleIcon : ClockIcon"
                class="w-7 h-7"
              />
            </div>
            <div class="detalle-head-info">
              <p class="detalle-head-title">Detalle de solicitud</p>
              <p class="detalle-head-sub">#{{ solicitudSeleccionada.id }} · {{ formatFecha(solicitudSeleccionada.created_at) }}</p>
            </div>
            <div class="detalle-head-badge" :class="'badge-' + solicitudSeleccionada.estado">
              {{ estadoLabel(solicitudSeleccionada.estado) }}
            </div>
            <button class="detalle-head-pdf" @click="exportarPdf">
              <component :is="FileDownIcon" class="w-4 h-4" />
              <span>PDF</span>
            </button>
          </div>

          <!-- Paciente + Remisión en 2 columnas -->
          <div class="grid grid-cols-2 gap-2.5 mb-2.5">
            <div class="detalle-card">
              <div class="card-icon" style="background:#dbeafe; color:#2563eb;"><component :is="ClipboardListIcon" class="w-4 h-4" /></div>
              <div class="card-body">
                <p class="card-title">Paciente</p>
                <div class="data-row"><span>Nombre</span><strong>{{ solicitudSeleccionada.primer_nombre }} {{ solicitudSeleccionada.segundo_nombre }} {{ solicitudSeleccionada.primer_apellido }} {{ solicitudSeleccionada.segundo_apellido }}</strong></div>
                <div class="data-row"><span>Documento</span><strong>{{ solicitudSeleccionada.tipo_documento }} {{ solicitudSeleccionada.numero_documento }}</strong></div>
                <div class="data-row"><span>Edad / Género</span><strong>{{ solicitudSeleccionada.edad }} años · {{ solicitudSeleccionada.genero === 'M' ? 'Masc.' : 'Fem.' }}</strong></div>
                <div class="data-row"><span>EPS</span><strong>{{ solicitudSeleccionada.eps }}</strong></div>
              </div>
            </div>
            <div class="detalle-card">
              <div class="card-icon" style="background:#fef3c7; color:#d97706;"><component :is="ClipboardListIcon" class="w-4 h-4" /></div>
              <div class="card-body">
                <p class="card-title">Remisión</p>
                <div class="data-row"><span>Especialidad</span><strong>{{ solicitudSeleccionada.especialidad_requerida }}</strong></div>
                <div class="data-row"><span>Servicio actual</span><strong>{{ solicitudSeleccionada.servicio_ubicacion_actual }}</strong></div>
                <div class="data-row"><span>Municipio</span><strong>{{ solicitudSeleccionada.municipio_capita }}</strong></div>
                <div v-if="solicitudSeleccionada.servicio_remision" class="data-row"><span>Destino</span><strong>{{ solicitudSeleccionada.servicio_remision }}</strong></div>
              </div>
            </div>
          </div>

          <!-- Diagnóstico + Historia en 2 columnas -->
          <div class="grid grid-cols-2 gap-2.5 mb-2.5">
            <div class="detalle-card">
              <div class="card-icon" style="background:#ede9fe; color:#7c3aed;"><component :is="ClipboardListIcon" class="w-4 h-4" /></div>
              <div class="card-body">
                <p class="card-title">Diagnóstico</p>
                <p class="card-text">{{ solicitudSeleccionada.diagnostico }}</p>
              </div>
            </div>
            <div class="detalle-card">
              <div class="card-icon" style="background:#e0f2fe; color:#0284c7;"><component :is="ClipboardListIcon" class="w-4 h-4" /></div>
              <div class="card-body">
                <p class="card-title">Historia clínica</p>
                <p class="card-text-sm">{{ solicitudSeleccionada.resumen_historia_clinica }}</p>
              </div>
            </div>
          </div>

          <!-- Código de aceptación -->
          <div v-if="solicitudSeleccionada.codigo_aceptacion" class="detalle-code-bar">
            <component :is="CheckCircleIcon" class="w-4 h-4 text-emerald-600" />
            <span class="text-xs text-emerald-700 font-semibold">Código de aceptación</span>
            <span class="detalle-code-value">{{ solicitudSeleccionada.codigo_aceptacion }}</span>
          </div>

          <!-- Respuesta / Negación -->
          <div v-if="solicitudSeleccionada.nombre_quien_responde" class="detalle-respuesta">
            <div class="flex items-center gap-2 mb-1">
              <component :is="CheckCircleIcon" class="w-4 h-4 text-blue-600" />
              <p class="text-[10px] font-extrabold text-blue-600 uppercase tracking-wider">Respuesta del equipo</p>
            </div>
            <p class="text-xs text-blue-700">Respondió: <strong>{{ solicitudSeleccionada.nombre_quien_responde }}</strong> · {{ solicitudSeleccionada.hora_respuesta }}</p>
            <p v-if="solicitudSeleccionada.observaciones_respuesta" class="text-xs text-blue-600 mt-1">{{ solicitudSeleccionada.observaciones_respuesta }}</p>
          </div>

          <div v-if="solicitudSeleccionada.motivo_negacion" class="detalle-negacion">
            <div class="flex items-center gap-2 mb-1">
              <component :is="XCircleIcon" class="w-4 h-4 text-red-500" />
              <p class="text-[10px] font-extrabold text-red-500 uppercase tracking-wider">Motivo de negación</p>
            </div>
            <p class="text-xs text-slate-700">{{ solicitudSeleccionada.motivo_negacion }}</p>
          </div>

          <!-- Seguimiento + Adjuntos en 2 columnas -->
          <div class="grid grid-cols-2 gap-2.5">
            <div v-if="solicitudSeleccionada.eventos?.length" class="detalle-card">
              <div class="card-icon" style="background:#f1f5f9; color:#475569;"><component :is="ClockIcon" class="w-4 h-4" /></div>
              <div class="card-body">
                <p class="card-title">Seguimiento</p>
                <div v-for="evento in solicitudSeleccionada.eventos" :key="evento.id" class="timeline-item">
                  <div class="timeline-dot"></div>
                  <div><p class="text-[11px] font-semibold text-slate-700">{{ evento.titulo }}</p><p class="text-[9px] text-slate-400">{{ formatFecha(evento.created_at) }}</p></div>
                </div>
              </div>
            </div>
            <div class="detalle-card">
              <div class="card-icon" style="background:#fef2f2; color:#dc2626;"><component :is="ClipboardListIcon" class="w-4 h-4" /></div>
              <div class="card-body">
                <p class="card-title">Soportes</p>
                <div v-if="solicitudSeleccionada.adjuntos?.length">
                  <div v-for="adj in solicitudSeleccionada.adjuntos" :key="adj.id" class="adjunto-item">
                    <a :href="`/api/externo/solicitudes/${solicitudSeleccionada.id}/adjuntos/${adj.id}/descargar`" target="_blank" class="adjunto-link">
                      <span class="adjunto-icon" :class="adj.mime_type?.includes('pdf') ? 'adjunto-pdf' : 'adjunto-img'">{{ adj.mime_type?.includes('pdf') ? 'PDF' : 'IMG' }}</span>
                      <span class="adjunto-name">{{ adj.nombre_original }}</span>
                    </a>
                  </div>
                </div>
                <p v-else class="adjunto-empty">Sin archivos adjuntos</p>
              </div>
            </div>
          </div>
        </div>
      </template>
    </el-dialog>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { ElMessage } from 'element-plus';
import {
  Search as SearchIcon,
  Clock as ClockIcon,
  CheckCircle as CheckCircleIcon,
  XCircle as XCircleIcon,
  ChevronRight as ChevronRightIcon,
  ClipboardList as ClipboardListIcon,
  RefreshCw as RefreshCwIcon,
  FileDown as FileDownIcon,
} from '@lucide/vue';
import http from '@/plugins/axios';
import { ESPECIALIDADES, EPS_LIST } from '@/data/referencia';

const solicitudes = ref<any[]>([]);
const cargando = ref(false);
const modalDetalle = ref(false);
const solicitudSeleccionada = ref<any>(null);

const busqueda = ref('');
const filtroEstado = ref('');
const filtroEspecialidad = ref('');
const filtroEps = ref('');
const filtroDesde = ref('');
const filtroHasta = ref('');
const pagina = ref(1);
const porPagina = 12;

const resumen = computed(() => ({
  pendientes: solicitudes.value.filter(s => s.estado === 'pendiente').length,
  aceptadas: solicitudes.value.filter(s => s.estado === 'aceptado').length,
  negadas: solicitudes.value.filter(s => s.estado === 'negado').length,
}));

const solicitudesFiltradas = computed(() => {
  let lista = solicitudes.value;

  if (busqueda.value) {
    const q = busqueda.value.toLowerCase();
    lista = lista.filter(s =>
      `${s.primer_nombre} ${s.primer_apellido}`.toLowerCase().includes(q) ||
      `${s.tipo_documento} ${s.numero_documento}`.toLowerCase().includes(q),
    );
  }

  if (filtroEstado.value) {
    lista = lista.filter(s => s.estado === filtroEstado.value);
  }

  if (filtroEspecialidad.value) {
    lista = lista.filter(s => s.especialidad_requerida === filtroEspecialidad.value);
  }

  if (filtroEps.value) {
    lista = lista.filter(s => s.eps === filtroEps.value);
  }

  if (filtroDesde.value) {
    lista = lista.filter(s => s.created_at?.slice(0, 10) >= filtroDesde.value);
  }

  if (filtroHasta.value) {
    lista = lista.filter(s => s.created_at?.slice(0, 10) <= filtroHasta.value);
  }

  return lista;
});

const totalPaginas = computed(() => Math.max(1, Math.ceil(solicitudesFiltradas.value.length / porPagina)));

const solicitudesPaginadas = computed(() => {
  const start = (pagina.value - 1) * porPagina;
  return solicitudesFiltradas.value.slice(start, start + porPagina);
});

async function cargar() {
  cargando.value = true;
  try {
    const { data } = await http.get('/api/externo/solicitudes');
    solicitudes.value = data.data;
  } catch {
    ElMessage.error('Error al cargar el historial');
  } finally {
    cargando.value = false;
  }
}

function verDetalle(sol: any) {
  solicitudSeleccionada.value = sol;
  modalDetalle.value = true;
}

function limpiarFiltros() {
  busqueda.value = '';
  filtroEstado.value = '';
  filtroEspecialidad.value = '';
  filtroEps.value = '';
  filtroDesde.value = '';
  filtroHasta.value = '';
  pagina.value = 1;
  ElMessage.info('Filtros limpiados');
}

function formatFecha(fecha: string) {
  return new Date(fecha).toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' });
}

function initialesPaciente(solicitud: any): string {
  return `${solicitud.primer_nombre?.[0] ?? ''}${solicitud.primer_apellido?.[0] ?? ''}`.toUpperCase();
}

function estadoLabel(estado: string): string {
  return { pendiente: 'Pendiente', aceptado: 'Aceptada', en_espera: 'En espera', completado: 'Completada', negado: 'Negada' }[estado] ?? estado;
}

function exportarPdf() {
  const s = solicitudSeleccionada.value;
  if (!s) return;

  const estadoText = estadoLabel(s.estado);
  const estadoColor = s.estado === 'pendiente' ? '#f59e0b' : s.estado === 'aceptado' ? '#22c55e' : s.estado === 'en_espera' ? '#3b82f6' : s.estado === 'completado' ? '#6366f1' : '#ef4444';
  const paciente = `${s.primer_nombre} ${s.segundo_nombre ?? ''} ${s.primer_apellido} ${s.segundo_apellido ?? ''}`.trim();

  const win = window.open('', '_blank', 'width=800,height=900');
  if (!win) {
    ElMessage.error('El navegador bloqueó la ventana emergente. Permita popups para exportar.');
    return;
  }
  ElMessage.success('Generando documento PDF...');

  win.document.write(`
    <!DOCTYPE html>
    <html lang="es">
    <head>
      <meta charset="UTF-8">
      <title>Solicitud #${s.id} - CAC Santa Bárbara</title>
      <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #1e293b; background: #f8faff; padding: 2rem; }
        .doc-header { background: linear-gradient(125deg, #0d2d6b, #16468e); border-radius: 16px; padding: 1.5rem; color: #fff; display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; }
        .doc-header h1 { font-size: 1.1rem; font-weight: 800; }
        .doc-header p { font-size: .75rem; opacity: .6; margin-top: .2rem; }
        .doc-badge { margin-left: auto; padding: .35rem .8rem; border-radius: 999px; font-size: .7rem; font-weight: 700; background: ${estadoColor}; color: #fff; }
        .doc-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; }
        .doc-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; }
        .doc-card h2 { font-size: .75rem; font-weight: 800; color: #0d2d5e; text-transform: uppercase; letter-spacing: .04em; margin-bottom: .6rem; padding-bottom: .4rem; border-bottom: 2px solid #f1f5f9; }
        .doc-row { display: flex; justify-content: space-between; padding: .25rem 0; font-size: .78rem; }
        .doc-row span { color: #94a3b8; }
        .doc-row strong { color: #1e293b; font-weight: 600; text-align: right; }
        .doc-full { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; margin-bottom: 1rem; }
        .doc-full h2 { font-size: .75rem; font-weight: 800; color: #0d2d5e; text-transform: uppercase; letter-spacing: .04em; margin-bottom: .5rem; padding-bottom: .4rem; border-bottom: 2px solid #f1f5f9; }
        .doc-full p { font-size: .78rem; line-height: 1.6; color: #475569; }
        .doc-code { background: linear-gradient(135deg, #ecfdf5, #d1fae5); border: 1px solid #a7f3d0; border-radius: 10px; padding: .8rem 1rem; display: flex; align-items: center; gap: .6rem; margin-bottom: 1rem; }
        .doc-code span { font-size: .75rem; color: #166534; font-weight: 600; }
        .doc-code strong { margin-left: auto; font-family: monospace; font-size: 1rem; font-weight: 800; color: #166534; background: #fff; padding: .2rem .6rem; border-radius: 6px; border: 1px solid #86efac; }
        .doc-footer { margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #e2e8f0; text-align: center; }
        .doc-footer p { font-size: .65rem; color: #94a3b8; }
        .doc-footer .logo { font-size: .8rem; font-weight: 800; color: #0d2d6b; margin-bottom: .3rem; }
        @media print { body { padding: 0; } }
      </style>
    </head>
    <body>
      <div class="doc-header">
        <div>
          <h1>Solicitud de Referencia #${s.id}</h1>
          <p>${new Date(s.created_at).toLocaleDateString('es-CO', { day: '2-digit', month: 'long', year: 'numeric' })}</p>
        </div>
        <div class="doc-badge">${estadoText}</div>
      </div>

      ${s.codigo_aceptacion ? `<div class="doc-code"><span>Código de aceptación</span><strong>${s.codigo_aceptacion}</strong></div>` : ''}

      <div class="doc-grid">
        <div class="doc-card">
          <h2>Paciente</h2>
          <div class="doc-row"><span>Nombre</span><strong>${paciente}</strong></div>
          <div class="doc-row"><span>Documento</span><strong>${s.tipo_documento} ${s.numero_documento}</strong></div>
          <div class="doc-row"><span>Edad / Género</span><strong>${s.edad} años · ${s.genero === 'M' ? 'Masculino' : 'Femenino'}</strong></div>
          <div class="doc-row"><span>EPS</span><strong>${s.eps}</strong></div>
        </div>
        <div class="doc-card">
          <h2>Remisión</h2>
          <div class="doc-row"><span>Especialidad</span><strong>${s.especialidad_requerida}</strong></div>
          <div class="doc-row"><span>Servicio actual</span><strong>${s.servicio_ubicacion_actual}</strong></div>
          <div class="doc-row"><span>Municipio</span><strong>${s.municipio_capita}</strong></div>
          ${s.servicio_remision ? `<div class="doc-row"><span>Destino</span><strong>${s.servicio_remision}</strong></div>` : ''}
        </div>
      </div>

      <div class="doc-full">
        <h2>Diagnóstico</h2>
        <p>${s.diagnostico}</p>
      </div>

      <div class="doc-full">
        <h2>Historia clínica</h2>
        <p>${s.resumen_historia_clinica}</p>
      </div>

      ${s.nombre_quien_responde ? `<div class="doc-full"><h2>Respuesta del equipo</h2><p><strong>Respondió:</strong> ${s.nombre_quien_responde} · ${s.hora_respuesta ?? ''}</p>${s.observaciones_respuesta ? `<p style="margin-top:.5rem;">${s.observaciones_respuesta}</p>` : ''}</div>` : ''}

      ${s.motivo_negacion ? `<div class="doc-full"><h2>Motivo de negación</h2><p>${s.motivo_negacion}</p></div>` : ''}

      <div class="doc-footer">
        <p class="logo">Clínica CAC Santa Bárbara</p>
        <p>Documento generado el ${new Date().toLocaleDateString('es-CO', { day: '2-digit', month: 'long', year: 'numeric' })}</p>
      </div>

      <script>
        window.onload = function() { window.print(); };
      <\/script>
    </body>
    </html>
  `);
  win.document.close();
}

let pollTimer: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
  cargar();
  pollTimer = setInterval(() => {
    if (!cargando.value && !modalDetalle.value) cargar();
  }, 30000);
});

onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer);
});
</script>

<style scoped>
.historial-page {
  background: #f5f7fb;
}

/* ── Header ── */
.historial-header {
  background: linear-gradient(115deg, #0d2d6b 0%, #16468e 55%, #1e3a7a 100%);
  border: 1px solid #1e3a7a;
  box-shadow: 0 8px 24px rgba(13, 45, 107, .25);
}
.historial-refresh {
  color: rgba(255, 255, 255, 0.7);
  background: rgba(255, 255, 255, 0.08);
  padding: .4rem .8rem;
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.1);
}
.historial-refresh:hover {
  color: #fff;
  background: rgba(255, 255, 255, 0.15);
}

.filtros-card {
  background: #fff;
  border: 1px solid #d4deea;
  box-shadow: 0 4px 16px rgba(22, 70, 142, .08);
  border-radius: 14px;
  position: relative;
  overflow: hidden;
}
.filtros-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: linear-gradient(90deg, #0d2d6b, #16468e, #2f70bb);
  opacity: .6;
}

/* ── Resumen ── */
.resumen-card {
  border-radius: 14px;
  position: relative;
  overflow: hidden;
  transition: transform .35s cubic-bezier(.22,1,.36,1), box-shadow .35s cubic-bezier(.22,1,.36,1), border-color .3s ease;
  border: 2px solid transparent;
  min-height: 72px;
}
.resumen-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; width: 5px; height: 100%;
  opacity: .95;
}
.resumen-card::after {
  content: '';
  position: absolute;
  top: -30px; right: -30px;
  width: 80px; height: 80px;
  border-radius: 50%;
  filter: blur(18px);
  opacity: .25;
}
.resumen-card--amber {
  background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
  border-color: rgba(251, 191, 36, .5);
  box-shadow: 0 10px 28px rgba(217, 119, 6, .15), 0 0 0 1px rgba(255,255,255,.5) inset;
}
.resumen-card--amber::before { background: #d97706; }
.resumen-card--amber::after { background: #fbbf24; }
.resumen-card--amber .resumen-icon { background: #fef3c7; color: #d97706; box-shadow: 0 4px 14px rgba(217,119,6,.25); }
.resumen-card--amber .resumen-value { color: #b45309; }
.resumen-card--amber .resumen-bar { background: rgba(251,191,36,.3); }
.resumen-card--amber .resumen-bar > div { background: linear-gradient(90deg, #f59e0b, #fbbf24); box-shadow: 0 0 8px rgba(245,158,11,.5); }
.resumen-card--green {
  background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
  border-color: rgba(74, 222, 128, .5);
  box-shadow: 0 10px 28px rgba(22, 163, 74, .15), 0 0 0 1px rgba(255,255,255,.5) inset;
}
.resumen-card--green::before { background: #16a34a; }
.resumen-card--green::after { background: #4ade80; }
.resumen-card--green .resumen-icon { background: #dcfce7; color: #16a34a; box-shadow: 0 4px 14px rgba(22,163,74,.25); }
.resumen-card--green .resumen-value { color: #15803d; }
.resumen-card--green .resumen-bar { background: rgba(74,222,128,.3); }
.resumen-card--green .resumen-bar > div { background: linear-gradient(90deg, #22c55e, #4ade80); box-shadow: 0 0 8px rgba(34,197,94,.5); }
.resumen-card--red {
  background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
  border-color: rgba(248, 113, 113, .5);
  box-shadow: 0 10px 28px rgba(220, 38, 38, .15), 0 0 0 1px rgba(255,255,255,.5) inset;
}
.resumen-card--red::before { background: #dc2626; }
.resumen-card--red::after { background: #f87171; }
.resumen-card--red .resumen-icon { background: #fee2e2; color: #dc2626; box-shadow: 0 4px 14px rgba(220,38,38,.25); }
.resumen-card--red .resumen-value { color: #b91c1c; }
.resumen-card--red .resumen-bar { background: rgba(248,113,113,.3); }
.resumen-card--red .resumen-bar > div { background: linear-gradient(90deg, #ef4444, #f87171); box-shadow: 0 0 8px rgba(239,68,68,.5); }
.resumen-card:hover {
  transform: translateY(-10px) scale(1.04);
  border-color: transparent;
}
.resumen-card--amber:hover { box-shadow: 0 28px 56px rgba(217, 119, 6, .22); }
.resumen-card--green:hover { box-shadow: 0 28px 56px rgba(22, 163, 74, .22); }
.resumen-card--red:hover { box-shadow: 0 28px 56px rgba(220, 38, 38, .22); }
.resumen-icon {
  width: 44px; height: 44px;
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  position: relative;
  z-index: 10;
  transition: transform .25s ease, box-shadow .25s ease;
}
.resumen-card:hover .resumen-icon {
  transform: scale(1.18) rotate(-6deg);
}
.resumen-label {
  font-size: 10px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: .04em;
}
.resumen-value {
  font-size: 26px;
  font-weight: 900;
  line-height: 1;
  text-shadow: 0 2px 0 rgba(255,255,255,0.8);
}
.resumen-bar {
  width: 42px;
  height: 6px;
  border-radius: 999px;
  overflow: hidden;
  box-shadow: inset 0 1px 2px rgba(0,0,0,.08);
}
.resumen-bar > div {
  transition: width .7s cubic-bezier(.22,1,.36,1);
}

/* ── Tabla ── */
.tabla-card {
  background: #fff;
  border: 1px solid #d4deea;
  box-shadow: 0 4px 16px rgba(22, 70, 142, .08);
  border-radius: 14px;
  position: relative;
  overflow: hidden;
}
.tabla-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: linear-gradient(90deg, #0d2d6b, #16468e, #2f70bb);
  opacity: .6;
}

.table-row {
  border-top: 1px solid #f1f5f9;
  transition: background .15s ease, box-shadow .15s ease;
  position: relative;
}
.table-row:hover {
  background: #f0f5ff;
  box-shadow: inset 3px 0 0 #16468e;
}
.table-row:hover .w-4\.h-4 {
  transform: translateX(3px);
  color: #16468e;
}

/* ── Cards móvil ── */
.mobile-card {
  background: #fff;
  border: 1px solid #d4deea;
  box-shadow: 0 3px 12px rgba(22, 70, 142, .07);
  transition: transform .2s ease, box-shadow .2s ease;
}
.mobile-card:active {
  transform: scale(.98);
  box-shadow: 0 6px 18px rgba(22, 70, 142, .12);
}

/* ── Estado badges ── */
.estado-badge {
  display: inline-flex;
  align-items: center;
  gap: .3rem;
}
.estado-pendiente {
  background: #fef3c7;
  color: #b45309;
}
.estado-pendiente::before {
  content: '';
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #fbbf24;
}
.estado-aceptado {
  background: #dcfce7;
  color: #15803d;
}
.estado-aceptado::before {
  content: '';
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #22c55e;
}
.estado-negado {
  background: #fee2e2;
  color: #b91c1c;
}
.estado-negado::before {
  content: '';
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #ef4444;
}
.estado-en_espera {
  background: #dbeafe;
  color: #1d4ed8;
}
.estado-en_espera::before {
  content: '';
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #3b82f6;
}
.estado-completado {
  background: #e0e7ff;
  color: #4338ca;
}
.estado-completado::before {
  content: '';
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #6366f1;
}

/* ── Skeleton ── */
.skeleton-row {
  background: #fff;
  border: 1px solid #edf1f7;
}
.skeleton-circle {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: #e3e8f0;
  animation: skeletonPulse 1.5s ease-in-out infinite;
  flex-shrink: 0;
}
.skeleton-bar {
  background: #e3e8f0;
  border-radius: 4px;
  animation: skeletonPulse 1.5s ease-in-out infinite;
}
@keyframes skeletonPulse {
  0%, 100% { opacity: 1; }
  50% { opacity: .5; }
}

/* ── Empty state ── */
.empty-state-wrap {
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
.empty-state-glow {
  position: absolute;
  top: -40px; left: 50%;
  transform: translateX(-50%);
  width: 200px; height: 200px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(126,179,255,0.1), transparent 70%);
  pointer-events: none;
}
.empty-state-icon {
  color: #2f70bb;
  background: linear-gradient(135deg, #e4f0ff, #dbeafe);
  box-shadow: 0 8px 20px rgba(47, 112, 187, .15);
}
.empty-state-btn {
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
.empty-state-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(13, 45, 107, .35);
}

/* ── Paginación ── */
.pag-btn {
  padding: .35rem .7rem;
  font-size: .72rem;
  font-weight: 600;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #475569;
  cursor: pointer;
  transition: all .15s ease;
}
.pag-btn:hover:not(:disabled) {
  background: #f1f5f9;
  border-color: #cbd5e1;
}
.pag-btn:disabled {
  opacity: .4;
  cursor: not-allowed;
}

/* ── Animaciones ── */
.anim-fade-down { animation: fadeDown 0.5s cubic-bezier(.22,1,.36,1) both; }
.anim-slide-up  { animation: slideUp  0.5s cubic-bezier(.22,1,.36,1) both; }
.anim-row-in    { animation: rowIn   0.4s cubic-bezier(.22,1,.36,1) both; }

@keyframes fadeDown { from { opacity:0; transform:translateY(-18px); } to { opacity:1; transform:none; } }
@keyframes slideUp  { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:none; } }
@keyframes rowIn    { from { opacity:0; transform:translateX(-12px); } to { opacity:1; transform:none; } }

:deep(.el-input__wrapper) {
  box-shadow: 0 0 0 1px #dce7f2 inset;
  border-radius: 10px;
}
:deep(.el-input__wrapper:hover) {
  box-shadow: 0 0 0 1px #86b4e8 inset;
}

/* ── Filtros nativos ── */
.filter-select {
  appearance: none;
  -webkit-appearance: none;
  padding: .5rem 2rem .5rem .8rem;
  font-size: .8rem;
  font-weight: 500;
  color: #475569;
  background: #fff;
  border: 1px solid #dce7f2;
  border-radius: 10px;
  cursor: pointer;
  transition: border-color .2s ease, box-shadow .2s ease;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238a9ab5' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right .6rem center;
  max-width: 150px;
}
.filter-select:hover {
  border-color: #86b4e8;
}
.filter-select:focus {
  outline: none;
  border-color: #16468e;
  box-shadow: 0 0 0 3px rgba(22, 70, 142, .1);
}

.filter-date-wrap {
  display: flex;
  align-items: center;
  gap: .4rem;
}
.filter-date {
  padding: .5rem .6rem;
  font-size: .78rem;
  font-weight: 500;
  color: #475569;
  background: #fff;
  border: 1px solid #dce7f2;
  border-radius: 10px;
  cursor: pointer;
  transition: border-color .2s ease, box-shadow .2s ease;
}
.filter-date:hover {
  border-color: #86b4e8;
}
.filter-date:focus {
  outline: none;
  border-color: #16468e;
  box-shadow: 0 0 0 3px rgba(22, 70, 142, .1);
}
.filter-date-sep {
  font-size: .75rem;
  color: #b0bccf;
  font-weight: 600;
}

/* ── Modal Detalle ── */
:deep(.detalle-dialog) {
  border-radius: 22px;
  overflow: hidden;
  box-shadow: 0 32px 80px rgba(11, 35, 73, .4), 0 0 0 1px rgba(255,255,255,.08);
}
:deep(.detalle-dialog .el-dialog__header) { display: none; }
:deep(.detalle-dialog .el-dialog__body) {
  padding: 0;
  max-height: calc(100vh - 3rem);
  overflow: hidden;
  background: linear-gradient(180deg, #f0f5ff 0%, #f8faff 30%, #ffffff 100%);
}
:deep(.detalle-dialog .el-dialog__headerbtn) { z-index: 10; top: 14px; right: 14px; }
:deep(.detalle-dialog .el-dialog__headerbtn .el-dialog__close) { color: #fff; font-size: 1.1rem; }
:deep(.detalle-dialog .el-dialog__headerbtn:hover .el-dialog__close) { color: #e1f7ff; }
:deep(.el-overlay) { background-color: rgba(8, 27, 58, .56); backdrop-filter: blur(4px); }

.detalle-content { padding: 0; }
.detalle-content > .grid,
.detalle-content > .detalle-code-bar,
.detalle-content > .detalle-respuesta,
.detalle-content > .detalle-negacion { padding-left: 1rem; padding-right: 1rem; margin-top: .8rem; }
.detalle-content > .grid:last-child { padding-bottom: 1rem; }

/* Header con gradiente azul institucional */
.detalle-head {
  position: relative;
  background: linear-gradient(125deg, #0d2d6b 0%, #16468e 50%, #1a3d8a 100%);
  padding: 1.1rem 1.3rem;
  display: flex;
  align-items: center;
  gap: .8rem;
  overflow: hidden;
}
.detalle-head::after {
  content: '';
  position: absolute;
  bottom: 0; left: 0; right: 0;
  height: 3px;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,.15), transparent);
}
.detalle-head-glow {
  position: absolute;
  top: -40px; right: -30px;
  width: 140px; height: 140px;
  border-radius: 50%;
  background: rgba(255,255,255,.06);
}
.detalle-head-glow::after {
  content: '';
  position: absolute;
  top: 20px; left: 30px;
  width: 60px; height: 60px;
  border-radius: 50%;
  background: rgba(255,255,255,.04);
}
.detalle-head-icon {
  width: 2.8rem; height: 2.8rem;
  border-radius: 12px;
  background: rgba(255,255,255,.15);
  border: 1px solid rgba(255,255,255,.2);
  display: grid; place-items: center;
  color: #fff;
  flex-shrink: 0;
  z-index: 1;
}
.detalle-head-info { flex: 1; z-index: 1; }
.detalle-head-title { margin: 0; color: #fff; font-size: 1rem; font-weight: 800; }
.detalle-head-sub { margin: .15rem 0 0; color: rgba(255,255,255,.55); font-size: .68rem; }

.detalle-head-badge {
  padding: .3rem .7rem;
  border-radius: 999px;
  font-size: .65rem;
  font-weight: 700;
  flex-shrink: 0;
  z-index: 1;
}
.badge-pendiente { background: #f59e0b; color: #fff; }
.badge-aceptado { background: #22c55e; color: #fff; }
.badge-en_espera { background: #3b82f6; color: #fff; }
.badge-completado { background: #6366f1; color: #fff; }
.badge-negado { background: #ef4444; color: #fff; }

.detalle-head-pdf {
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  padding: .35rem .7rem;
  border-radius: 8px;
  background: rgba(255,255,255,.12);
  border: 1px solid rgba(255,255,255,.2);
  color: #fff;
  font-size: .65rem;
  font-weight: 700;
  cursor: pointer;
  transition: background .2s ease;
  flex-shrink: 0;
  z-index: 1;
}
.detalle-head-pdf:hover { background: rgba(255,255,255,.22); }

/* Cards */
.detalle-card {
  background: #fff;
  border: 1px solid #d4deea;
  border-radius: 14px;
  overflow: hidden;
  box-shadow: 0 2px 10px rgba(22,70,142,.07);
  transition: box-shadow .25s ease, border-color .25s ease, transform .25s ease;
  position: relative;
}
.detalle-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: linear-gradient(90deg, #0d2d6b, #16468e, #2f70bb);
  opacity: .7;
}
.detalle-card:hover {
  box-shadow: 0 10px 28px rgba(22,70,142,.14);
  border-color: #b8c8de;
  transform: translateY(-1px);
}

.card-icon {
  width: 1.8rem; height: 1.8rem;
  border-radius: 8px;
  display: grid; place-items: center;
  flex-shrink: 0;
  margin: .6rem .6rem 0 .6rem;
  float: left;
}
.card-body { padding: .6rem .8rem .6rem .6rem; }
.card-title {
  margin: 0 0 .35rem;
  font-size: .72rem;
  font-weight: 800;
  color: #0d2d5e;
  text-transform: uppercase;
  letter-spacing: .04em;
  padding-top: .15rem;
}
.card-text { font-size: .72rem; color: #334e70; line-height: 1.5; margin: 0; }
.card-text-sm { font-size: .66rem; color: #64748b; line-height: 1.45; margin: 0; }

.data-row {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: .4rem;
  padding: .18rem 0;
  border-bottom: 1px solid #f1f5f9;
}
.data-row:last-child { border-bottom: none; }
.data-row span { font-size: .62rem; color: #94a3b8; white-space: nowrap; }
.data-row strong { font-size: .68rem; color: #1e293b; font-weight: 600; text-align: right; }

/* Código de aceptación */
.detalle-code-bar {
  display: flex;
  align-items: center;
  gap: .5rem;
  background: linear-gradient(135deg, #ecfdf5, #d1fae5);
  border: 1px solid #a7f3d0;
  border-radius: 12px;
  padding: .55rem .8rem;
  margin-bottom: .6rem;
}
.detalle-code-value {
  margin-left: auto;
  font-family: monospace;
  font-size: .9rem;
  font-weight: 800;
  color: #166534;
  background: #fff;
  padding: .2rem .6rem;
  border-radius: 6px;
  border: 1px solid #86efac;
}

/* Respuesta / Negación */
.detalle-respuesta {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 12px;
  padding: .55rem .8rem;
  margin-bottom: .6rem;
}
.detalle-negacion {
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 12px;
  padding: .55rem .8rem;
  margin-bottom: .6rem;
}

/* Timeline */
.timeline-item {
  display: flex;
  gap: .5rem;
  align-items: flex-start;
  padding: .15rem 0;
}
.timeline-dot {
  width: 6px; height: 6px;
  border-radius: 50%;
  background: #16468e;
  margin-top: 5px;
  flex-shrink: 0;
}

/* Adjuntos */
.adjunto-item { padding: .15rem 0; }
.adjunto-item:not(:last-child) { border-bottom: 1px solid #f1f5f9; }
.adjunto-link {
  display: flex;
  align-items: center;
  gap: .4rem;
  text-decoration: none;
  padding: .2rem 0;
  transition: opacity .15s ease;
}
.adjunto-link:hover { opacity: .7; }
.adjunto-icon {
  font-size: .52rem;
  font-weight: 800;
  color: #fff;
  padding: .12rem .3rem;
  border-radius: 4px;
  flex-shrink: 0;
}
.adjunto-pdf { background: #dc2626; }
.adjunto-img { background: #2563eb; }
.adjunto-name {
  font-size: .66rem;
  color: #334e70;
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.adjunto-empty {
  font-size: .66rem;
  color: #cbd5e1;
  font-style: italic;
  margin: 0;
  padding: .3rem 0;
}
</style>
