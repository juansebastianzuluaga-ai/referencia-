<template>
  <div class="ph-solicitudes h-full flex flex-col gap-2 p-3 sm:p-4 overflow-hidden">

    <!-- ── Header ── -->
    <div class="flex items-center justify-between shrink-0">
      <h1 class="text-lg font-bold text-gray-900">Solicitudes de referencia</h1>
      <el-button type="primary" size="small" @click="cargar">
        <component :is="RefreshIcon" class="w-3.5 h-3.5 mr-1" :class="{ 'animate-spin': cargando }" />
        Actualizar
      </el-button>
    </div>

    <!-- ── Tabs + Búsqueda ── -->
    <div class="flex items-center gap-3 shrink-0 flex-wrap">
      <div class="sol-tabs">
        <button
          v-for="tab in tabs"
          :key="tab.value"
          class="sol-tab"
          :class="{ 'sol-tab-active': tabActiva === tab.value }"
          @click="tabActiva = tab.value"
        >
          {{ tab.label }}
          <span class="sol-tab-count">{{ tab.count }}</span>
        </button>
      </div>
      <el-input
        v-model="filtro.buscar"
        placeholder="Buscar paciente, EPS, especialidad..."
        class="sol-search"
        clearable
        size="small"
      >
        <template #prefix>
          <component :is="SearchIcon" class="w-3.5 h-3.5 text-gray-400" />
        </template>
      </el-input>
    </div>

    <!-- ── Tabla ── -->
    <div class="flex-1 overflow-hidden sol-table-panel">
      <!-- Loading -->
      <div v-if="cargando" class="sol-table-loading">
        <div v-for="i in 5" :key="i" class="sol-table-row-skeleton">
          <div class="shimmer-box" style="width:32px; height:32px; border-radius:8px; flex-shrink:0;"></div>
          <div class="flex-1 space-y-1">
            <div class="shimmer-bar" style="width:35%; height:12px;"></div>
            <div class="shimmer-bar" style="width:25%; height:9px;"></div>
          </div>
          <div class="shimmer-bar" style="width:15%; height:11px;"></div>
          <div class="shimmer-box" style="width:60px; height:22px; border-radius:999px;"></div>
          <div class="shimmer-box" style="width:80px; height:26px; border-radius:6px; flex-shrink:0;"></div>
        </div>
      </div>

      <!-- Vacío -->
      <div v-else-if="solicitudesFiltradas.length === 0" class="sol-empty-wrap">
        <div class="sol-empty-icon w-14 h-14 rounded-2xl flex items-center justify-center mb-3">
          <component :is="FileTextIcon" class="w-7 h-7" />
        </div>
        <p class="font-bold text-sm mb-1" style="color:#0d2d5e;">Sin solicitudes</p>
        <p class="text-xs max-w-[260px]" style="color:#64748b;">No se encontraron solicitudes en esta categoría.</p>
      </div>

      <!-- Tabla real -->
      <div v-else class="flex flex-col h-full overflow-hidden">
        <div class="overflow-y-auto custom-scrollbar flex-1">
          <table class="sol-table">
            <thead class="sol-table-thead">
              <tr>
                <th class="sol-th sol-th-paciente">Paciente</th>
                <th class="sol-th">Especialidad</th>
                <th class="sol-th">Estado</th>
                <th class="sol-th sol-th-actions">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(s, idx) in solicitudesFiltradas"
                :key="s.id"
                class="sol-table-row anim-row-in"
                :style="{ animationDelay: (idx * 0.02) + 's' }"
              >
                <td class="sol-td">
                  <div class="sol-table-paciente">
                    <div class="sol-table-avatar"
                      :style="{
                        background: s.estado === 'pendiente' ? '#fef3c7' : s.estado === 'aceptado' ? '#dcfce7' : '#fee2e2',
                        color: s.estado === 'pendiente' ? '#d97706' : s.estado === 'aceptado' ? '#16a34a' : '#dc2626'
                      }">
                      {{ inicialesPaciente(s) }}
                    </div>
                    <div>
                      <p class="sol-table-name">{{ nombreCompleto(s) }}</p>
                      <p class="sol-table-doc">{{ s.tipo_documento }} {{ s.numero_documento }} · {{ s.eps }}</p>
                    </div>
                  </div>
                </td>
                <td class="sol-td">{{ s.especialidad_requerida }}</td>
                <td class="sol-td">
                  <span class="sol-table-status" :class="{
                    'sol-status-pending': s.estado === 'pendiente',
                    'sol-status-accepted': s.estado === 'aceptado',
                    'sol-status-rejected': s.estado === 'negado',
                  }">
                    <span class="sol-status-dot"></span>
                    {{ estadoLabel(s.estado) }}
                  </span>
                </td>
                <td class="sol-td">
                  <div class="sol-table-actions">
                    <el-button size="small" @click="verDetalle(s)">
                      <component :is="EyeIcon" class="w-3 h-3 mr-0.5" />
                      Ver
                    </el-button>
                    <el-button v-if="s.estado !== 'aceptado'" type="success" size="small" @click="abrirAceptar(s)">
                      <component :is="CheckIcon" class="w-3 h-3 mr-0.5" />
                      Aceptar
                    </el-button>
                    <el-button v-if="s.estado !== 'negado'" type="danger" size="small" @click="abrirNegar(s)">
                      <component :is="XIcon" class="w-3 h-3 mr-0.5" />
                      Negar
                    </el-button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ── Modal: Detalle ── -->
    <el-dialog v-model="modalDetalle" width="620px" class="detalle-dialog" :show-close="true" align-center>
      <template v-if="solicitudSeleccionada">
        <div class="detalle-content">
          <!-- Header azul -->
          <div class="detalle-head">
            <div class="detalle-head-glow"></div>
            <div class="detalle-head-icon">
              <component
                :is="solicitudSeleccionada.estado === 'aceptado' ? CheckCircleIcon : solicitudSeleccionada.estado === 'negado' ? XCircleIcon : ClockIcon"
                class="w-6 h-6"
              />
            </div>
            <div class="detalle-head-info">
              <p class="detalle-head-title">Detalle de solicitud</p>
              <p class="detalle-head-sub">#{{ solicitudSeleccionada.id }} · {{ nombreCompleto(solicitudSeleccionada) }}</p>
            </div>
            <div class="detalle-head-badge" :class="'badge-' + solicitudSeleccionada.estado">
              {{ estadoLabel(solicitudSeleccionada.estado) }}
            </div>
          </div>

          <!-- Body con cards -->
          <div class="detalle-body">
            <div class="detalle-cards-grid">
              <!-- Card: Paciente -->
              <div class="detalle-card detalle-card-blue">
                <div class="detalle-card-header">
                  <component :is="FileTextIcon" class="w-4 h-4" />
                  <span>PACIENTE</span>
                </div>
                <div class="detalle-card-rows">
                  <div class="detalle-card-row"><span class="detalle-row-label">Nombre</span><span class="detalle-row-value">{{ nombreCompleto(solicitudSeleccionada) }}</span></div>
                  <div class="detalle-card-row"><span class="detalle-row-label">Documento</span><span class="detalle-row-value font-mono">{{ solicitudSeleccionada.tipo_documento }} {{ solicitudSeleccionada.numero_documento }}</span></div>
                  <div class="detalle-card-row"><span class="detalle-row-label">Edad / Género</span><span class="detalle-row-value">{{ solicitudSeleccionada.edad }} años · {{ solicitudSeleccionada.genero === 'M' ? 'Masc.' : 'Fem.' }}</span></div>
                  <div class="detalle-card-row"><span class="detalle-row-label">EPS</span><span class="detalle-row-value">{{ solicitudSeleccionada.eps }}</span></div>
                </div>
              </div>

              <!-- Card: Remisión -->
              <div class="detalle-card detalle-card-amber">
                <div class="detalle-card-header">
                  <component :is="FileTextIcon" class="w-4 h-4" />
                  <span>REMISIÓN</span>
                </div>
                <div class="detalle-card-rows">
                  <div class="detalle-card-row"><span class="detalle-row-label">Institución</span><span class="detalle-row-value">{{ solicitudSeleccionada.clinica?.nombre ?? '—' }}</span></div>
                  <div class="detalle-card-row"><span class="detalle-row-label">Especialidad</span><span class="detalle-row-value">{{ solicitudSeleccionada.especialidad_requerida }}</span></div>
                  <div class="detalle-card-row"><span class="detalle-row-label">Municipio</span><span class="detalle-row-value">{{ solicitudSeleccionada.municipio_capita }}</span></div>
                  <div class="detalle-card-row"><span class="detalle-row-label">Fecha</span><span class="detalle-row-value">{{ formatFecha(solicitudSeleccionada.fecha) }} · {{ solicitudSeleccionada.hora }}</span></div>
                </div>
              </div>

              <!-- Card: Diagnóstico -->
              <div class="detalle-card detalle-card-purple">
                <div class="detalle-card-header">
                  <component :is="FileTextIcon" class="w-4 h-4" />
                  <span>DIAGNÓSTICO</span>
                </div>
                <p class="detalle-card-text">{{ solicitudSeleccionada.diagnostico }}</p>
              </div>

              <div v-if="solicitudSeleccionada.adjuntos?.length" class="detalle-card detalle-card-blue">
                <div class="detalle-card-header">
                  <component :is="PaperclipIcon" class="w-4 h-4" />
                  <span>ARCHIVOS ADJUNTOS ({{ solicitudSeleccionada.adjuntos.length }})</span>
                </div>
                <div class="detalle-adjuntos-list">
                  <a
                    v-for="adj in solicitudSeleccionada.adjuntos"
                    :key="adj.id"
                    :href="`/api/solicitudes-referencia/${solicitudSeleccionada.id}/adjuntos/${adj.id}/descargar`"
                    target="_blank"
                    class="detalle-adjunto-item"
                  >
                    <component :is="FileTextIcon" class="w-4 h-4 text-blue-500 flex-shrink-0" />
                    <span class="detalle-adjunto-name">{{ adj.nombre_original }}</span>
                    <span class="detalle-adjunto-size">{{ formatFileSize(adj.tamano) }}</span>
                    <component :is="DownloadIcon" class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" />
                  </a>
                </div>
              </div>
            </div>

            <div class="detalle-card detalle-card-cyan mt-3">
              <div class="detalle-card-header">
                <component :is="FileTextIcon" class="w-4 h-4" />
                <span>HISTORIA CLÍNICA</span>
              </div>
              <p class="detalle-card-text detalle-historia-preview">{{ solicitudSeleccionada.resumen_historia_clinica }}</p>
              <button
                v-if="solicitudSeleccionada.resumen_historia_clinica?.length > 280"
                type="button"
                class="detalle-historia-button"
                @click="abrirHistoriaClinica"
              >
                Mostrar más
              </button>
            </div>

            <!-- Código de aceptación -->
            <div v-if="solicitudSeleccionada.codigo_aceptacion" class="detalle-code-bar mt-3">
              <component :is="CheckCircleIcon" class="w-4 h-4 text-emerald-600" />
              <span class="text-xs text-emerald-700 font-semibold">Código de aceptación</span>
              <span class="detalle-code-value">{{ solicitudSeleccionada.codigo_aceptacion }}</span>
            </div>

            <!-- Respuesta -->
            <div v-if="solicitudSeleccionada.nombre_quien_responde" class="detalle-card detalle-card-blue mt-3">
              <div class="detalle-card-header">
                <component :is="CheckCircleIcon" class="w-4 h-4" />
                <span>RESPUESTA DEL EQUIPO</span>
              </div>
              <p class="text-xs text-blue-700">Respondió: <strong>{{ solicitudSeleccionada.nombre_quien_responde }}</strong> · {{ solicitudSeleccionada.hora_respuesta }}</p>
              <p v-if="solicitudSeleccionada.observaciones_respuesta" class="text-xs text-blue-600 mt-1">{{ solicitudSeleccionada.observaciones_respuesta }}</p>
            </div>

            <!-- Motivo negación -->
            <div v-if="solicitudSeleccionada.motivo_negacion" class="detalle-card detalle-card-red mt-3">
              <div class="detalle-card-header detalle-card-header-red">
                <component :is="XCircleIcon" class="w-4 h-4" />
                <span>MOTIVO DE NEGACIÓN</span>
              </div>
              <p class="text-xs text-red-600 mt-1">{{ solicitudSeleccionada.motivo_negacion }}</p>
            </div>

          </div>
        </div>
      </template>
    </el-dialog>

    <el-dialog v-model="modalHistoriaClinica" width="620px" class="historia-dialog" append-to-body align-center>
      <template #header>
        <div class="historia-dialog-header">
          <component :is="FileTextIcon" class="w-5 h-5" />
          <div>
            <p>Historia clínica</p>
            <span>{{ solicitudSeleccionada ? nombreCompleto(solicitudSeleccionada) : '' }}</span>
          </div>
        </div>
      </template>
      <p class="historia-dialog-text">{{ solicitudSeleccionada?.resumen_historia_clinica }}</p>
    </el-dialog>

    <!-- ── Modal: Aceptar ── -->
    <el-dialog v-model="modalAceptar" width="480px" :close-on-click-modal="false" class="accion-dialog" align-center>
      <template #header>
        <div class="accion-head">
          <div class="accion-head-glow"></div>
          <div class="accion-head-icon" style="background:rgba(34,197,94,0.2); color:#4ade80;">
            <component :is="CheckIcon" class="w-5 h-5" />
          </div>
          <div class="accion-head-info">
            <p class="accion-head-title">Aceptar solicitud</p>
            <p class="accion-head-sub">{{ solicitudSeleccionada ? nombreCompleto(solicitudSeleccionada) : '' }}</p>
          </div>
        </div>
      </template>
      <div class="space-y-3 px-4 py-4">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold mb-1" style="color:#334e70;">Hora de respuesta <span class="text-red-400">*</span></label>
            <el-time-select v-model="formAceptar.hora_respuesta" placeholder="HH:MM" start="00:00" step="00:05" end="23:55" class="w-full" />
          </div>
          <div>
            <label class="block text-xs font-semibold mb-1" style="color:#334e70;">N.° de ingreso</label>
            <el-input v-model.number="formAceptar.numero_ingreso" type="number" placeholder="Opcional" />
          </div>
        </div>
        <div>
          <label class="block text-xs font-semibold mb-1" style="color:#334e70;">Nombre de quien responde <span class="text-red-400">*</span></label>
          <el-input v-model="formAceptar.nombre_quien_responde" placeholder="Nombre completo" />
        </div>
        <div>
          <label class="block text-xs font-semibold mb-1" style="color:#334e70;">Observaciones</label>
          <el-input v-model="formAceptar.observaciones_respuesta" type="textarea" :rows="3" placeholder="Opcional" />
        </div>
      </div>
      <template #footer>
        <el-button @click="modalAceptar = false">Cancelar</el-button>
        <el-button type="success" :loading="procesando" @click="aceptar">
          <component :is="CheckIcon" class="w-3.5 h-3.5 mr-1" />
          Confirmar
        </el-button>
      </template>
    </el-dialog>

    <!-- ── Modal: Negar ── -->
    <el-dialog v-model="modalNegar" width="480px" :close-on-click-modal="false" class="accion-dialog" align-center>
      <template #header>
        <div class="accion-head">
          <div class="accion-head-glow"></div>
          <div class="accion-head-icon" style="background:rgba(239,68,68,0.2); color:#f87171;">
            <component :is="XIcon" class="w-5 h-5" />
          </div>
          <div class="accion-head-info">
            <p class="accion-head-title">Negar solicitud</p>
            <p class="accion-head-sub">{{ solicitudSeleccionada ? nombreCompleto(solicitudSeleccionada) : '' }}</p>
          </div>
        </div>
      </template>
      <div class="space-y-3 px-4 py-4">
        <div>
          <label class="block text-xs font-semibold mb-1" style="color:#334e70;">Hora de respuesta <span class="text-red-400">*</span></label>
          <el-time-select v-model="formNegar.hora_respuesta" placeholder="HH:MM" start="00:00" step="00:05" end="23:55" class="w-full" />
        </div>
        <div>
          <label class="block text-xs font-semibold mb-1" style="color:#334e70;">Motivo de negación <span class="text-red-400">*</span></label>
          <el-input v-model="formNegar.motivo_negacion" type="textarea" :rows="3" placeholder="Indique el motivo..." />
        </div>
        <div>
          <label class="block text-xs font-semibold mb-1" style="color:#334e70;">Nombre de quien responde <span class="text-red-400">*</span></label>
          <el-input v-model="formNegar.nombre_quien_responde" placeholder="Nombre completo" />
        </div>
        <div>
          <label class="block text-xs font-semibold mb-1" style="color:#334e70;">Observaciones adicionales</label>
          <el-input v-model="formNegar.observaciones_respuesta" type="textarea" :rows="2" placeholder="Opcional" />
        </div>
      </div>
      <template #footer>
        <el-button @click="modalNegar = false">Cancelar</el-button>
        <el-button type="danger" :loading="procesando" @click="negar">
          <component :is="XIcon" class="w-3.5 h-3.5 mr-1" />
          Confirmar
        </el-button>
      </template>
    </el-dialog>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { ElMessage } from 'element-plus';
