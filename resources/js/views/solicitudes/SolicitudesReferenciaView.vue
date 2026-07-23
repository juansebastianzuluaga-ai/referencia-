<template>
  <div class="p-6">

    <!-- Encabezado -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
      <div>
        <h1 class="text-xl font-bold text-gray-800">Solicitudes de referencia</h1>
        <p class="text-sm text-gray-400 mt-0.5">Gestión de solicitudes enviadas por clínicas externas</p>
      </div>
      <div class="flex gap-3">
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl px-4 py-2 text-center">
          <p class="text-lg font-bold text-yellow-600">{{ resumen.pendientes }}</p>
          <p class="text-xs text-yellow-500">Pendientes</p>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-2 text-center">
          <p class="text-lg font-bold text-green-600">{{ resumen.aceptadas }}</p>
          <p class="text-xs text-green-500">Aceptadas</p>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-2 text-center">
          <p class="text-lg font-bold text-red-500">{{ resumen.negadas }}</p>
          <p class="text-xs text-red-400">Negadas</p>
        </div>
      </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-5 flex flex-wrap gap-3 items-center">
      <el-input
        v-model="filtro.buscar"
        placeholder="Buscar por paciente, EPS, especialidad..."
        class="w-72"
        clearable
        size="small"
        :prefix-icon="SearchIcon"
      />
      <el-select v-model="filtro.estado" placeholder="Estado" size="small" class="w-40" clearable>
        <el-option label="Pendientes" value="pendiente" />
        <el-option label="Aceptadas" value="aceptado" />
        <el-option label="Negadas" value="negado" />
      </el-select>
      <el-button size="small" @click="cargar">
        <component :is="RefreshIcon" class="w-3.5 h-3.5 mr-1" />
        Actualizar
      </el-button>
    </div>

    <!-- Loading -->
    <div v-if="cargando" class="space-y-3">
      <div v-for="i in 4" :key="i" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 animate-pulse">
        <div class="h-4 bg-gray-100 rounded w-2/5 mb-3"></div>
        <div class="h-3 bg-gray-100 rounded w-1/3 mb-4"></div>
        <div class="h-3 bg-gray-100 rounded w-full mb-2"></div>
        <div class="h-3 bg-gray-100 rounded w-3/4"></div>
      </div>
    </div>

    <!-- Vacío -->
    <div v-else-if="solicitudesFiltradas.length === 0" class="bg-white rounded-2xl border border-gray-100 shadow-sm py-16 text-center text-gray-300">
      <component :is="FileTextIcon" class="w-10 h-10 mx-auto mb-2 opacity-40" />
      <p class="text-sm">No hay solicitudes con ese criterio</p>
    </div>

    <!-- Lista -->
    <div v-else class="space-y-3">
      <div
        v-for="s in solicitudesFiltradas"
        :key="s.id"
        class="bg-white rounded-2xl border shadow-sm transition-shadow hover:shadow-md"
        :class="{
          'border-yellow-200': s.estado === 'pendiente',
          'border-green-200': s.estado === 'aceptado',
          'border-red-200': s.estado === 'negado',
        }"
      >
        <div class="p-5 flex flex-wrap gap-4 items-start">

          <!-- Estado badge -->
          <div class="shrink-0 mt-0.5">
            <span
              class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
              :class="{
                'bg-yellow-100 text-yellow-700': s.estado === 'pendiente',
                'bg-green-100 text-green-700': s.estado === 'aceptado',
                'bg-red-100 text-red-600': s.estado === 'negado',
              }"
            >
              <span class="w-1.5 h-1.5 rounded-full"
                :class="{
                  'bg-yellow-500': s.estado === 'pendiente',
                  'bg-green-500': s.estado === 'aceptado',
                  'bg-red-500': s.estado === 'negado',
                }"
              />
              {{ estadoLabel(s.estado) }}
            </span>
          </div>

          <!-- Info principal -->
          <div class="flex-1 min-w-0 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-6 gap-y-2">
            <div>
              <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold">Paciente</p>
              <p class="text-sm font-bold text-gray-800 truncate">{{ nombreCompleto(s) }}</p>
              <p class="text-xs text-gray-400">{{ s.tipo_documento }}: {{ s.numero_documento }} · {{ s.genero === 'M' ? 'Masculino' : 'Femenino' }} · {{ s.edad }} años</p>
            </div>
            <div>
              <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold">Institución</p>
              <p class="text-sm font-semibold text-gray-700 truncate">{{ s.clinica?.nombre ?? '—' }}</p>
              <p class="text-xs text-gray-400 truncate">{{ s.municipio_capita }}</p>
            </div>
            <div>
              <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold">Especialidad</p>
              <p class="text-sm text-gray-700 truncate">{{ s.especialidad_requerida }}</p>
              <p class="text-xs text-gray-400 truncate">EPS: {{ s.eps }}</p>
            </div>
            <div>
              <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold">Fecha / Hora</p>
              <p class="text-sm text-gray-700">{{ formatFecha(s.fecha) }}</p>
              <p class="text-xs text-gray-400">{{ s.hora }}</p>
            </div>
          </div>

          <!-- Código aceptación si aplica -->
          <div v-if="s.codigo_aceptacion" class="shrink-0 bg-green-50 border border-green-100 rounded-xl px-3 py-2 text-center">
            <p class="text-[10px] text-green-500 font-semibold">Código</p>
            <p class="text-sm font-mono font-bold text-green-700">{{ s.codigo_aceptacion }}</p>
          </div>

          <!-- Acciones -->
          <div class="shrink-0 flex gap-2 self-center">
            <el-button size="small" @click="verDetalle(s)">
              <component :is="EyeIcon" class="w-3.5 h-3.5" />
            </el-button>
            <el-button
              v-if="s.estado !== 'aceptado'"
              size="small"
              type="success"
              @click="abrirAceptar(s)"
            >
              <component :is="CheckIcon" class="w-3.5 h-3.5 mr-1" />
              Aceptar
            </el-button>
            <el-button
              v-if="s.estado !== 'negado'"
              size="small"
              type="danger"
              @click="abrirNegar(s)"
            >
              <component :is="XIcon" class="w-3.5 h-3.5 mr-1" />
              Negar
            </el-button>
          </div>
        </div>

        <!-- Diagnóstico colapsado -->
        <div class="border-t border-gray-50 px-5 py-3 flex items-start gap-2 text-xs text-gray-500">
          <component :is="FileTextIcon" class="w-3.5 h-3.5 text-gray-300 mt-0.5 shrink-0" />
          <p class="line-clamp-2"><strong class="text-gray-600">Diagnóstico:</strong> {{ s.diagnostico }}</p>
        </div>

      </div>
    </div>

    <!-- Modal: Detalle -->
    <el-dialog v-model="modalDetalle" title="Detalle de la solicitud" width="640px" class="rounded-2xl">
      <template v-if="solicitudSeleccionada">
        <div class="space-y-5 text-sm">

          <!-- Identificación del paciente -->
          <div>
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-2">Paciente</p>
            <div class="grid grid-cols-2 gap-3">
              <div><p class="text-xs text-gray-400">Nombre completo</p><p class="font-semibold">{{ nombreCompleto(solicitudSeleccionada) }}</p></div>
              <div><p class="text-xs text-gray-400">Documento</p><p>{{ solicitudSeleccionada.tipo_documento }} {{ solicitudSeleccionada.numero_documento }}</p></div>
              <div><p class="text-xs text-gray-400">Género / Edad</p><p>{{ solicitudSeleccionada.genero === 'M' ? 'Masculino' : 'Femenino' }}, {{ solicitudSeleccionada.edad }} años</p></div>
              <div><p class="text-xs text-gray-400">EPS</p><p>{{ solicitudSeleccionada.eps }}</p></div>
              <div v-if="solicitudSeleccionada.gestante"><p class="text-xs text-gray-400">Gestante</p><p class="text-pink-600 font-semibold">Sí</p></div>
              <div v-if="solicitudSeleccionada.condicion_especial" class="col-span-2"><p class="text-xs text-gray-400">Condición especial</p><p>{{ solicitudSeleccionada.condicion_especial }}</p></div>
            </div>
          </div>

          <!-- Solicitud -->
          <div>
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-2">Solicitud</p>
            <div class="grid grid-cols-2 gap-3">
              <div><p class="text-xs text-gray-400">Institución</p><p class="font-semibold">{{ solicitudSeleccionada.clinica?.nombre ?? '—' }}</p></div>
              <div><p class="text-xs text-gray-400">Municipio / Capita</p><p>{{ solicitudSeleccionada.municipio_capita }}</p></div>
              <div><p class="text-xs text-gray-400">Especialidad requerida</p><p>{{ solicitudSeleccionada.especialidad_requerida }}</p></div>
              <div><p class="text-xs text-gray-400">Servicio ubicación actual</p><p>{{ solicitudSeleccionada.servicio_ubicacion_actual }}</p></div>
              <div v-if="solicitudSeleccionada.servicio_remision"><p class="text-xs text-gray-400">Servicio de remisión</p><p>{{ solicitudSeleccionada.servicio_remision }}</p></div>
              <div v-if="solicitudSeleccionada.via_contacto"><p class="text-xs text-gray-400">Vía de contacto</p><p>{{ solicitudSeleccionada.via_contacto }}</p></div>
              <div><p class="text-xs text-gray-400">Fecha / Hora</p><p>{{ formatFecha(solicitudSeleccionada.fecha) }} — {{ solicitudSeleccionada.hora }}</p></div>
            </div>
          </div>

          <!-- Diagnóstico / Historia -->
          <div>
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-2">Clínica</p>
            <div class="space-y-2">
              <div><p class="text-xs text-gray-400">Diagnóstico</p><p>{{ solicitudSeleccionada.diagnostico }}</p></div>
              <div class="bg-gray-50 rounded-xl p-3"><p class="text-xs text-gray-400 mb-1">Resumen historia clínica</p><p class="text-xs text-gray-700 whitespace-pre-wrap">{{ solicitudSeleccionada.resumen_historia_clinica }}</p></div>
              <div v-if="solicitudSeleccionada.observaciones" class="bg-gray-50 rounded-xl p-3"><p class="text-xs text-gray-400 mb-1">Observaciones</p><p class="text-xs text-gray-700">{{ solicitudSeleccionada.observaciones }}</p></div>
            </div>
          </div>

          <!-- Respuesta (si ya fue procesada) -->
          <div v-if="solicitudSeleccionada.nombre_quien_responde">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-2">Respuesta</p>
            <div class="grid grid-cols-2 gap-3">
              <div v-if="solicitudSeleccionada.codigo_aceptacion"><p class="text-xs text-gray-400">Código aceptación</p><p class="font-mono font-bold text-green-700">{{ solicitudSeleccionada.codigo_aceptacion }}</p></div>
              <div v-if="solicitudSeleccionada.numero_ingreso"><p class="text-xs text-gray-400">N.° ingreso</p><p>{{ solicitudSeleccionada.numero_ingreso }}</p></div>
              <div><p class="text-xs text-gray-400">Hora respuesta</p><p>{{ solicitudSeleccionada.hora_respuesta }}</p></div>
              <div><p class="text-xs text-gray-400">Respondió</p><p>{{ solicitudSeleccionada.nombre_quien_responde }}</p></div>
              <div v-if="solicitudSeleccionada.motivo_negacion" class="col-span-2 bg-red-50 rounded-xl p-3"><p class="text-xs text-red-500 font-semibold mb-1">Motivo de negación</p><p class="text-xs text-red-600">{{ solicitudSeleccionada.motivo_negacion }}</p></div>
              <div v-if="solicitudSeleccionada.observaciones_respuesta" class="col-span-2 bg-gray-50 rounded-xl p-3"><p class="text-xs text-gray-400 mb-1">Observaciones de respuesta</p><p class="text-xs text-gray-700">{{ solicitudSeleccionada.observaciones_respuesta }}</p></div>
            </div>
          </div>

        </div>
      </template>
    </el-dialog>

    <!-- Modal: Aceptar -->
    <el-dialog v-model="modalAceptar" title="Aceptar solicitud" width="460px" :close-on-click-modal="false" class="rounded-2xl">
      <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs text-gray-500 mb-1">Hora de respuesta <span class="text-red-400">*</span></label>
            <el-time-select
              v-model="formAceptar.hora_respuesta"
              placeholder="HH:MM"
              start="00:00"
              step="00:05"
              end="23:55"
              class="w-full"
            />
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">N.° de ingreso</label>
            <el-input v-model.number="formAceptar.numero_ingreso" type="number" placeholder="Opcional" />
          </div>
        </div>
        <div>
          <label class="block text-xs text-gray-500 mb-1">Nombre de quien responde <span class="text-red-400">*</span></label>
          <el-input v-model="formAceptar.nombre_quien_responde" placeholder="Nombre completo" />
        </div>
        <div>
          <label class="block text-xs text-gray-500 mb-1">Observaciones</label>
          <el-input v-model="formAceptar.observaciones_respuesta" type="textarea" :rows="3" placeholder="Opcional" />
        </div>
      </div>
      <template #footer>
        <el-button @click="modalAceptar = false">Cancelar</el-button>
        <el-button type="success" :loading="procesando" @click="aceptar">
          <component :is="CheckIcon" class="w-3.5 h-3.5 mr-1" />
          Confirmar aceptación
        </el-button>
      </template>
    </el-dialog>

    <!-- Modal: Negar -->
    <el-dialog v-model="modalNegar" title="Negar solicitud" width="460px" :close-on-click-modal="false" class="rounded-2xl">
      <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs text-gray-500 mb-1">Hora de respuesta <span class="text-red-400">*</span></label>
            <el-time-select
              v-model="formNegar.hora_respuesta"
              placeholder="HH:MM"
              start="00:00"
              step="00:05"
              end="23:55"
              class="w-full"
            />
          </div>
        </div>
        <div>
          <label class="block text-xs text-gray-500 mb-1">Motivo de negación <span class="text-red-400">*</span></label>
          <el-input v-model="formNegar.motivo_negacion" type="textarea" :rows="3" placeholder="Indique el motivo..." />
        </div>
        <div>
          <label class="block text-xs text-gray-500 mb-1">Nombre de quien responde <span class="text-red-400">*</span></label>
          <el-input v-model="formNegar.nombre_quien_responde" placeholder="Nombre completo" />
        </div>
        <div>
          <label class="block text-xs text-gray-500 mb-1">Observaciones adicionales</label>
          <el-input v-model="formNegar.observaciones_respuesta" type="textarea" :rows="2" placeholder="Opcional" />
        </div>
      </div>
      <template #footer>
        <el-button @click="modalNegar = false">Cancelar</el-button>
        <el-button type="danger" :loading="procesando" @click="negar">
          <component :is="XIcon" class="w-3.5 h-3.5 mr-1" />
          Confirmar negación
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
  created_at: string;
}

