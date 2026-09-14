<template>
  <div class="min-h-screen flex items-center justify-center px-4"
    style="background: radial-gradient(ellipse at 60% 0%, #16468E 0%, #0D2D6B 55%, #071a42 100%);">

    <!-- Glow decorativos de fondo -->
    <div class="fixed top-0 left-1/4 w-[500px] h-[500px] rounded-full opacity-[0.07] pointer-events-none"
      style="background: radial-gradient(circle, #3b82f6, transparent 70%)" />
    <div class="fixed bottom-0 right-1/4 w-[400px] h-[400px] rounded-full opacity-[0.05] pointer-events-none"
      style="background: radial-gradient(circle, #60a5fa, transparent 70%)" />

    <div class="w-full max-w-4xl relative z-10 animate-fade-in-up"
      style="animation-duration: 0.5s; animation-fill-mode: both;">

      <!-- Card -->
      <div class="rounded-2xl overflow-hidden card-enter"
        style="box-shadow: 12px 12px 24px rgba(7,26,66,0.4), -12px -12px 24px rgba(255,255,255,0.15), 0 0 60px rgba(255,255,255,0.12);">

        <!-- Header -->
        <div class="relative px-6 pt-4 pb-4 flex items-center overflow-hidden"
          style="background: linear-gradient(135deg, #16468E 0%, #0D2D6B 50%, #0a2150 100%);">
          <div class="absolute -top-8 -right-8 w-36 h-36 rounded-full opacity-[0.12]"
            style="background: radial-gradient(circle, #60a5fa, transparent 70%)" />
          <div class="absolute -bottom-6 -left-6 w-24 h-24 rounded-full opacity-[0.08]"
            style="background: radial-gradient(circle, #93c5fd, transparent 70%)" />
          <div class="absolute inset-0 opacity-[0.03]"
            style="background-image: repeating-linear-gradient(45deg, #fff 0, #fff 1px, transparent 1px, transparent 12px)" />
          <img :src="logoBlanco" alt="logo" class="h-9 object-contain relative z-10 drop-shadow-lg flex-shrink-0" />
          <div class="flex-1 text-center relative z-10">
            <h1 class="text-xl font-extrabold text-white tracking-wide">Registro de Clínica</h1>
            <p class="text-blue-300/80 text-sm font-medium mt-0.5">Solicite acceso al Sistema de Referencia</p>
          </div>
        </div>

        <!-- Cuerpo -->
        <div class="px-6 py-5 relative" style="background: #eef1f6;">
          <div class="absolute inset-0 opacity-[0.02] pointer-events-none" style="background-image: radial-gradient(circle, #0D2D6B 1px, transparent 1px); background-size: 16px 16px;" />

          <!-- ── Éxito ── -->
          <div v-if="enviado" class="py-6">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4"
              style="background: linear-gradient(135deg, #d1fae5 0%, #6ee7b7 100%); box-shadow: 0 8px 24px rgba(34,197,94,0.25);">
              <component :is="CheckCircleIcon" class="w-8 h-8 text-green-600" />
            </div>
            <h2 class="text-lg font-bold text-gray-800 mb-2 text-center">¡Solicitud enviada!</h2>
            <p class="text-sm text-gray-500 mb-1 text-center">Su solicitud fue recibida. El equipo de referencia la revisará y le notificará por correo.</p>
            <p class="text-xs text-gray-400 mb-6 text-center">El proceso puede tardar hasta 24 horas hábiles.</p>

            <!-- Resumen de datos -->
            <div class="max-w-md mx-auto rounded-xl p-4 mb-6" style="background: linear-gradient(135deg, #eef2f9 0%, #e0e8f5 100%); border-left: 3px solid #0D2D6B; box-shadow: 0 4px 12px rgba(13,45,107,0.08);">
              <div class="flex items-center justify-between mb-2 pb-2 border-b border-blue-200/50">
                <span class="text-xs font-bold text-[#0D2D6B] uppercase tracking-wider">N.° de Radicado</span>
                <span class="text-sm font-extrabold text-[#0D2D6B]">{{ radicado }}</span>
              </div>
              <div class="space-y-1.5">
                <div class="flex justify-between text-xs">
                  <span class="text-gray-500">Institución</span>
                  <span class="font-semibold text-gray-700">{{ form.razon_social }}</span>
                </div>
                <div class="flex justify-between text-xs">
                  <span class="text-gray-500">NIT</span>
                  <span class="font-semibold text-gray-700">{{ form.nit }}</span>
                </div>
                <div class="flex justify-between text-xs">
                  <span class="text-gray-500">Correo</span>
                  <span class="font-semibold text-gray-700">{{ form.email }}</span>
                </div>
                <div class="flex justify-between text-xs">
                  <span class="text-gray-500">Persona a cargo</span>
                  <span class="font-semibold text-gray-700">{{ form.representante_legal }}</span>
                </div>
                <div class="flex justify-between text-xs">
                  <span class="text-gray-500">Ubicación</span>
                  <span class="font-semibold text-gray-700">{{ form.ciudad }}, {{ form.departamento }}</span>
                </div>
              </div>
            </div>

            <div class="flex justify-center gap-3">
              <router-link :to="{ name: 'login' }">
                <button class="px-6 py-2.5 rounded-xl text-white text-sm font-bold transition-all hover:scale-[1.03] active:scale-[0.98]"
                  style="background: linear-gradient(135deg, #0D2D6B 0%, #16468E 100%); box-shadow: 0 6px 20px rgba(13,45,107,0.3);">
                  Volver al inicio
                </button>
              </router-link>
            </div>
          </div>

          <!-- ── Formulario ── -->
          <div v-else>

            <el-form ref="formRef" :model="form" :rules="rules" label-position="top" autocomplete="off">

              <!-- Sección institución -->
              <div class="flex items-center gap-2 mb-2 relative z-10">
                <div class="w-6 h-6 rounded-xl flex items-center justify-center text-[10px] font-bold text-[#0D2D6B]" style="background: #eef1f6; box-shadow: 3px 3px 6px rgba(163,177,198,0.6), -3px -3px 6px rgba(255,255,255,0.9);">1</div>
                <p class="text-xs font-bold text-[#0D2D6B] uppercase tracking-widest">Datos de la institución</p>
                <div class="flex-1 h-px" style="background: linear-gradient(90deg, #c5cfdb, transparent);" />
              </div>

                <div class="grid grid-cols-3 gap-x-3 relative z-10">
                  <el-form-item prop="nit" class="mb-1 form-item-custom">
                    <template #label><span class="flex items-center gap-1"><component :is="BuildingIcon" class="w-3 h-3 text-[#16468E]" /> NIT</span></template>
                    <el-input v-model="form.nit" placeholder="900123456 o 900123456-7" :prefix-icon="BuildingIcon" clearable autocomplete="off" @blur="validarNIT" />
                    <p v-if="nitError" class="text-xs text-red-500 mt-1">{{ nitError }}</p>
                    <p v-if="nitValido && !nitError" class="text-xs text-green-600 mt-1 flex items-center gap-1">
                      <component :is="CheckIcon" class="w-3 h-3" /> NIT válido
                    </p>
                  </el-form-item>
                  <el-form-item prop="razon_social" class="mb-1 form-item-custom">
                    <template #label><span class="flex items-center gap-1"><component :is="BuildingIcon" class="w-3 h-3 text-[#16468E]" /> Razón social</span></template>
                    <el-input v-model="form.razon_social" placeholder="Nombre legal" clearable autocomplete="off" @input="capitalizar('razon_social')" />
                  </el-form-item>
                  <el-form-item prop="telefono" class="mb-1 form-item-custom">
                    <template #label><span class="flex items-center gap-1"><component :is="PhoneIcon" class="w-3 h-3 text-[#16468E]" /> Teléfono</span></template>
                    <el-input v-model="form.telefono" placeholder="3101234567" :prefix-icon="PhoneIcon" clearable autocomplete="off" />
                  </el-form-item>
                </div>

                <div class="grid grid-cols-3 gap-x-3 relative z-10">
                  <el-form-item prop="direccion" class="mb-1 form-item-custom">
                    <template #label><span class="flex items-center gap-1"><component :is="MapPinIcon" class="w-3 h-3 text-[#16468E]" /> Dirección</span></template>
                    <el-input v-model="form.direccion" placeholder="Dirección completa" :prefix-icon="MapPinIcon" clearable autocomplete="off" @input="capitalizar('direccion')" />
                  </el-form-item>
                  <el-form-item prop="email" class="mb-1 form-item-custom">
                    <template #label><span class="flex items-center gap-1"><component :is="MailIcon" class="w-3 h-3 text-[#16468E]" /> Correo institucional</span></template>
                    <el-input v-model="form.email" type="email" placeholder="correo@clinica.com" :prefix-icon="MailIcon" clearable autocomplete="off" />
                  </el-form-item>
                  <el-form-item prop="email_confirmacion" class="mb-1 form-item-custom">
                    <template #label><span class="flex items-center gap-1"><component :is="MailCheckIcon" class="w-3 h-3 text-[#16468E]" /> Confirmar correo</span></template>
                    <el-input v-model="form.email_confirmacion" type="email" placeholder="Repita el correo" :prefix-icon="MailCheckIcon" clearable autocomplete="off" />
                  </el-form-item>
                </div>

              <!-- Separador -->
              <div class="flex items-center gap-2 mb-2 mt-2.5 relative z-10">
                <div class="w-6 h-6 rounded-xl flex items-center justify-center text-[10px] font-bold text-[#0D2D6B]" style="background: #eef1f6; box-shadow: 3px 3px 6px rgba(163,177,198,0.6), -3px -3px 6px rgba(255,255,255,0.9);">2</div>
                <p class="text-xs font-bold text-[#0D2D6B] uppercase tracking-widest">Ubicación</p>
                <div class="flex-1 h-px" style="background: linear-gradient(90deg, #c5cfdb, transparent);" />
              </div>

                <div class="grid grid-cols-2 gap-x-3 relative z-10">
                  <el-form-item prop="departamento" class="mb-1 form-item-custom">
                    <template #label><span class="flex items-center gap-1"><component :is="MapPinIcon" class="w-3 h-3 text-[#16468E]" /> Departamento</span></template>
                    <el-select v-model="form.departamento" placeholder="Seleccione" filterable autocomplete="off" class="w-full" @change="form.ciudad = ''">
                      <el-option v-for="dep in departamentos" :key="dep" :label="dep" :value="dep" />
                    </el-select>
                  </el-form-item>
                  <el-form-item prop="ciudad" class="mb-1 form-item-custom">
                    <template #label><span class="flex items-center gap-1"><component :is="MapPinIcon" class="w-3 h-3 text-[#16468E]" /> Ciudad / Municipio</span></template>
                    <el-select v-model="form.ciudad" placeholder="Seleccione o escriba el municipio" filterable allow-create default-first-option autocomplete="off" class="w-full" :disabled="!form.departamento">
                      <el-option v-for="c in ciudadesDelDepartamento" :key="c" :label="c" :value="c" />
                    </el-select>
                  </el-form-item>
                </div>

              <!-- Separador -->
              <div class="flex items-center gap-2 mb-2 mt-2.5 relative z-10">
                <div class="w-6 h-6 rounded-xl flex items-center justify-center text-[10px] font-bold text-[#0D2D6B]" style="background: #eef1f6; box-shadow: 3px 3px 6px rgba(163,177,198,0.6), -3px -3px 6px rgba(255,255,255,0.9);">3</div>
                <p class="text-xs font-bold text-[#0D2D6B] uppercase tracking-widest">Persona a cargo</p>
                <div class="flex-1 h-px" style="background: linear-gradient(90deg, #c5cfdb, transparent);" />
              </div>

                <div class="grid grid-cols-2 gap-x-3 relative z-10">
                  <el-form-item prop="representante_legal" class="mb-1 form-item-custom">
                    <template #label><span class="flex items-center gap-1"><component :is="UserIcon" class="w-3 h-3 text-[#16468E]" /> Nombre completo</span></template>
                    <el-input v-model="form.representante_legal" placeholder="Nombre del representante" :prefix-icon="UserIcon" clearable autocomplete="off" @input="capitalizar('representante_legal')" />
                  </el-form-item>
                  <el-form-item prop="cedula_representante" class="mb-1 form-item-custom">
                    <template #label><span class="flex items-center gap-1"><component :is="IdCardIcon" class="w-3 h-3 text-[#16468E]" /> Cédula</span></template>
                    <el-input v-model="form.cedula_representante" placeholder="Número de cédula" :prefix-icon="IdCardIcon" clearable autocomplete="off" />
                  </el-form-item>
                </div>

              <!-- Errores -->
              <div v-if="errorGeneral || erroresCampos.length" class="mb-3 rounded-xl p-3"
                style="background: #eef1f6; box-shadow: inset 3px 3px 6px rgba(239,68,68,0.15), inset -3px -3px 6px rgba(255,255,255,0.8);">
                <p class="text-xs font-semibold text-red-600 mb-1">{{ errorGeneral || 'Corrija los siguientes errores:' }}</p>
                <ul v-if="erroresCampos.length" class="list-disc list-inside space-y-0.5">
                  <li v-for="e in erroresCampos" :key="e" class="text-xs text-red-500">{{ e }}</li>
                </ul>
              </div>

              <!-- Botones -->
              <div class="flex gap-2 pt-1.5 relative z-10">
                <button
                  type="button"
                  class="flex-1 py-2.5 rounded-xl text-white font-bold text-xs transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-1.5 hover:scale-[1.02] active:scale-[0.98] relative overflow-hidden"
                  style="background: linear-gradient(135deg, #0D2D6B 0%, #16468E 100%); box-shadow: 4px 4px 10px rgba(163,177,198,0.5), -4px -4px 10px rgba(255,255,255,0.8);"
                  :disabled="enviando"
                  @click="enviarRegistro"
                >
                  <span v-if="enviando" class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                  <component v-else :is="SendIcon" class="w-3.5 h-3.5" />
                  Enviar solicitud
                </button>

                <router-link :to="{ name: 'login' }">
                  <button type="button" class="px-4 py-2.5 rounded-xl text-gray-500 text-xs font-semibold hover:text-[#0D2D6B] transition-all flex items-center gap-1.5 hover:scale-[1.02] active:scale-[0.98]"
                    style="background: #eef1f6; box-shadow: 4px 4px 10px rgba(163,177,198,0.5), -4px -4px 10px rgba(255,255,255,0.8);">
                    <component :is="ArrowLeftIcon" class="w-3.5 h-3.5" />
                    Ya tengo acceso
                  </button>
                </router-link>
              </div>

              <!-- Badge de seguridad -->
              <div class="flex items-center justify-center gap-1.5 mt-1.5 relative z-10">
                <component :is="ShieldCheckIcon" class="w-3 h-3 text-gray-400" />
                <span class="text-[10px] text-gray-400 font-medium">Sus datos están protegidos</span>
              </div>

            </el-form>
          </div>

          <p class="text-xs text-center text-gray-400 mt-2 mb-1 flex items-center justify-center gap-1">
            <component :is="LockIcon" class="w-3 h-3" />
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
  MailCheck as MailCheckIcon,
  Phone as PhoneIcon,
  User as UserIcon,
  Send as SendIcon,
  ArrowLeft as ArrowLeftIcon,
  CheckCircle as CheckCircleIcon,
  Check as CheckIcon,
  MapPin as MapPinIcon,
  IdCard as IdCardIcon,
  ShieldCheck as ShieldCheckIcon,
  Lock as LockIcon,
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
const radicado = ref('');
const nitError = ref('');
const nitValido = ref(false);