import {
  Search as SearchIcon,
  RefreshCw as RefreshIcon,
  Eye as EyeIcon,
  Check as CheckIcon,
  X as XIcon,
  FileText as FileTextIcon,
  Clock as ClockIcon,
  CheckCircle as CheckCircleIcon,
  XCircle as XCircleIcon,
  ClipboardList as ClipboardListIcon,
  Paperclip as PaperclipIcon,
  Download as DownloadIcon,
} from '@lucide/vue';
import http from '@/plugins/axios';

interface Clinica {
  id: number;
  nombre: string;
}

interface Solicitud {
  id: number;
  clinica_id: number;
  clinica?: Clinica;
  fecha: string;
  hora: string;
  primer_nombre: string;
  segundo_nombre?: string;
  primer_apellido: string;
  segundo_apellido?: string;
  genero: 'M' | 'F';
  edad: number;
  tipo_documento: string;
  numero_documento: string;
  eps: string;
  diagnostico: string;
  municipio_capita: string;
  especialidad_requerida: string;
  servicio_ubicacion_actual: string;
  servicio_remision?: string;
  resumen_historia_clinica: string;
  via_contacto?: string;
  gestante?: boolean;
  condicion_especial?: string;
  observaciones?: string;
  estado: 'pendiente' | 'aceptado' | 'negado';
  codigo_aceptacion?: string;
  hora_respuesta?: string;
  motivo_negacion?: string;
  numero_ingreso?: number;
  nombre_quien_responde?: string;
  observaciones_respuesta?: string;
  adjuntos?: { id: number; nombre_original: string; mime_type: string; tamano: number }[];
  created_at: string;
}

