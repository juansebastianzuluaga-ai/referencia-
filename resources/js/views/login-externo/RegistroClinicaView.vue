<template>
  <div class="min-h-screen flex items-center justify-center px-4 py-8"
    style="background: radial-gradient(ellipse at 60% 0%, #16468E 0%, #0D2D6B 55%, #071a42 100%);">
    <div class="w-full max-w-3xl">

      <!-- Card -->
      <div class="rounded-2xl overflow-hidden"
        style="box-shadow: 0 32px 64px rgba(0,0,0,0.4), 0 0 0 1px rgba(255,255,255,0.06);">

        <!-- Header -->
        <div class="relative px-6 pt-5 pb-4 flex items-center overflow-hidden"
          style="background: linear-gradient(160deg, #16468E 0%, #0D2D6B 100%);">
          <div class="absolute -top-6 -right-6 w-32 h-32 rounded-full opacity-10"
            style="background: radial-gradient(circle, #fff, transparent)" />
          <div class="absolute -bottom-4 -left-4 w-20 h-20 rounded-full opacity-10"
            style="background: radial-gradient(circle, #fff, transparent)" />
          <!-- Logo izquierda -->
          <img :src="logoBlanco" alt="logo" class="h-8 object-contain relative z-10 drop-shadow-lg flex-shrink-0" />
          <!-- Texto centrado en el espacio restante -->
          <div class="flex-1 text-center relative z-10">
            <span class="text-base font-extrabold text-white tracking-wide">Registro de Clínica</span>
            <span class="text-blue-300 text-xs ml-2">— Solicite acceso al Sistema de Referencia</span>
          </div>
        </div>

        <!-- Cuerpo -->
        <div class="bg-white px-6 py-5">

          <!-- Éxito -->
          <div v-if="enviado" class="text-center py-8">
            <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
              <component :is="CheckCircleIcon" class="w-8 h-8 text-green-500" />
            </div>
            <h2 class="text-lg font-bold text-gray-800 mb-2">¡Solicitud enviada!</h2>
            <p class="text-sm text-gray-400 mb-1">Su solicitud fue recibida. El equipo de referencia la revisará y le notificará por correo.</p>
            <p class="text-xs text-gray-300 mb-6">El proceso puede tardar hasta 24 horas hábiles.</p>
            <router-link :to="{ name: 'login' }">
              <button class="px-6 py-2.5 rounded-xl bg-[#0D2D6B] text-white text-sm font-semibold hover:bg-[#16468E] transition-colors">
                Volver al inicio
              </button>
            </router-link>
          </div>

          <!-- Formulario -->
          <el-form v-else ref="formRef" :model="form" :rules="rules" label-position="top" size="small" autocomplete="off">

            <!-- Sección institución -->
            <div class="flex items-center gap-2 mb-2">
              <div class="w-1 h-4 rounded-full bg-[#0D2D6B]" />
              <p class="text-xs font-bold text-[#0D2D6B] uppercase tracking-widest">Datos de la institución</p>
            </div>

            <div class="grid grid-cols-3 gap-x-4">
              <el-form-item label="NIT" prop="nit" class="mb-2">
                <el-input v-model="form.nit" placeholder="900123456-7" :prefix-icon="BuildingIcon" clearable autocomplete="off" />
              </el-form-item>
              <el-form-item label="Razón social" prop="razon_social" class="mb-2">
                <el-input v-model="form.razon_social" placeholder="Nombre legal" clearable autocomplete="off" />
              </el-form-item>
              <el-form-item label="Nombre comercial / sede" prop="nombre" class="mb-2">
                <el-input v-model="form.nombre" placeholder="Nombre de la sede" clearable autocomplete="off" />
              </el-form-item>
            </div>

            <div class="grid grid-cols-4 gap-x-4">
              <el-form-item label="Correo institucional" prop="email" class="mb-2">
                <el-input v-model="form.email" type="email" placeholder="correo@clinica.com" :prefix-icon="MailIcon" clearable autocomplete="off" />
              </el-form-item>
              <el-form-item label="Teléfono" prop="telefono" class="mb-2">
                <el-input v-model="form.telefono" placeholder="3101234567" :prefix-icon="PhoneIcon" clearable autocomplete="off" />
              </el-form-item>
              <el-form-item label="Departamento" prop="departamento" class="mb-2">
                <el-select v-model="form.departamento" placeholder="Seleccione" filterable autocomplete="off" class="w-full" @change="form.ciudad = ''">
                  <el-option v-for="dep in departamentos" :key="dep" :label="dep" :value="dep" />
                </el-select>
              </el-form-item>
              <el-form-item label="Ciudad / Municipio" prop="ciudad" class="mb-2">
                <el-select v-model="form.ciudad" placeholder="Seleccione" filterable autocomplete="off" class="w-full" :disabled="!form.departamento">
                  <el-option v-for="c in ciudadesDelDepartamento" :key="c" :label="c" :value="c" />
                </el-select>
              </el-form-item>
            </div>

            <div class="grid grid-cols-1 mb-1">
              <el-form-item label="Dirección" prop="direccion" class="mb-2">
                <el-input v-model="form.direccion" placeholder="Dirección completa de la sede" clearable autocomplete="off" />
              </el-form-item>
            </div>

            <!-- Separador -->
            <div class="flex items-center gap-2 mb-2">
              <div class="w-1 h-4 rounded-full bg-[#0D2D6B]" />
              <p class="text-xs font-bold text-[#0D2D6B] uppercase tracking-widest">Representante legal</p>
            </div>

            <div class="grid grid-cols-2 gap-x-4">
              <el-form-item label="Nombre completo" prop="representante_legal" class="mb-2">
                <el-input v-model="form.representante_legal" placeholder="Nombre del representante" :prefix-icon="UserIcon" clearable autocomplete="off" />
              </el-form-item>
              <el-form-item label="Cédula" prop="cedula_representante" class="mb-2">
                <el-input v-model="form.cedula_representante" placeholder="Número de cédula" clearable autocomplete="off" />
              </el-form-item>
            </div>

            <!-- Errores -->
            <div v-if="errorGeneral || erroresCampos.length" class="mb-4 bg-red-50 border border-red-100 rounded-xl p-3">
              <p class="text-xs font-semibold text-red-600 mb-1">{{ errorGeneral || 'Corrija los siguientes errores:' }}</p>
              <ul v-if="erroresCampos.length" class="list-disc list-inside space-y-0.5">
                <li v-for="e in erroresCampos" :key="e" class="text-xs text-red-500">{{ e }}</li>
              </ul>
            </div>

            <!-- Botones -->
            <div class="flex gap-3 pt-1">
              <button
                class="flex-1 py-2.5 rounded-xl bg-[#0D2D6B] text-white font-bold text-sm hover:bg-[#16468E] transition-colors disabled:opacity-60 flex items-center justify-center gap-2"
                :disabled="enviando"
                @click="enviarRegistro"
              >
                <span v-if="enviando" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                <component v-else :is="SendIcon" class="w-4 h-4" />
                Enviar solicitud de registro
              </button>
              <router-link :to="{ name: 'login' }">
                <button class="px-5 py-2.5 rounded-xl border-2 border-gray-200 text-gray-500 text-sm font-semibold hover:border-gray-300 hover:text-gray-700 transition-colors flex items-center gap-2">
                  <component :is="ArrowLeftIcon" class="w-4 h-4" />
                  Ya tengo acceso
                </button>
              </router-link>
            </div>

          </el-form>

          <p class="text-xs text-center text-gray-300 mt-6">
            © {{ new Date().getFullYear() }} Santa Bárbara — Uso exclusivo clínicas externas
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue';
import type { FormInstance, FormRules } from 'element-plus';
import {
  Building2 as BuildingIcon,
  Mail as MailIcon,
  Phone as PhoneIcon,
  User as UserIcon,
  Send as SendIcon,
  ArrowLeft as ArrowLeftIcon,
  CheckCircle as CheckCircleIcon,
} from '@lucide/vue';
import http from '@/plugins/axios';

