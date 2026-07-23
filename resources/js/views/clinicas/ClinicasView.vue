<template>
  <div class="p-6">

    <!-- Encabezado -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
      <div>
        <h1 class="text-xl font-bold text-gray-800">Clínicas externas</h1>
        <p class="text-sm text-gray-400 mt-0.5">Gestión y aprobación de instituciones registradas</p>
      </div>
      <div class="flex gap-3">
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl px-4 py-2 text-center">
          <p class="text-lg font-bold text-yellow-600">{{ resumen.pendientes }}</p>
          <p class="text-xs text-yellow-500">Pendientes</p>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-2 text-center">
          <p class="text-lg font-bold text-green-600">{{ resumen.activas }}</p>
          <p class="text-xs text-green-500">Activas</p>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-2 text-center">
          <p class="text-lg font-bold text-red-500">{{ resumen.rechazadas }}</p>
          <p class="text-xs text-red-400">Rechazadas</p>
        </div>
      </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-5 flex flex-wrap gap-3 items-center">
      <el-input
        v-model="filtro.buscar"
        placeholder="Buscar por nombre, NIT o ciudad..."
        class="w-64"
        clearable
        size="small"
        :prefix-icon="SearchIcon"
      />
      <el-select v-model="filtro.estado" placeholder="Estado" size="small" class="w-40" clearable>
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
      <div v-for="i in 3" :key="i" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 animate-pulse">
        <div class="h-4 bg-gray-100 rounded w-3/4 mb-3"></div>
        <div class="h-3 bg-gray-100 rounded w-1/2 mb-4"></div>
        <div class="h-3 bg-gray-100 rounded w-full mb-2"></div>
        <div class="h-3 bg-gray-100 rounded w-2/3"></div>
      </div>
    </div>

    <!-- Vacío -->
    <div v-else-if="clinicasFiltradas.length === 0" class="bg-white rounded-2xl border border-gray-100 shadow-sm py-16 text-center text-gray-300">
      <component :is="BuildingIcon" class="w-10 h-10 mx-auto mb-2 opacity-40" />
      <p class="text-sm">No hay clínicas con ese criterio</p>
    </div>

    <!-- Tarjetas -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
      <div
        v-for="clinica in clinicasFiltradas"
        :key="clinica.id"
        class="bg-white rounded-2xl border shadow-sm flex flex-col transition-shadow hover:shadow-md"
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
    <el-dialog v-model="modalDetalle" title="Detalle de la clínica" width="500px" class="rounded-2xl">
      <template v-if="clinicaSeleccionada">
        <div class="space-y-3 text-sm">
          <div class="grid grid-cols-2 gap-3">
            <div><p class="text-xs text-gray-400">NIT</p><p class="font-semibold">{{ clinicaSeleccionada.nit }}</p></div>
            <div><p class="text-xs text-gray-400">Nombre</p><p class="font-semibold">{{ clinicaSeleccionada.nombre }}</p></div>
            <div><p class="text-xs text-gray-400">Razón social</p><p>{{ clinicaSeleccionada.razon_social }}</p></div>
            <div><p class="text-xs text-gray-400">Ciudad</p><p>{{ clinicaSeleccionada.ciudad }}, {{ clinicaSeleccionada.departamento }}</p></div>
            <div><p class="text-xs text-gray-400">Dirección</p><p>{{ clinicaSeleccionada.direccion }}</p></div>
            <div><p class="text-xs text-gray-400">Teléfono</p><p>{{ clinicaSeleccionada.telefono }}</p></div>
            <div class="col-span-2"><p class="text-xs text-gray-400">Correo</p><p>{{ clinicaSeleccionada.email }}</p></div>
            <div><p class="text-xs text-gray-400">Representante legal</p><p>{{ clinicaSeleccionada.representante_legal }}</p></div>
            <div><p class="text-xs text-gray-400">Cédula</p><p>{{ clinicaSeleccionada.cedula_representante }}</p></div>
          </div>
          <div v-if="clinicaSeleccionada.observaciones" class="bg-gray-50 rounded-xl px-3 py-2">
            <p class="text-xs text-gray-400 font-semibold mb-1">Observaciones</p>
            <p class="text-xs text-gray-600">{{ clinicaSeleccionada.observaciones }}</p>
          </div>
          <div v-if="clinicaSeleccionada.motivo_rechazo" class="bg-red-50 border border-red-100 rounded-xl p-3">
            <p class="text-xs text-red-500 font-semibold mb-1">Motivo de rechazo</p>
            <p class="text-xs text-red-600">{{ clinicaSeleccionada.motivo_rechazo }}</p>
          </div>
        </div>
      </template>
    </el-dialog>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
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

function formatFecha(fecha: string) {
  return new Date(fecha).toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' });
}

onMounted(cargar);
</script>