const solicitudes = ref<Solicitud[]>([]);
const cargando = ref(false);
const procesando = ref(false);
const filtro = ref({ buscar: '' });
const tabActiva = ref<'todas' | 'pendiente' | 'aceptado' | 'negado'>('todas');

const modalDetalle = ref(false);
const modalHistoriaClinica = ref(false);
const modalAceptar = ref(false);
const modalNegar = ref(false);
const solicitudSeleccionada = ref<Solicitud | null>(null);

const formAceptar = ref({ hora_respuesta: '', nombre_quien_responde: '', numero_ingreso: null as number | null, observaciones_respuesta: '' });
const formNegar = ref({ hora_respuesta: '', motivo_negacion: '', nombre_quien_responde: '', observaciones_respuesta: '' });

const resumen = computed(() => ({
  total: solicitudes.value.length,
  pendientes: solicitudes.value.filter(s => s.estado === 'pendiente').length,
  aceptadas: solicitudes.value.filter(s => s.estado === 'aceptado').length,
  negadas: solicitudes.value.filter(s => s.estado === 'negado').length,
}));

const tabs = computed(() => [
  { label: 'Todas', value: 'todas', count: resumen.value.total },
  { label: 'Pendientes', value: 'pendiente', count: resumen.value.pendientes },
  { label: 'Aceptadas', value: 'aceptado', count: resumen.value.aceptadas },
  { label: 'Negadas', value: 'negado', count: resumen.value.negadas },
]);

