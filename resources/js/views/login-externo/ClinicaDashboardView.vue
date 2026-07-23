<template>
  <div class="min-h-screen bg-gray-50">

    <!-- Header institucional -->
    <div class="bg-[#0D2D6B] text-white px-6 py-5 flex items-center justify-between">
      <div>
        <h1 class="text-lg font-bold leading-tight">{{ clinicaAuth.clinica?.nombre }}</h1>
        <p class="text-blue-200 text-xs mt-0.5">Panel de solicitudes de referencia · Clínica Santa Bárbara</p>
      </div>
      <div class="flex items-center gap-3">
        <el-button type="primary" @click="abrirFormulario" class="!bg-white !text-[#0D2D6B] !border-white font-semibold">
          <component :is="PlusIcon" class="w-4 h-4 mr-1" />
          Nueva solicitud
        </el-button>
        <el-button text @click="logout" class="!text-white">
          <component :is="LogOutIcon" class="w-4 h-4" />
        </el-button>
      </div>
    </div>

    <div class="p-6">

      <!-- Stats -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 text-center">
          <p class="text-2xl font-bold text-gray-800">{{ stats.total }}</p>
          <p class="text-xs text-gray-400 mt-0.5">Total</p>
        </div>
        <div class="bg-yellow-50 rounded-2xl border border-yellow-100 shadow-sm p-4 text-center">
          <p class="text-2xl font-bold text-yellow-600">{{ stats.pendientes }}</p>
          <p class="text-xs text-yellow-500 mt-0.5">Pendientes</p>
        </div>
        <div class="bg-green-50 rounded-2xl border border-green-100 shadow-sm p-4 text-center">
          <p class="text-2xl font-bold text-green-600">{{ stats.aceptadas }}</p>
          <p class="text-xs text-green-500 mt-0.5">Aceptadas</p>
        </div>
        <div class="bg-red-50 rounded-2xl border border-red-100 shadow-sm p-4 text-center">
          <p class="text-2xl font-bold text-red-500">{{ stats.negadas }}</p>
          <p class="text-xs text-red-400 mt-0.5">Negadas</p>
        </div>
      </div>

      <!-- Lista de solicitudes -->
      <div v-if="cargando" class="space-y-3">
        <div v-for="i in 3" :key="i" class="bg-white rounded-2xl border border-gray-100 p-5 animate-pulse h-24" />
      </div>

      <div v-else-if="solicitudes.length === 0" class="bg-white rounded-2xl border border-gray-100 shadow-sm py-16 text-center">
        <component :is="ClipboardIcon" class="w-10 h-10 mx-auto mb-3 text-gray-200" />
        <p class="text-sm text-gray-400">Aún no ha enviado solicitudes</p>
        <el-button type="primary" class="mt-4 !bg-[#0D2D6B]" @click="abrirFormulario">Crear primera solicitud</el-button>
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="sol in solicitudes"
          :key="sol.id"
          class="bg-white rounded-2xl border shadow-sm hover:shadow-md transition-shadow cursor-pointer p-5 flex items-start gap-4"
          :class="{
            'border-yellow-200': sol.estado === 'pendiente',
            'border-green-200': sol.estado === 'aceptado',
            'border-red-200': sol.estado === 'negado',
          }"
          @click="verDetalle(sol)"
        >
          <!-- Info paciente -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1">
              <span
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold"
                :class="{
                  'bg-yellow-100 text-yellow-700': sol.estado === 'pendiente',
                  'bg-green-100 text-green-700': sol.estado === 'aceptado',
                  'bg-red-100 text-red-600': sol.estado === 'negado',
                }"
              >
                <span class="w-1.5 h-1.5 rounded-full"
                  :class="{
                    'bg-yellow-500': sol.estado === 'pendiente',
                    'bg-green-500': sol.estado === 'aceptado',
                    'bg-red-500': sol.estado === 'negado',
                  }"
                />
                {{ sol.estado === 'pendiente' ? 'Pendiente' : sol.estado === 'aceptado' ? 'Aceptada' : 'Negada' }}
              </span>
              <span v-if="sol.codigo_aceptacion" class="text-xs font-mono text-green-600 font-semibold">{{ sol.codigo_aceptacion }}</span>
            </div>
            <p class="font-semibold text-gray-800 text-sm">
              {{ sol.primer_nombre }} {{ sol.primer_apellido }}
            </p>
            <p class="text-xs text-gray-500 mt-0.5 truncate">{{ sol.diagnostico }}</p>
            <div class="flex flex-wrap gap-3 mt-2 text-xs text-gray-400">
              <span>{{ sol.eps }}</span>
              <span>·</span>
              <span>{{ sol.especialidad_requerida }}</span>
              <span>·</span>
              <span>{{ sol.servicio_ubicacion_actual }}</span>
            </div>
          </div>
          <!-- Fecha -->
          <div class="text-right shrink-0">
            <p class="text-xs text-gray-400">{{ formatFecha(sol.created_at) }}</p>
            <p class="text-xs text-gray-300 mt-0.5">{{ sol.hora }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Drawer: Nueva Solicitud -->
    <el-drawer
      v-model="drawerVisible"
      title="Nueva Solicitud de Referencia"
      direction="rtl"
      size="600px"
      :close-on-click-modal="false"
      :destroy-on-close="true"
    >
      <div class="px-1 pb-6">
        <el-form :model="form" :rules="rules" ref="formRef" label-position="top" size="default">

          <!-- Sección 1: Datos del paciente -->
          <div class="mb-5">
            <div class="flex items-center gap-2 mb-3">
              <div class="w-6 h-6 rounded-full bg-[#0D2D6B] text-white text-xs flex items-center justify-center font-bold shrink-0">1</div>
              <p class="font-semibold text-gray-700 text-sm">Datos del paciente</p>
            </div>
            <div class="grid grid-cols-2 gap-x-4 gap-y-1">
              <el-form-item label="Fecha" prop="fecha" required>
                <el-date-picker v-model="form.fecha" type="date" format="DD/MM/YYYY" value-format="YYYY-MM-DD" class="w-full" placeholder="Seleccione" />
              </el-form-item>
              <el-form-item label="Hora" prop="hora" required>
                <el-time-picker v-model="form.hora" format="HH:mm" value-format="HH:mm" class="w-full" placeholder="HH:MM" />
              </el-form-item>
              <el-form-item label="Primer nombre" prop="primer_nombre" required>
                <el-input v-model="form.primer_nombre" autocomplete="off" />
              </el-form-item>
              <el-form-item label="Segundo nombre">
                <el-input v-model="form.segundo_nombre" autocomplete="off" />
              </el-form-item>
              <el-form-item label="Primer apellido" prop="primer_apellido" required>
                <el-input v-model="form.primer_apellido" autocomplete="off" />
              </el-form-item>
              <el-form-item label="Segundo apellido">
                <el-input v-model="form.segundo_apellido" autocomplete="off" />
              </el-form-item>
              <el-form-item label="Tipo de documento" prop="tipo_documento" required>
                <el-select v-model="form.tipo_documento" class="w-full" placeholder="Tipo">
                  <el-option v-for="t in TIPOS_DOCUMENTO" :key="t" :label="t" :value="t" />
                </el-select>
              </el-form-item>
              <el-form-item label="Número de documento" prop="numero_documento" required>
                <el-input v-model="form.numero_documento" autocomplete="off" />
              </el-form-item>
              <el-form-item label="Edad" prop="edad" required>
                <el-input-number v-model="form.edad" :min="0" :max="120" class="w-full" />
              </el-form-item>
              <el-form-item label="Género" prop="genero" required>
                <el-select v-model="form.genero" class="w-full" placeholder="Seleccione">
                  <el-option label="Masculino" value="M" />
                  <el-option label="Femenino" value="F" />
                </el-select>
              </el-form-item>
            </div>
            <el-form-item label="EPS / Aseguradora" prop="eps" required>
              <el-select v-model="form.eps" filterable class="w-full" placeholder="Seleccione o escriba">
                <el-option v-for="e in EPS_LIST" :key="e" :label="e" :value="e" />
              </el-select>
            </el-form-item>
          </div>

          <el-divider />

          <!-- Sección 2: Datos de la remisión -->
          <div class="mb-5">
            <div class="flex items-center gap-2 mb-3">
              <div class="w-6 h-6 rounded-full bg-[#0D2D6B] text-white text-xs flex items-center justify-center font-bold shrink-0">2</div>
              <p class="font-semibold text-gray-700 text-sm">Datos de la remisión</p>
            </div>
            <div class="grid grid-cols-2 gap-x-4 gap-y-1">
              <el-form-item label="Diagnóstico (CIE-10)" prop="diagnostico" required class="col-span-2">
                <el-input v-model="form.diagnostico" placeholder="Ej: J18.9 Neumonía no especificada" autocomplete="off" />
              </el-form-item>
              <el-form-item label="Municipio de origen" prop="municipio_capita" required>
                <el-input v-model="form.municipio_capita" autocomplete="off" />
              </el-form-item>
              <el-form-item label="Especialidad requerida" prop="especialidad_requerida" required>
                <el-select v-model="form.especialidad_requerida" filterable class="w-full" placeholder="Seleccione">
                  <el-option v-for="e in ESPECIALIDADES" :key="e" :label="e" :value="e" />
                </el-select>
              </el-form-item>
              <el-form-item label="Servicio / Ubicación actual" prop="servicio_ubicacion_actual" required>
                <el-select v-model="form.servicio_ubicacion_actual" class="w-full" placeholder="Seleccione">
                  <el-option v-for="s in SERVICIOS" :key="s" :label="s" :value="s" />
                </el-select>
              </el-form-item>
              <el-form-item label="Servicio al que se remite">
                <el-select v-model="form.servicio_remision" class="w-full" placeholder="Seleccione" clearable>
                  <el-option v-for="s in SERVICIOS" :key="s" :label="s" :value="s" />
                </el-select>
              </el-form-item>
              <el-form-item label="Vía de contacto">
                <el-select v-model="form.via_contacto" class="w-full" placeholder="Seleccione" clearable>
                  <el-option label="Email" value="EMAIL" />
                  <el-option label="Telefónica" value="TELEFONICA" />
                  <el-option label="N/A" value="N/A" />
                </el-select>
              </el-form-item>
              <el-form-item label="¿Paciente gestante?">
                <el-select v-model="form.gestante" class="w-full" placeholder="Seleccione" clearable>
                  <el-option label="Sí" :value="true" />
                  <el-option label="No" :value="false" />
                </el-select>
              </el-form-item>
              <el-form-item label="Condición especial" class="col-span-2">
                <el-input v-model="form.condicion_especial" placeholder="Ej: Paciente con discapacidad, obesidad mórbida..." autocomplete="off" />
              </el-form-item>
            </div>
          </div>

          <el-divider />

          <!-- Sección 3: Historia clínica -->
          <div class="mb-5">
            <div class="flex items-center gap-2 mb-3">
              <div class="w-6 h-6 rounded-full bg-[#0D2D6B] text-white text-xs flex items-center justify-center font-bold shrink-0">3</div>
              <p class="font-semibold text-gray-700 text-sm">Resumen clínico</p>
            </div>
            <el-form-item label="Resumen de la historia clínica" prop="resumen_historia_clinica" required>
              <el-input
                v-model="form.resumen_historia_clinica"
                type="textarea"
                :rows="5"
                placeholder="Describa el motivo de remisión, antecedentes relevantes, estado actual del paciente..."
              />
            </el-form-item>
            <el-form-item label="Observaciones adicionales">
              <el-input
                v-model="form.observaciones"
                type="textarea"
                :rows="2"
                placeholder="Información adicional relevante..."
              />
            </el-form-item>
          </div>

        </el-form>

        <!-- Footer del drawer -->
        <div class="flex gap-3 pt-2 border-t border-gray-100">
          <el-button class="flex-1" @click="drawerVisible = false">Cancelar</el-button>
          <el-button type="primary" class="flex-1 !bg-[#0D2D6B]" :loading="guardando" @click="guardar">
            Enviar solicitud
          </el-button>
        </div>
      </div>
    </el-drawer>

    <!-- Modal: Detalle de solicitud -->
    <el-dialog v-model="modalDetalle" title="Detalle de solicitud" width="560px" class="rounded-2xl">
      <template v-if="solicitudSeleccionada">
        <div class="space-y-4 text-sm">
          <!-- Estado -->
          <div class="flex items-center justify-between">
            <span
              class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-semibold"
              :class="{
                'bg-yellow-100 text-yellow-700': solicitudSeleccionada.estado === 'pendiente',
                'bg-green-100 text-green-700': solicitudSeleccionada.estado === 'aceptado',
                'bg-red-100 text-red-600': solicitudSeleccionada.estado === 'negado',
              }"
            >
              {{ solicitudSeleccionada.estado === 'pendiente' ? 'Pendiente' : solicitudSeleccionada.estado === 'aceptado' ? 'Aceptada' : 'Negada' }}
            </span>
            <span v-if="solicitudSeleccionada.codigo_aceptacion" class="font-mono text-green-600 font-bold text-sm">
              {{ solicitudSeleccionada.codigo_aceptacion }}
            </span>
          </div>

          <!-- Paciente -->
          <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Paciente</p>
            <div class="grid grid-cols-2 gap-2">
              <div><p class="text-xs text-gray-400">Nombre completo</p><p class="font-medium">{{ solicitudSeleccionada.primer_nombre }} {{ solicitudSeleccionada.segundo_nombre }} {{ solicitudSeleccionada.primer_apellido }} {{ solicitudSeleccionada.segundo_apellido }}</p></div>
              <div><p class="text-xs text-gray-400">Documento</p><p>{{ solicitudSeleccionada.tipo_documento }} {{ solicitudSeleccionada.numero_documento }}</p></div>
              <div><p class="text-xs text-gray-400">Edad / Género</p><p>{{ solicitudSeleccionada.edad }} años · {{ solicitudSeleccionada.genero === 'M' ? 'Masculino' : 'Femenino' }}</p></div>
              <div><p class="text-xs text-gray-400">EPS</p><p>{{ solicitudSeleccionada.eps }}</p></div>
              <div class="col-span-2"><p class="text-xs text-gray-400">Diagnóstico</p><p>{{ solicitudSeleccionada.diagnostico }}</p></div>
            </div>
          </div>

          <!-- Remisión -->
          <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Remisión</p>
            <div class="grid grid-cols-2 gap-2">
              <div><p class="text-xs text-gray-400">Municipio origen</p><p>{{ solicitudSeleccionada.municipio_capita }}</p></div>
              <div><p class="text-xs text-gray-400">Especialidad</p><p>{{ solicitudSeleccionada.especialidad_requerida }}</p></div>
              <div><p class="text-xs text-gray-400">Servicio actual</p><p>{{ solicitudSeleccionada.servicio_ubicacion_actual }}</p></div>
              <div v-if="solicitudSeleccionada.servicio_remision"><p class="text-xs text-gray-400">Servicio destino</p><p>{{ solicitudSeleccionada.servicio_remision }}</p></div>
            </div>
          </div>

          <!-- Historia clínica -->
          <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Historia clínica</p>
            <p class="text-gray-700 text-sm bg-gray-50 rounded-xl p-3">{{ solicitudSeleccionada.resumen_historia_clinica }}</p>
          </div>

          <!-- Respuesta interna (si existe) -->
          <div v-if="solicitudSeleccionada.nombre_quien_responde" class="bg-blue-50 rounded-xl p-3 border border-blue-100">
            <p class="text-xs font-semibold text-blue-600 mb-1">Respuesta del equipo de referencia</p>
            <p class="text-xs text-blue-700">Por: {{ solicitudSeleccionada.nombre_quien_responde }} · {{ solicitudSeleccionada.hora_respuesta }}</p>
            <p v-if="solicitudSeleccionada.observaciones_respuesta" class="text-xs text-blue-600 mt-1">{{ solicitudSeleccionada.observaciones_respuesta }}</p>
          </div>
          <div v-if="solicitudSeleccionada.motivo_negacion" class="bg-red-50 rounded-xl p-3 border border-red-100">
            <p class="text-xs font-semibold text-red-500 mb-1">Motivo de negación</p>
            <p class="text-xs text-red-600">{{ solicitudSeleccionada.motivo_negacion }}</p>
          </div>
        </div>
      </template>
    </el-dialog>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { ElMessage } from 'element-plus';
