<template>
  <div class="min-h-screen flex items-center justify-center px-4 py-8"
    style="background: radial-gradient(ellipse at 60% 0%, #16468E 0%, #0D2D6B 55%, #071a42 100%);">

    <!-- Glow decorativos de fondo -->
    <div class="fixed top-0 left-1/4 w-[500px] h-[500px] rounded-full opacity-[0.07] pointer-events-none"
      style="background: radial-gradient(circle, #3b82f6, transparent 70%)" />
    <div class="fixed bottom-0 right-1/4 w-[400px] h-[400px] rounded-full opacity-[0.05] pointer-events-none"
      style="background: radial-gradient(circle, #60a5fa, transparent 70%)" />

    <div class="w-full max-w-md relative z-10">

      <!-- Card -->
      <div class="rounded-2xl overflow-hidden card-enter"
        style="box-shadow: 12px 12px 24px rgba(7,26,66,0.4), -12px -12px 24px rgba(255,255,255,0.15), 0 0 60px rgba(255,255,255,0.12);">

        <!-- Header con gradiente azul -->
        <div class="relative px-6 pt-5 pb-4 flex flex-col items-center overflow-hidden"
          style="background: linear-gradient(135deg, #16468E 0%, #0D2D6B 50%, #0a2150 100%);">
          <div class="absolute -top-8 -right-8 w-36 h-36 rounded-full opacity-[0.12]"
            style="background: radial-gradient(circle, #60a5fa, transparent 70%)" />
          <div class="absolute -bottom-6 -left-6 w-24 h-24 rounded-full opacity-[0.08]"
            style="background: radial-gradient(circle, #93c5fd, transparent 70%)" />
          <div class="absolute inset-0 opacity-[0.03]"
            style="background-image: repeating-linear-gradient(45deg, #fff 0, #fff 1px, transparent 1px, transparent 12px)" />
          <img :src="logoBlanco" alt="logo" class="h-10 object-contain relative z-10 drop-shadow-lg mb-3" />
          <h1 class="text-xl font-extrabold text-white tracking-wide relative z-10">Acceso Clínicas</h1>
          <p class="text-blue-300/80 text-xs relative z-10 mt-1">
            Sistema de Referencia y Contrarreferencia
          </p>
        </div>

        <!-- Cuerpo -->
        <div class="px-8 py-6 relative" style="background: #eef1f6;">
          <div class="absolute inset-0 opacity-[0.02] pointer-events-none" style="background-image: radial-gradient(circle, #0D2D6B 1px, transparent 1px); background-size: 16px 16px;" />

          <!-- Paso 1: Ingresar NIT -->
          <div v-if="paso === 1" class="relative z-10">
            <div class="flex items-center gap-2 mb-4">
              <div class="w-6 h-6 rounded-xl flex items-center justify-center text-[10px] font-bold text-[#0D2D6B]" style="background: #eef1f6; box-shadow: 3px 3px 6px rgba(163,177,198,0.6), -3px -3px 6px rgba(255,255,255,0.9);">1</div>
              <p class="text-xs font-bold text-[#0D2D6B] uppercase tracking-widest">Identificación</p>
              <div class="flex-1 h-px" style="background: linear-gradient(90deg, #c5cfdb, transparent);" />
            </div>

            <p class="text-sm text-gray-500 mb-5 text-center">
              Ingrese el <strong class="text-gray-700">NIT</strong> o <strong class="text-gray-700">cédula</strong> de la clínica para continuar.
            </p>

            <el-form @submit.prevent="buscarClinica" label-position="top">
              <el-form-item class="form-item-custom">
                <template #label><span class="flex items-center gap-1"><component :is="BuildingIcon" class="w-3 h-3 text-[#16468E]" /> NIT o Cédula de la clínica</span></template>
                <el-input
                  v-model="nit"
                  placeholder="Ej: 900123456"
                  :prefix-icon="BuildingIcon"
                  clearable
                  size="large"
                  :disabled="buscando"
                  @keyup.enter="buscarClinica"
                />
              </el-form-item>

              <el-alert
                v-if="error"
                :title="error"
                type="error"
                show-icon
                :closable="false"
                class="mb-4"
              />

              <button
                type="button"
                class="w-full py-3 rounded-xl text-white font-bold text-sm transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed hover:scale-[1.02] active:scale-[0.98]"
                style="background: linear-gradient(135deg, #0D2D6B 0%, #16468E 100%); box-shadow: 4px 4px 10px rgba(163,177,198,0.5), -4px -4px 10px rgba(255,255,255,0.8);"
                :disabled="buscando"
                @click="buscarClinica"
              >
                <span v-if="buscando" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                <component v-else :is="SearchIcon" class="w-4 h-4" />
                {{ buscando ? 'Buscando...' : 'Continuar' }}
              </button>

              <div class="flex items-center justify-center gap-1.5 mt-3">
                <component :is="ShieldCheckIcon" class="w-3 h-3 text-gray-400" />
                <span class="text-[10px] text-gray-400 font-medium">Acceso seguro y protegido</span>
              </div>
            </el-form>
          </div>

          <!-- Paso 2: Elegir método de acceso -->
          <div v-if="paso === 2" class="relative z-10">
            <div class="flex items-center gap-2 mb-4">
              <div class="w-6 h-6 rounded-xl flex items-center justify-center text-[10px] font-bold text-[#0D2D6B]" style="background: #eef1f6; box-shadow: 3px 3px 6px rgba(163,177,198,0.6), -3px -3px 6px rgba(255,255,255,0.9);">2</div>
              <p class="text-xs font-bold text-[#0D2D6B] uppercase tracking-widest">Método de acceso</p>
              <div class="flex-1 h-px" style="background: linear-gradient(90deg, #c5cfdb, transparent);" />
            </div>

            <div class="rounded-xl p-4 mb-5" style="background: #eef1f6; box-shadow: inset 3px 3px 6px rgba(163,177,198,0.4), inset -3px -3px 6px rgba(255,255,255,0.8);">
              <p class="text-sm font-semibold text-[#0D2D6B] flex items-center gap-1.5">
                <component :is="BuildingIcon" class="w-4 h-4" />
                {{ clinicaNombre }}
              </p>
              <p class="text-xs text-[#16468E] mt-0.5">NIT: {{ nit }}</p>
            </div>

            <p class="text-sm text-gray-500 mb-4 text-center">
              ¿Cómo desea recibir su código de acceso?
            </p>

            <div class="space-y-3">
              <!-- Opción: Correo electrónico -->
              <button
                class="w-full flex items-center gap-4 p-4 rounded-xl transition-all hover:scale-[1.02] active:scale-[0.98]"
                style="background: #eef1f6; box-shadow: 4px 4px 10px rgba(163,177,198,0.5), -4px -4px 10px rgba(255,255,255,0.8);"
                :disabled="enviando"
                @click="seleccionarMetodo('email')"
              >
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                  style="background: linear-gradient(135deg, #16468E 0%, #0D2D6B 100%); box-shadow: 2px 2px 6px rgba(7,26,66,0.3);">
                  <component :is="MailIcon" class="w-5 h-5 text-white" />
                </div>
                <div class="text-left">
                  <p class="text-sm font-semibold text-gray-800">Correo electrónico</p>
                  <p class="text-xs text-gray-500">Recibirá un enlace mágico en su correo</p>
                </div>
                <component :is="ChevronRightIcon" class="w-4 h-4 text-gray-400 ml-auto" />
              </button>

              <!-- Opción: SMS -->
              <button
                class="w-full flex items-center gap-4 p-4 rounded-xl transition-all hover:scale-[1.02] active:scale-[0.98]"
                style="background: #eef1f6; box-shadow: 4px 4px 10px rgba(163,177,198,0.5), -4px -4px 10px rgba(255,255,255,0.8);"
                :disabled="enviando"
                @click="seleccionarMetodo('sms')"
              >
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                  style="background: linear-gradient(135deg, #16468E 0%, #0D2D6B 100%); box-shadow: 2px 2px 6px rgba(7,26,66,0.3);">
                  <component :is="SmartphoneIcon" class="w-5 h-5 text-white" />
                </div>
                <div class="text-left">
                  <p class="text-sm font-semibold text-gray-800">Mensaje de texto (SMS)</p>
                  <p class="text-xs text-gray-500">Recibirá un código de 6 dígitos</p>
                </div>
                <component :is="ChevronRightIcon" class="w-4 h-4 text-gray-400 ml-auto" />
              </button>
            </div>

            <el-alert
              v-if="error"
              :title="error"
              type="error"
              show-icon
              :closable="false"
              class="mt-4"
            />

            <div class="mt-5 flex items-center justify-between">
              <button
                class="inline-flex items-center text-sm text-gray-500 hover:text-[#0D2D6B] transition-colors font-medium"
                @click="volverPaso1"
              >
                <component :is="ArrowLeftIcon" class="w-4 h-4 mr-1" />
                Volver
              </button>
            </div>
          </div>

          <!-- Paso 3: Correo enviado -->
          <div v-if="paso === 3" class="text-center py-4 relative z-10">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4"
              style="background: linear-gradient(135deg, #d1fae5 0%, #6ee7b7 100%); box-shadow: 0 8px 24px rgba(34,197,94,0.25);">
              <component :is="MailCheckIcon" class="w-8 h-8 text-green-600" />
            </div>
            <h2 class="text-lg font-bold text-gray-800 mb-2">¡Revise su correo!</h2>
            <p class="text-sm text-gray-500 mb-4">
              Enviamos un enlace de acceso a la dirección de correo registrada para esta clínica.
              El enlace es válido por <strong class="text-gray-700">15 minutos</strong>.
            </p>
            <p class="text-xs text-gray-400 mb-6">
              Si no lo encuentra, revise la carpeta de spam.
            </p>
            <button
              class="text-sm text-[#0D2D6B] hover:text-[#16468E] font-semibold transition-colors"
              @click="volverPaso1"
            >
              Intentar con otro NIT
            </button>
          </div>

          <!-- Enlace a registro -->
          <div v-if="paso === 1" class="mt-6 text-center pt-5 relative z-10" style="border-top: 1px solid #d4deea;">
            <p class="text-sm text-gray-500">¿Su clínica aún no está registrada?</p>
            <router-link
              :to="{ name: 'registro-clinica' }"
              class="inline-flex items-center gap-1 text-sm text-[#0D2D6B] hover:text-[#16468E] mt-1 font-semibold transition-colors"
            >
              <component :is="PlusCircleIcon" class="w-4 h-4" />
              Solicitar registro de clínica
            </router-link>
          </div>

          <p class="text-xs text-center text-gray-400 mt-5 mb-1 flex items-center justify-center gap-1 relative z-10">
            <component :is="LockIcon" class="w-3 h-3" />
            © {{ new Date().getFullYear() }} Santa Bárbara — Uso exclusivo clínicas externas
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import {
  Building2 as BuildingIcon,
  Search as SearchIcon,
  Mail as MailIcon,
  Smartphone as SmartphoneIcon,
  ChevronRight as ChevronRightIcon,
  ArrowLeft as ArrowLeftIcon,
  MailCheck as MailCheckIcon,
  PlusCircle as PlusCircleIcon,
  ShieldCheck as ShieldCheckIcon,
  Lock as LockIcon,
} from '@lucide/vue';
import { useClinicaAuthStore } from '@/stores/clinicaAuth';