const statCards = [
  { iconBackground: '#dbeafe', color: '#2563eb', icon: ClipboardListIcon, label: 'Solicitudes', sub: (n: number) => n ? `${n} registradas` : 'sin registros' },
  { iconBackground: '#fef3c7', color: '#d97706', icon: ClockIcon, label: 'Pendientes', sub: (n: number) => n ? `${n} en espera` : 'sin pendientes' },
  { iconBackground: '#dcfce7', color: '#16a34a', icon: CheckCircleIcon, label: 'Aceptadas', sub: (n: number) => n ? `${n} aprobadas` : 'sin aprobadas' },
  { iconBackground: '#fee2e2', color: '#dc2626', icon: XCircleIcon, label: 'Negadas', sub: (n: number) => n ? `${n} rechazadas` : 'sin rechazos' },
];

const displayStats = ref([0, 0, 0, 0]);

function animateCounters(targets: number[]) {
  const duration = 900;
  const steps = 40;
  const interval = duration / steps;
  let step = 0;
  const timer = setInterval(() => {
    step++;
    const progress = step / steps;
    const ease = 1 - Math.pow(1 - progress, 3);
    displayStats.value = targets.map(t => Math.round(t * ease));
    if (step >= steps) {
      displayStats.value = [...targets];
      clearInterval(timer);
    }
  }, interval);
}