const form = reactive({
  nit: '', razon_social: '', email: '', email_confirmacion: '', telefono: '',
  departamento: '', ciudad: '', direccion: '', representante_legal: '', cedula_representante: '',
});

// ── Capitalizar primera letra ──
function capitalizar(campo: 'razon_social' | 'direccion' | 'representante_legal') {
  const val = form[campo];
  if (val && val.length === 1) {
    form[campo] = val.charAt(0).toUpperCase();
  } else if (val && val.length > 1) {
    form[campo] = val.charAt(0).toUpperCase() + val.slice(1);
  }
}

// ── Validación NIT (formato simple) ──
function validarNIT() {
  const digitos = form.nit.replace(/\D/g, '');
  if (!digitos) { nitError.value = ''; nitValido.value = false; return; }

  if (digitos.length < 8 || digitos.length > 11) {
    nitError.value = 'Formato inválido. Ejemplos: 900123456, 900123456-7 o 900.123.456-7';
    nitValido.value = false;
    return;
  }

  nitError.value = '';
  nitValido.value = true;
}

// ── Reglas de validación ──
const rules: FormRules = {
  nit: [{ required: true, message: 'Obligatorio', trigger: 'blur' }],
  razon_social: [{ required: true, message: 'Obligatorio', trigger: 'blur' }],
  email: [{ required: true, message: 'Obligatorio', trigger: 'blur' }, { type: 'email', message: 'Correo inválido', trigger: 'blur' }],
  email_confirmacion: [
    { required: true, message: 'Obligatorio', trigger: 'blur' },
    { type: 'email', message: 'Correo inválido', trigger: 'blur' },
    {
      validator: (_rule: any, value: string, callback: any) => {
        if (value && value !== form.email) callback(new Error('Los correos no coinciden'));
        else callback();
      },
      trigger: 'blur',
    },
  ],
  telefono: [{ required: true, message: 'Obligatorio', trigger: 'blur' }],
  direccion: [{ required: true, message: 'Obligatorio', trigger: 'blur' }],
  departamento: [{ required: true, message: 'Obligatorio', trigger: 'change' }],
  ciudad: [{ required: true, message: 'Obligatorio', trigger: 'change' }],
  representante_legal: [{ required: true, message: 'Obligatorio', trigger: 'blur' }],
  cedula_representante: [{ required: true, message: 'Obligatorio', trigger: 'blur' }],
};