const router = useRouter();
const clinicaAuthStore = useClinicaAuthStore();

const logoBlanco = '/images/logo-w.png';

const paso = ref<1 | 2 | 3>(1);
const nit = ref('');
const metodo = ref<'email' | 'sms' | null>(null);
const clinicaNombre = ref('');
const error = ref('');
const buscando = ref(false);
const enviando = ref(false);

async function buscarClinica() {
  if (!nit.value.trim()) {
    error.value = 'Por favor ingrese el NIT o cédula de la clínica';
    return;
  }

  try {
    buscando.value = true;
    error.value = '';
    const resultado = await clinicaAuthStore.buscarClinica(nit.value.trim());
    clinicaNombre.value = resultado.nombre;
    paso.value = 2;
  } catch (e: any) {
    error.value = e?.response?.data?.message ?? 'Clínica no encontrada o no activa';
  } finally {
    buscando.value = false;
  }
}

async function seleccionarMetodo(m: 'email' | 'sms') {
  metodo.value = m;

  try {
    enviando.value = true;
    error.value = '';
    await clinicaAuthStore.solicitarAcceso(nit.value.trim(), m);

    if (m === 'email') {
      paso.value = 3;
    } else {
      router.push({ name: 'login-externo-otp', query: { nit: nit.value.trim() } });
    }
  } catch (e: any) {
    error.value = e?.response?.data?.message ?? 'Error al enviar el código. Intente de nuevo.';
  } finally {
    enviando.value = false;
  }
}