function cardValue(i: number): number {
  if (i === 0) return resumen.value.total;
  if (i === 1) return resumen.value.pendientes;
  if (i === 2) return resumen.value.aceptadas;
  return resumen.value.negadas;
}

const statPercents = computed(() => {
  const total = Math.max(1, resumen.value.total);
  return [
    100,
    Math.round((resumen.value.pendientes / total) * 100),
    Math.round((resumen.value.aceptadas / total) * 100),
    Math.round((resumen.value.negadas / total) * 100),
  ];
});

const solicitudesFiltradas = computed(() => {
  return solicitudes.value.filter(s => {
    if (tabActiva.value !== 'todas' && s.estado !== tabActiva.value) return false;
    const texto = filtro.value.buscar.toLowerCase();
    const coincideTexto = !texto ||
      nombreCompleto(s).toLowerCase().includes(texto) ||
      s.eps.toLowerCase().includes(texto) ||
      s.especialidad_requerida.toLowerCase().includes(texto) ||
      s.clinica?.nombre.toLowerCase().includes(texto) ||
      s.numero_documento.toLowerCase().includes(texto);
    return coincideTexto;
  });
});

function inicialesPaciente(s: Solicitud): string {
  const parts = [s.primer_nombre, s.primer_apellido].filter(Boolean);
  if (parts.length === 0) return '?';
  return parts.map(p => p[0]).join('').toUpperCase().slice(0, 2);
}

function nombreCompleto(s: Solicitud) {
  return [s.primer_nombre, s.segundo_nombre, s.primer_apellido, s.segundo_apellido]
    .filter(Boolean).join(' ');
}

function estadoLabel(estado: string) {
  return { pendiente: 'Pendiente', aceptado: 'Aceptado', negado: 'Negado' }[estado] ?? estado;
}