const solicitudes = ref<Solicitud[]>([]);
const cargando = ref(false);
const procesando = ref(false);
const filtro = ref({ buscar: '', estado: '' });

const modalDetalle = ref(false);
const modalAceptar = ref(false);
const modalNegar = ref(false);
const solicitudSeleccionada = ref<Solicitud | null>(null);

const formAceptar = ref({ hora_respuesta: '', nombre_quien_responde: '', numero_ingreso: null as number | null, observaciones_respuesta: '' });
const formNegar = ref({ hora_respuesta: '', motivo_negacion: '', nombre_quien_responde: '', observaciones_respuesta: '' });

const resumen = computed(() => ({
  pendientes: solicitudes.value.filter(s => s.estado === 'pendiente').length,
  aceptadas: solicitudes.value.filter(s => s.estado === 'aceptado').length,
  negadas: solicitudes.value.filter(s => s.estado === 'negado').length,
}));

const solicitudesFiltradas = computed(() => {
  return solicitudes.value.filter(s => {
    const texto = filtro.value.buscar.toLowerCase();
    const coincideTexto = !texto ||
      nombreCompleto(s).toLowerCase().includes(texto) ||
      s.eps.toLowerCase().includes(texto) ||
      s.especialidad_requerida.toLowerCase().includes(texto) ||
      s.clinica?.nombre.toLowerCase().includes(texto) ||
      s.numero_documento.toLowerCase().includes(texto);
    const coincideEstado = !filtro.value.estado || s.estado === filtro.value.estado;
    return coincideTexto && coincideEstado;
  });
});