function volverPaso1() {
  paso.value = 1;
  metodo.value = null;
  error.value = '';
  clinicaNombre.value = '';
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

.form-item-custom :deep(.el-input__wrapper) {
  border-radius: 12px;
  background: #eef1f6 !important;
  box-shadow: inset 3px 3px 6px rgba(163, 177, 198, 0.5), inset -3px -3px 6px rgba(255, 255, 255, 0.8);
  transition: all 0.2s ease;
}

.form-item-custom :deep(.el-input__wrapper:hover) {
  box-shadow: inset 3px 3px 6px rgba(163, 177, 198, 0.6), inset -3px -3px 6px rgba(255, 255, 255, 0.9);
}

.form-item-custom :deep(.el-input__wrapper.is-focus) {
  box-shadow: inset 4px 4px 8px rgba(163, 177, 198, 0.6), inset -4px -4px 8px rgba(255, 255, 255, 0.95), 0 0 0 1.5px rgba(13, 45, 107, 0.15);
}

.form-item-custom :deep(.el-input__prefix-inner) {
  color: #16468E;
}

.form-item-custom :deep(.el-input__inner) {
  background: transparent !important;
}

.form-item-custom :deep(.el-form-item__label)::before {
  display: none !important;
  content: '' !important;
}
</style>