function formatFecha(fecha: string) {
  if (!fecha) return '—';
  const d = new Date(fecha.includes('T') ? fecha : fecha + 'T00:00:00');
  if (isNaN(d.getTime())) return fecha;
  return d.toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatFileSize(bytes: number): string {
  if (!bytes) return '0 B';
  const units = ['B', 'KB', 'MB', 'GB'];
  let i = 0;
  let size = bytes;
  while (size >= 1024 && i < units.length - 1) { size /= 1024; i++; }
  return size.toFixed(i === 0 ? 0 : 1) + ' ' + units[i];
}

async function cargar() {
  try {
    cargando.value = true;
    const { data } = await http.get('/api/solicitudes-referencia');
    solicitudes.value = data.data;
    animateCounters([resumen.value.total, resumen.value.pendientes, resumen.value.aceptadas, resumen.value.negadas]);
  } catch {
    ElMessage.error('Error al cargar las solicitudes');
  } finally {
    cargando.value = false;
  }
}

function verDetalle(s: Solicitud) {
  solicitudSeleccionada.value = s;
  modalHistoriaClinica.value = false;
  modalDetalle.value = true;
}

function abrirHistoriaClinica() {
  modalHistoriaClinica.value = true;
}

function horaActual(): string {
  const now = new Date();
  const h = String(now.getHours()).padStart(2, '0');
  const m = String(Math.floor(now.getMinutes() / 5) * 5).padStart(2, '0');
  return `${h}:${m}`;
}

function abrirAceptar(s: Solicitud) {
  solicitudSeleccionada.value = s;
  formAceptar.value = { hora_respuesta: horaActual(), nombre_quien_responde: '', numero_ingreso: null, observaciones_respuesta: '' };
  modalAceptar.value = true;
}

function abrirNegar(s: Solicitud) {
  solicitudSeleccionada.value = s;
  formNegar.value = { hora_respuesta: horaActual(), motivo_negacion: '', nombre_quien_responde: '', observaciones_respuesta: '' };
  modalNegar.value = true;
}

async function aceptar() {
  if (!formAceptar.value.hora_respuesta || !formAceptar.value.nombre_quien_responde.trim()) {
    ElMessage.warning('Complete los campos obligatorios');
    return;
  }
  try {
    procesando.value = true;
    const { data } = await http.post(`/api/solicitudes-referencia/${solicitudSeleccionada.value!.id}/aceptar`, formAceptar.value);
    ElMessage.success('Solicitud aceptada correctamente');
    const idx = solicitudes.value.findIndex(s => s.id === solicitudSeleccionada.value!.id);
    if (idx !== -1) solicitudes.value[idx] = data.data;
    modalAceptar.value = false;
  } catch {
    ElMessage.error('Error al aceptar la solicitud');
  } finally {
    procesando.value = false;
  }
}

async function negar() {
  if (!formNegar.value.hora_respuesta || !formNegar.value.motivo_negacion.trim() || !formNegar.value.nombre_quien_responde.trim()) {
    ElMessage.warning('Complete los campos obligatorios');
    return;
  }
  try {
    procesando.value = true;
    const { data } = await http.post(`/api/solicitudes-referencia/${solicitudSeleccionada.value!.id}/negar`, formNegar.value);
    ElMessage.success('Solicitud negada');
    const idx = solicitudes.value.findIndex(s => s.id === solicitudSeleccionada.value!.id);
    if (idx !== -1) solicitudes.value[idx] = data.data;
    modalNegar.value = false;
  } catch {
    ElMessage.error('Error al negar la solicitud');
  } finally {
    procesando.value = false;
  }
}

onMounted(cargar);
</script>

<style scoped>
.ph-solicitudes {
  background:
    radial-gradient(circle at 95% 0%, rgba(188, 218, 255, 0.45), transparent 24rem),
    radial-gradient(circle at 5% 100%, rgba(208, 242, 226, 0.35), transparent 22rem);
}

/* ── Tabs ── */
.sol-tabs {
  display: flex; gap: 4px;
  padding: 3px;
  background: rgba(255,255,255,0.8);
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}
.sol-tab {
  display: flex; align-items: center; gap: 6px;
  padding: 5px 12px;
  border-radius: 8px;
  font-size: 12px; font-weight: 600; color: #64748b;
  background: transparent;
  border: none; cursor: pointer;
  transition: all .2s ease;
}
.sol-tab:hover { color: #1e2d55; background: rgba(13,45,107,.04); }
.sol-tab-active {
  background: #0d2d6b;
  color: #fff;
  box-shadow: 0 2px 8px rgba(13,45,107,.25);
}
.sol-tab-count {
  padding: 1px 6px; border-radius: 999px;
  font-size: 9px; font-weight: 700;
  background: rgba(13,45,107,.08); color: #1e2d55;
}
.sol-tab-active .sol-tab-count { background: rgba(255,255,255,.2); color: #fff; }

/* ── Search ── */
.sol-search { max-width: 280px; }

/* ── Table panel ── */
.sol-table-panel {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 4px 16px rgba(22,70,142,.08);
  padding: 4px;
}

/* ── Table loading ── */
.sol-table-loading {
  display: flex; flex-direction: column; gap: 6px;
  padding: 4px;
}
.sol-table-row-skeleton {
  display: flex; align-items: center; gap: 12px;
  padding: 10px 12px; border-radius: 8px;
  background: rgba(255,255,255,0.8);
  border: 1px solid #e2e8f0;
}

/* ── Table ── */
.sol-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.sol-th {
  padding: 8px 12px;
  text-align: left;
  font-size: 10px; font-weight: 700;
  text-transform: uppercase; letter-spacing: .05em;
  color: #64748b;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}
.sol-th:first-child { border-radius: 8px 0 0 8px; }
.sol-th:last-child { border-radius: 0 8px 8px 0; }
.sol-table-thead th { position: sticky; top: 0; z-index: 10; }
.sol-td {
  padding: 9px 12px;
  font-size: 12px; color: #334155;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
  white-space: nowrap;
}
.sol-table-row { transition: background .15s ease; }
.sol-table-row:hover { background: #f8fafc; }
.sol-table-row:last-child td { border-bottom: none; }
.sol-th-paciente { min-width: 220px; }
.sol-th-actions { text-align: right; }

.sol-table-paciente { display: flex; align-items: center; gap: 10px; }
.sol-table-avatar {
  width: 32px; height: 32px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; font-size: 11px; font-weight: 800;
}
.sol-table-name {
  font-size: 13px; font-weight: 700; color: #1e2d55;
  margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  max-width: 180px;
}
.sol-table-doc { font-size: 10px; color: #94a3b8; margin: 1px 0 0; }
.sol-table-status {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 3px 10px; border-radius: 999px;
  font-size: 10px; font-weight: 700;
}
.sol-status-dot { width: 5px; height: 5px; border-radius: 50%; }
.sol-status-pending { background: #fef3c7; color: #d97706; }
.sol-status-pending .sol-status-dot { background: #fbbf24; }
.sol-status-accepted { background: #dcfce7; color: #15966a; }
.sol-status-accepted .sol-status-dot { background: #22c55e; }
.sol-status-rejected { background: #fee2e2; color: #dc2626; }
.sol-status-rejected .sol-status-dot { background: #ef4444; }
.sol-table-actions { display: flex; justify-content: flex-end; gap: 4px; }

/* ── Empty state ── */
.sol-empty-wrap {
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  text-align: center; padding: 2rem 1rem;
  height: 100%;
}
.sol-empty-icon {
  color: #2f70bb;
  background: linear-gradient(135deg, #e4f0ff, #dbeafe);
  box-shadow: 0 8px 20px rgba(47, 112, 187, .15);
}

/* ── Scrollbar ── */
.custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #c5c9d0; border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }

/* ── Animaciones ── */
.anim-row-in { animation: rowIn 0.4s cubic-bezier(.22,1,.36,1) both; }
@keyframes rowIn { from { opacity:0; transform:translateX(-12px); } to { opacity:1; transform:none; } }

/* ── Shimmer ── */
.shimmer-box, .shimmer-bar { position: relative; overflow: hidden; background: #e6ebf3; }
.shimmer-box { border-radius: 6px; }
.shimmer-bar { border-radius: 4px; }
.shimmer-box::after, .shimmer-bar::after {
  content: ''; position: absolute; inset: 0;
  background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.6) 50%, transparent 100%);
  animation: shimmer 1.8s ease-in-out infinite;
}
@keyframes shimmer { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }

/* ── Modal Detalle ── */
:deep(.detalle-dialog) { border-radius: 22px; overflow: hidden; box-shadow: 0 32px 80px rgba(11,35,73,.4), 0 0 0 1px rgba(255,255,255,.08); }
:deep(.detalle-dialog .el-dialog__header) { display: none; }
:deep(.detalle-dialog .el-dialog__body) { padding: 0; }
:deep(.detalle-dialog .el-dialog__headerbtn) { z-index: 10; top: 14px; right: 14px; }
:deep(.detalle-dialog .el-dialog__headerbtn .el-dialog__close) { color: #fff; font-size: 1.1rem; }
:deep(.detalle-dialog .el-dialog__headerbtn:hover .el-dialog__close) { color: #e1f7ff; }
:deep(.el-overlay) { background-color: rgba(8,27,58,.56); backdrop-filter: blur(4px); }

.detalle-content { overflow: hidden; }

/* Header */
.detalle-head {
  position: relative;
  background: linear-gradient(135deg, #0a1f4d 0%, #0D2D6B 48%, #16468E 100%);
  padding: 22px 24px;
  display: flex; align-items: center; gap: 14px;
  overflow: hidden;
}
.detalle-head-glow {
  position: absolute; top: -30px; right: -30px;
  width: 120px; height: 120px; border-radius: 50%;
  background: radial-gradient(circle, rgba(126,179,255,0.2), transparent 70%);
  pointer-events: none;
}
.detalle-head-icon {
  width: 44px; height: 44px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; z-index: 10;
  background: rgba(255,255,255,0.12); color: #fff;
  border: 1px solid rgba(255,255,255,0.15);
}
.detalle-head-info { flex: 1; z-index: 10; }
.detalle-head-title { margin: 0; color: #fff; font-size: 17px; font-weight: 800; }
.detalle-head-sub {
  margin: 2px 0 0; color: rgba(255,255,255,0.55); font-size: 11px;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.detalle-head-badge {
  padding: 5px 14px; border-radius: 999px;
  font-size: 11px; font-weight: 700; flex-shrink: 0; z-index: 10;
}
.badge-pendiente { background: #f59e0b; color: #fff; }
.badge-aceptado { background: #22c55e; color: #fff; }
.badge-negado { background: #ef4444; color: #fff; }

/* Body */
.detalle-body { padding: 20px 24px; background: #f1f5f9; }
.detalle-cards-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }

/* Card base */
.detalle-card {
  min-width: 0;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 14px 16px;
  border-left: 4px solid #e2e8f0;
  box-shadow: 0 2px 8px rgba(22,70,142,.05);
}
.detalle-card-blue { border-left-color: #3b82f6; }
.detalle-card-amber { border-left-color: #f59e0b; }
.detalle-card-purple { border-left-color: #8b5cf6; }
.detalle-card-cyan { border-left-color: #06b6d4; }
.detalle-card-red { border-left-color: #ef4444; background: #fef2f2; border-color: #fecaca; }

.detalle-card-header {
  display: flex; align-items: center; gap: 6px;
  font-size: 11px; font-weight: 800; color: #1e2d55;
  letter-spacing: .04em; margin-bottom: 10px;
}
.detalle-card-header svg { color: #3b82f6; }
.detalle-card-blue .detalle-card-header svg { color: #3b82f6; }
.detalle-card-amber .detalle-card-header svg { color: #f59e0b; }
.detalle-card-purple .detalle-card-header svg { color: #8b5cf6; }
.detalle-card-cyan .detalle-card-header svg { color: #06b6d4; }
.detalle-card-header-red svg { color: #ef4444; }
.detalle-card-header-red { color: #dc2626; }

.detalle-card-rows { display: flex; flex-direction: column; gap: 6px; }
.detalle-card-row {
  display: flex; align-items: baseline; justify-content: space-between; gap: 8px;
}
.detalle-row-label { font-size: 11px; color: #94a3b8; flex-shrink: 0; }
.detalle-row-value {
  min-width: 0;
  font-size: 12px; font-weight: 700; color: #1e293b;
  text-align: right; overflow-wrap: break-word;
}
.detalle-card-text {
  font-size: 12px; color: #475569; line-height: 1.5; margin: 0;
  word-break: break-word; white-space: normal;
}
.detalle-historia-preview {
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 4;
  overflow: hidden;
}
.detalle-historia-button {
  margin-top: .55rem;
  padding: 0;
  color: #0284c7;
  font-size: 11px;
  font-weight: 700;
  background: transparent;
  border: 0;
  cursor: pointer;
}
.detalle-historia-button:hover { color: #0369a1; text-decoration: underline; }

:deep(.historia-dialog) { border-radius: 18px; overflow: hidden; box-shadow: 0 32px 80px rgba(11,35,73,.35); }
:deep(.historia-dialog .el-dialog__header) { margin: 0; padding: 1rem 1.25rem; background: linear-gradient(135deg, #0D2D6B, #16468E); }
:deep(.historia-dialog .el-dialog__body) { padding: 1.25rem; }
:deep(.historia-dialog .el-dialog__headerbtn) { top: 14px; right: 14px; }
:deep(.historia-dialog .el-dialog__headerbtn .el-dialog__close) { color: #fff; }
.historia-dialog-header { display: flex; align-items: center; gap: .7rem; color: #fff; }
.historia-dialog-header > svg { color: #7dd3fc; flex-shrink: 0; }
.historia-dialog-header p { margin: 0; font-size: 14px; font-weight: 800; }
.historia-dialog-header span { display: block; margin-top: 2px; color: rgba(255,255,255,.65); font-size: 11px; }
.historia-dialog-text { max-height: min(55vh, 520px); margin: 0; overflow-y: auto; white-space: pre-wrap; word-break: break-word; color: #334155; font-size: 13px; line-height: 1.7; }

.detalle-code-bar {
  display: flex; align-items: center; gap: .5rem;
  background: linear-gradient(135deg, #ecfdf5, #d1fae5); border: 1px solid #a7f3d0;
  border-radius: 12px; padding: .55rem .8rem;
}
.detalle-code-value { margin-left: auto; font-family: monospace; font-size: .9rem; font-weight: 800; color: #166534; background: #fff; padding: .2rem .6rem; border-radius: 6px; border: 1px solid #86efac; }

.detalle-adjuntos-list { display: flex; flex-direction: column; gap: 6px; margin-top: 8px; }
.detalle-adjunto-item {
  display: flex; align-items: center; gap: 8px;
  padding: 8px 12px; border-radius: 8px;
  background: #f8fafc; border: 1px solid #e2e8f0;
  text-decoration: none; transition: all .2s;
}
.detalle-adjunto-item:hover { background: #eff6ff; border-color: #bfdbfe; }
.detalle-adjunto-name { flex: 1; font-size: 12px; font-weight: 600; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.detalle-adjunto-size { font-size: 11px; color: #94a3b8; flex-shrink: 0; }

/* ── Modal Acción (Aceptar/Negar) ── */
:deep(.accion-dialog) { border-radius: 22px; overflow: hidden; box-shadow: 0 32px 80px rgba(11,35,73,.4); }
:deep(.accion-dialog .el-dialog__header) { padding: 0; margin: 0; }
:deep(.accion-dialog .el-dialog__body) { padding: 0; }
:deep(.accion-dialog .el-dialog__headerbtn) { z-index: 10; top: 14px; right: 14px; }
:deep(.accion-dialog .el-dialog__headerbtn .el-dialog__close) { color: #fff; font-size: 1.1rem; }
:deep(.accion-dialog .el-dialog__headerbtn:hover .el-dialog__close) { color: #e1f7ff; }
:deep(.accion-dialog .el-input__wrapper),
:deep(.accion-dialog .el-select__wrapper) { box-shadow: 0 0 0 1px #dce7f2 inset; border-radius: 10px; background: #fff; }
:deep(.accion-dialog .el-input__wrapper:hover),
:deep(.accion-dialog .el-select__wrapper:hover) { box-shadow: 0 0 0 1px #86b4e8 inset; }

.accion-head {
  position: relative; background: linear-gradient(125deg, #0d2d6b 0%, #16468e 50%, #1a3d8a 100%);
  padding: 1.1rem 1.3rem; display: flex; align-items: center; gap: .8rem; overflow: hidden;
}
.accion-head-glow { position: absolute; top: -40px; right: -30px; width: 140px; height: 140px; border-radius: 50%; background: rgba(255,255,255,.06); }
.accion-head-icon {
  width: 2.4rem; height: 2.4rem; border-radius: 12px;
  display: grid; place-items: center; flex-shrink: 0; z-index: 1;
  border: 1px solid rgba(255,255,255,.2);
}
.accion-head-info { flex: 1; z-index: 1; }
.accion-head-title { margin: 0; color: #fff; font-size: .9rem; font-weight: 800; }
.accion-head-sub { margin: .1rem 0 0; color: rgba(255,255,255,.55); font-size: .65rem; }
</style>