// ── Envío ──
async function enviarRegistro() {
  if (!formRef.value) return;
  const esValido = await formRef.value.validate().catch(() => false);
  if (!esValido) return;
  if (!nitValido.value) {
    validarNIT();
    if (!nitValido.value) return;
  }
  try {
    enviando.value = true;
    errorGeneral.value = '';
    erroresCampos.value = [];

    const payload: Record<string, any> = { ...form };
    payload.nombre = payload.razon_social;

    const { data } = await http.post('/api/externo/registro', payload);
    enviado.value = true;
    radicado.value = data.radicado ?? '';
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

<style scoped>
.card-enter {
  animation: cardEnter 0.5s ease-out;
}

@keyframes cardEnter {
  from {
    opacity: 0;
    transform: translateY(16px) scale(0.98);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.form-item-custom :deep(.el-input__wrapper),
.form-item-custom :deep(.el-select__wrapper) {
  border-radius: 12px;
  background: #eef1f6 !important;
  box-shadow: inset 3px 3px 6px rgba(163, 177, 198, 0.5), inset -3px -3px 6px rgba(255, 255, 255, 0.8);
  transition: all 0.2s ease;
}

.form-item-custom :deep(.el-input__wrapper:hover),
.form-item-custom :deep(.el-select__wrapper:hover) {
  box-shadow: inset 3px 3px 6px rgba(163, 177, 198, 0.6), inset -3px -3px 6px rgba(255, 255, 255, 0.9);
}

.form-item-custom :deep(.el-input__wrapper.is-focus),
.form-item-custom :deep(.el-select__wrapper.is-focus) {
  box-shadow: inset 4px 4px 8px rgba(163, 177, 198, 0.6), inset -4px -4px 8px rgba(255, 255, 255, 0.95), 0 0 0 1.5px rgba(13, 45, 107, 0.15);
}

.form-item-custom :deep(.el-input__prefix-inner) {
  color: #16468E;
}

.form-item-custom :deep(.el-input__inner) {
  background: transparent !important;
}

.form-item-custom :deep(.el-form-item__label)::before,
.el-form-item :deep(.el-form-item__label)::before {
  display: none !important;
  content: '' !important;
}
</style>