import {
  Plus as PlusIcon,
  LogOut as LogOutIcon,
  ClipboardList as ClipboardIcon,
} from '@lucide/vue';
import http from '@/plugins/axios';
import { useClinicaAuthStore } from '@/stores/clinicaAuth';
import { TIPOS_DOCUMENTO, EPS_LIST, ESPECIALIDADES, SERVICIOS } from '@/data/referencia';

const clinicaAuth = useClinicaAuthStore();

const solicitudes = ref<any[]>([]);
const cargando = ref(false);
const drawerVisible = ref(false);
const guardando = ref(false);
const formRef = ref();
const modalDetalle = ref(false);
const solicitudSeleccionada = ref<any>(null);

const stats = computed(() => ({
  total: solicitudes.value.length,
  pendientes: solicitudes.value.filter(s => s.estado === 'pendiente').length,
  aceptadas: solicitudes.value.filter(s => s.estado === 'aceptado').length,
  negadas: solicitudes.value.filter(s => s.estado === 'negado').length,
}));

function emptyForm() {
  const now = new Date();
  return {
    fecha: now.toISOString().slice(0, 10),
    hora: now.toTimeString().slice(0, 5),
    primer_nombre: '',
    segundo_nombre: '',
    primer_apellido: '',
    segundo_apellido: '',
    genero: '',
    edad: null as number | null,
    tipo_documento: '',
    numero_documento: '',
    eps: '',
    diagnostico: '',
    municipio_capita: '',
    especialidad_requerida: '',
    servicio_ubicacion_actual: '',
    servicio_remision: '',
    via_contacto: '',
    gestante: null as boolean | null,
    condicion_especial: '',
    resumen_historia_clinica: '',
    observaciones: '',
  };
}