const logoBlanco = '/images/logo-w.png';

const colombiaDatos: Record<string, string[]> = {
  'Amazonas': ['Leticia', 'Puerto Nariño'],
  'Antioquia': ['Medellín', 'Bello', 'Itagüí', 'Envigado', 'Apartadó', 'Turbo', 'Rionegro', 'Caucasia', 'Marinilla', 'El Carmen de Viboral', 'Sabaneta', 'Copacabana', 'Girardota', 'Barbosa', 'La Estrella', 'Caldas'],
  'Arauca': ['Arauca', 'Saravena', 'Tame', 'Arauquita', 'Fortul'],
  'Atlántico': ['Barranquilla', 'Soledad', 'Malambo', 'Sabanalarga', 'Baranoa', 'Puerto Colombia'],
  'Bolívar': ['Cartagena', 'Magangué', 'Turbaco', 'El Carmen de Bolívar', 'Mompós'],
  'Boyacá': ['Tunja', 'Duitama', 'Sogamoso', 'Chiquinquirá', 'Paipa', 'Moniquirá'],
  'Caldas': ['Manizales', 'Villamaría', 'Chinchiná', 'Riosucio', 'Salamina', 'Aguadas'],
  'Caquetá': ['Florencia', 'San Vicente del Caguán', 'Puerto Rico', 'Belén de los Andaquíes'],
  'Casanare': ['Yopal', 'Aguazul', 'Villanueva', 'Tauramena', 'Paz de Ariporo'],
  'Cauca': ['Popayán', 'Santander de Quilichao', 'Puerto Tejada', 'Patía', 'El Tambo'],
  'Cesar': ['Valledupar', 'Aguachica', 'Bosconia', 'Codazzi', 'La Jagua de Ibirico'],
  'Chocó': ['Quibdó', 'Istmina', 'Tadó', 'Condoto', 'Bagadó'],
  'Córdoba': ['Montería', 'Cereté', 'Lorica', 'Sahagún', 'Montelíbano', 'Tierralta'],
  'Cundinamarca': ['Bogotá D.C.', 'Soacha', 'Zipaquirá', 'Facatativá', 'Chía', 'Mosquera', 'Funza', 'Madrid', 'Fusagasugá', 'Girardot', 'Cajicá'],
  'Guainía': ['Inírida'],
  'Guaviare': ['San José del Guaviare', 'Calamar'],
  'Huila': ['Neiva', 'Pitalito', 'Garzón', 'La Plata', 'Campoalegre'],
  'La Guajira': ['Riohacha', 'Maicao', 'Uribia', 'Manaure', 'Fonseca'],
  'Magdalena': ['Santa Marta', 'Ciénaga', 'Fundación', 'El Banco', 'Plato'],
  'Meta': ['Villavicencio', 'Acacías', 'Granada', 'San Martín', 'Puerto López'],
  'Nariño': ['Pasto', 'Tumaco', 'Ipiales', 'Túquerres', 'La Unión'],
  'Norte de Santander': ['Cúcuta', 'Ocaña', 'Pamplona', 'Villa del Rosario', 'Los Patios', 'Tibú'],
  'Putumayo': ['Mocoa', 'Puerto Asís', 'Orito', 'Valle del Guamuez', 'Sibundoy'],
  'Quindío': ['Armenia', 'Calarcá', 'Montenegro', 'Quimbaya', 'La Tebaida'],
  'Risaralda': ['Pereira', 'Dosquebradas', 'Santa Rosa de Cabal', 'La Virginia', 'Quinchía'],
  'San Andrés': ['San Andrés', 'Providencia'],
  'Santander': ['Bucaramanga', 'Floridablanca', 'Girón', 'Piedecuesta', 'Barrancabermeja', 'San Gil', 'Socorro'],
  'Sucre': ['Sincelejo', 'Corozal', 'Sampués', 'San Marcos', 'Tolú'],
  'Tolima': ['Ibagué', 'Espinal', 'Girardot', 'Honda', 'Melgar', 'Chaparral'],
  'Valle del Cauca': ['Cali', 'Buenaventura', 'Palmira', 'Tuluá', 'Buga', 'Cartago', 'Jamundí', 'Yumbo', 'Florida', 'Pradera'],
  'Vaupés': ['Mitú'],
  'Vichada': ['Puerto Carreño'],
};