function nombreCompleto(s: Solicitud) {
  return [s.primer_nombre, s.segundo_nombre, s.primer_apellido, s.segundo_apellido]
    .filter(Boolean).join(' ');
}

function estadoLabel(estado: string) {
  return { pendiente: 'Pendiente', aceptado: 'Aceptado', negado: 'Negado' }[estado] ?? estado;
}

function formatFecha(fecha: string) {
  return new Date(fecha + 'T00:00:00').toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' });
}

async function cargar() {
  try {
    cargando.value = true;
    const { data } = await http.get('/api/solicitudes-referencia');
    solicitudes.value = data.data;
  } catch {
    ElMessage.error('Error al cargar las solicitudes');
  } finally {
    cargando.value = false;
  }
}

function verDetalle(s: Solicitud) {
  solicitudSeleccionada.value = s;
  modalDetalle.value = true;
}

function abrirAceptar(s: Solicitud) {
  solicitudSeleccionada.value = s;
  formAceptar.value = { hora_respuesta: '', nombre_quien_responde: '', numero_ingreso: null, observaciones_respuesta: '' };
  modalAceptar.value = true;
}

function abrirNegar(s: Solicitud) {
  solicitudSeleccionada.value = s;
  formNegar.value = { hora_respuesta: '', motivo_negacion: '', nombre_quien_responde: '', observaciones_respuesta: '' };
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