const form = ref(emptyForm());

const rules = {
  fecha: [{ required: true, message: 'Requerido', trigger: 'change' }],
  hora: [{ required: true, message: 'Requerido', trigger: 'change' }],
  primer_nombre: [{ required: true, message: 'Requerido', trigger: 'blur' }],
  primer_apellido: [{ required: true, message: 'Requerido', trigger: 'blur' }],
  genero: [{ required: true, message: 'Requerido', trigger: 'change' }],
  edad: [{ required: true, message: 'Requerido', trigger: 'blur' }],
  tipo_documento: [{ required: true, message: 'Requerido', trigger: 'change' }],
  numero_documento: [{ required: true, message: 'Requerido', trigger: 'blur' }],
  eps: [{ required: true, message: 'Requerido', trigger: 'change' }],
  diagnostico: [{ required: true, message: 'Requerido', trigger: 'blur' }],
  municipio_capita: [{ required: true, message: 'Requerido', trigger: 'blur' }],
  especialidad_requerida: [{ required: true, message: 'Requerido', trigger: 'change' }],
  servicio_ubicacion_actual: [{ required: true, message: 'Requerido', trigger: 'change' }],
  resumen_historia_clinica: [{ required: true, message: 'Requerido', trigger: 'blur' }],
};

async function cargar() {
  cargando.value = true;
  try {
    const { data } = await http.get('/api/externo/solicitudes');
    solicitudes.value = data.data;
  } catch {
    ElMessage.error('Error al cargar solicitudes');
  } finally {
    cargando.value = false;
  }
}

function abrirFormulario() {
  form.value = emptyForm();
  drawerVisible.value = true;
}

async function guardar() {
  try {
    await formRef.value?.validate();
  } catch {
    ElMessage.error('Por favor complete todos los campos requeridos');
    return;
  }
  guardando.value = true;
  try {
    await http.post('/api/externo/solicitudes', form.value);
    ElMessage.success('Solicitud enviada correctamente');
    drawerVisible.value = false;
    await cargar();
  } catch (e: any) {
    const errors = e.response?.data?.data?.errors || e.response?.data?.errors;
    if (errors) {
      ElMessage.error(Object.values(errors).flat().join('\n'));
    } else {
      ElMessage.error('Error al enviar la solicitud');
    }
  } finally {
    guardando.value = false;
  }
}

function verDetalle(sol: any) {
  solicitudSeleccionada.value = sol;
  modalDetalle.value = true;
}

async function logout() {
  await clinicaAuth.logout();
}

function formatFecha(fecha: string) {
  return new Date(fecha).toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' });
}

onMounted(cargar);
</script>