const departamentos = Object.keys(colombiaDatos).sort();
const ciudadesDelDepartamento = computed(() => form.departamento ? colombiaDatos[form.departamento] ?? [] : []);

const formRef = ref<FormInstance>();
const enviando = ref(false);
const enviado = ref(false);
const errorGeneral = ref('');
const erroresCampos = ref<string[]>([]);

const form = reactive({
  nit: '', razon_social: '', nombre: '', email: '', telefono: '',
  departamento: '', ciudad: '', direccion: '', representante_legal: '', cedula_representante: '',
});

const rules: FormRules = {
  nit: [{ required: true, message: 'Obligatorio', trigger: 'blur' }],
  razon_social: [{ required: true, message: 'Obligatorio', trigger: 'blur' }],
  nombre: [{ required: true, message: 'Obligatorio', trigger: 'blur' }],
  email: [{ required: true, message: 'Obligatorio', trigger: 'blur' }, { type: 'email', message: 'Correo inválido', trigger: 'blur' }],
  telefono: [{ required: true, message: 'Obligatorio', trigger: 'blur' }],
  departamento: [{ required: true, message: 'Obligatorio', trigger: 'change' }],
  ciudad: [{ required: true, message: 'Obligatorio', trigger: 'change' }],
  direccion: [{ required: true, message: 'Obligatorio', trigger: 'blur' }],
  representante_legal: [{ required: true, message: 'Obligatorio', trigger: 'blur' }],
  cedula_representante: [{ required: true, message: 'Obligatorio', trigger: 'blur' }],
};

async function enviarRegistro() {
  if (!formRef.value) return;
  const esValido = await formRef.value.validate().catch(() => false);
  if (!esValido) return;
  try {
    enviando.value = true;
    errorGeneral.value = '';
    erroresCampos.value = [];
    await http.post('/api/externo/registro', form);
    enviado.value = true;
  } catch (e: any) {
    const errors = e?.response?.data?.errors;
    if (errors) {
      erroresCampos.value = Object.values(errors).flat() as string[];
    } else {
      errorGeneral.value = e?.response?.data?.message ?? 'Error al enviar la solicitud. Intente de nuevo.';
    }
  } finally {
    enviando.value = false;
  }
}
</script>
