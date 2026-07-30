<template>
  <div class="h-screen flex items-center justify-center px-4" style="background: radial-gradient(ellipse at 60% 0%, #16468E 0%, #0D2D6B 55%, #071a42 100%);">

    <!-- Glow decorativos de fondo -->
    <div class="fixed top-0 left-1/4 w-[500px] h-[500px] rounded-full opacity-[0.07] pointer-events-none"
      style="background: radial-gradient(circle, #3b82f6, transparent 70%)" />
    <div class="fixed bottom-0 right-1/4 w-[400px] h-[400px] rounded-full opacity-[0.05] pointer-events-none"
      style="background: radial-gradient(circle, #60a5fa, transparent 70%)" />

    <div class="w-full max-w-sm relative z-10"
      v-motion
      :initial="{ opacity: 0, y: 30, scale: 0.96 }"
      :enter="{ opacity: 1, y: 0, scale: 1, transition: { duration: 600, ease: 'easeOut' } }">

      <!-- Card -->
      <div class="rounded-3xl overflow-hidden card-enter" style="box-shadow: 12px 12px 24px rgba(7,26,66,0.4), -12px -12px 24px rgba(255,255,255,0.15), 0 0 60px rgba(255,255,255,0.12);">

        <!-- Header azul -->
        <div class="relative px-6 pt-6 pb-5 flex flex-col items-center text-center overflow-hidden"
          style="background: linear-gradient(135deg, #16468E 0%, #0D2D6B 50%, #0a2150 100%);">
          <!-- Círculos decorativos -->
          <div class="absolute -top-8 -right-8 w-36 h-36 rounded-full opacity-[0.12]" style="background: radial-gradient(circle, #60a5fa, transparent 70%)" />
          <div class="absolute -bottom-6 -left-6 w-24 h-24 rounded-full opacity-[0.08]" style="background: radial-gradient(circle, #93c5fd, transparent 70%)" />
          <div class="absolute inset-0 opacity-[0.03]" style="background-image: repeating-linear-gradient(45deg, #fff 0, #fff 1px, transparent 1px, transparent 12px)" />

          <img :src="logoBlanco" alt="logo" class="h-11 object-contain mb-3 relative z-10 drop-shadow-lg" />
          <h1 class="text-xl font-extrabold text-white tracking-wide relative z-10">Sistema de Referencia</h1>
          <div class="flex items-center gap-2 mt-3 relative z-10">
            <div class="w-6 h-px bg-blue-300/40" />
            <div class="w-1.5 h-1.5 rounded-full bg-blue-300/60" />
            <div class="w-6 h-px bg-blue-300/40" />
          </div>
        </div>

        <!-- Cuerpo -->
        <div class="px-6 pt-5 pb-6 relative" style="background: #eef1f6;">
          <div class="absolute inset-0 opacity-[0.02] pointer-events-none" style="background-image: radial-gradient(circle, #0D2D6B 1px, transparent 1px); background-size: 16px 16px;" />

          <!-- Tabs -->
          <div class="flex mb-5 rounded-2xl p-1 gap-1 relative z-10" style="background: #eef1f6; box-shadow: inset 3px 3px 6px rgba(163,177,198,0.4), inset -3px -3px 6px rgba(255,255,255,0.8);">
            <button
              class="flex-1 py-2 text-xs font-bold rounded-xl transition-all duration-200"
              :class="tab === 'clinica'
                ? 'text-[#0D2D6B]'
                : 'text-gray-400 hover:text-gray-600'"
              :style="tab === 'clinica' ? 'background: #eef1f6; box-shadow: 3px 3px 6px rgba(163,177,198,0.5), -3px -3px 6px rgba(255,255,255,0.9);' : ''"
              @click="cambiarTab('clinica')"
            >
              🏥 Clínica externa
            </button>
            <button
              class="flex-1 py-2 text-xs font-bold rounded-xl transition-all duration-200"
              :class="tab === 'interno'
                ? 'text-[#0D2D6B]'
                : 'text-gray-400 hover:text-gray-600'"
              :style="tab === 'interno' ? 'background: #eef1f6; box-shadow: 3px 3px 6px rgba(163,177,198,0.5), -3px -3px 6px rgba(255,255,255,0.9);' : ''"
              @click="cambiarTab('interno')"
            >
              🔐 Personal interno
            </button>
          </div>

          <!-- ── TAB: Clínica externa ── -->
          <div v-if="tab === 'clinica'" class="relative z-10">

            <!-- Paso 1: NIT -->
            <div v-if="pasoClinoca === 1">
              <p class="text-xs text-gray-500 mb-3 text-center">
                Ingrese el <strong>NIT</strong> o <strong>cédula</strong> de su institución
              </p>
              <div class="mb-3">
                <div class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition-all neu-input">
                  <component :is="BuildingIcon" class="w-5 h-5 text-[#16468E] flex-shrink-0" />
                  <input
                    v-model="nitModel"
                    type="text"
                    placeholder="Ej: 900.123.456-7"
                    autocomplete="off"
                    class="flex-1 bg-transparent outline-none text-gray-800 text-sm placeholder:text-gray-400"
                    :disabled="buscando"
                    @keyup.enter="buscarClinica"
                  />
                  <component
                    v-if="nit.length > 0"
                    :is="nitValido ? CheckCircleIcon : XCircleIcon"
                    class="w-5 h-5 flex-shrink-0"
                    :class="nitValido ? 'text-green-500' : 'text-red-400'"
                  />
                </div>
                <p v-if="nit.length > 0 && !nitValido" class="text-[10px] text-red-400 mt-1 ml-1">
                  Dígito de verificación incorrecto
                </p>
              </div>
              <p v-if="errorClinica" class="text-xs text-red-500 mb-3 text-center">{{ errorClinica }}</p>
              <button
                class="w-full py-2.5 rounded-xl text-white font-semibold text-sm transition-all hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-2"
                style="background: linear-gradient(135deg, #0D2D6B 0%, #16468E 100%); box-shadow: 4px 4px 10px rgba(163,177,198,0.5), -4px -4px 10px rgba(255,255,255,0.8);"
                :disabled="buscando || !nitValido"
                @click="buscarClinica"
              >
                <span v-if="buscando" class="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin" />
                Continuar
              </button>
              <div class="flex items-center justify-center gap-1.5 mt-3">
                <component :is="ShieldCheckIcon" class="w-3 h-3 text-gray-400" />
                <span class="text-[10px] text-gray-400 font-medium">Acceso seguro y protegido</span>
              </div>
              <div class="mt-4 text-center pt-3" style="border-top: 1px solid #d4deea;">
                <p class="text-xs text-gray-400 mb-1">¿Su institución no está registrada?</p>
                <router-link :to="{ name: 'registro-clinica' }" class="text-xs font-semibold text-[#0D2D6B] hover:underline">
                  Solicitar registro de clínica →
                </router-link>
              </div>
            </div>

            <!-- Paso 2: Elegir método -->
            <div v-if="pasoClinoca === 2">
              <div class="rounded-xl p-3 mb-5 flex items-center gap-3" style="background: #eef1f6; box-shadow: inset 3px 3px 6px rgba(163,177,198,0.4), inset -3px -3px 6px rgba(255,255,255,0.8);">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0" style="background: linear-gradient(135deg, #16468E 0%, #0D2D6B 100%); box-shadow: 2px 2px 6px rgba(7,26,66,0.3);">
                  <component :is="BuildingIcon" class="w-4 h-4 text-white" />
                </div>
                <div>
                  <p class="text-sm font-semibold text-[#0D2D6B]">{{ clinicaNombre }}</p>
                  <p class="text-xs text-[#16468E]">NIT: {{ nit }}</p>
                </div>
              </div>
              <p class="text-sm text-gray-500 mb-4 text-center">¿Cómo desea recibir su código?</p>
              <div class="space-y-3">
                <button
                  class="w-full flex items-center gap-3 p-3.5 rounded-2xl transition-all hover:scale-[1.02] active:scale-[0.98]"
                  style="background: #eef1f6; box-shadow: 4px 4px 10px rgba(163,177,198,0.5), -4px -4px 10px rgba(255,255,255,0.8);"
                  :disabled="enviando"
                  @click="seleccionarMetodo('email')"
                >
                  <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background: linear-gradient(135deg, #16468E 0%, #0D2D6B 100%); box-shadow: 2px 2px 6px rgba(7,26,66,0.3);">
                    <component :is="MailIcon" class="w-4 h-4 text-white" />
                  </div>
                  <div class="text-left">
                    <p class="text-sm font-semibold text-gray-800">Correo electrónico</p>
                    <p class="text-xs text-gray-500">Enlace mágico a su correo</p>
                  </div>
                  <component :is="ChevronRightIcon" class="w-4 h-4 text-gray-400 ml-auto" />
                </button>
                <button
                  class="w-full flex items-center gap-3 p-3.5 rounded-2xl transition-all hover:scale-[1.02] active:scale-[0.98]"
                  style="background: #eef1f6; box-shadow: 4px 4px 10px rgba(163,177,198,0.5), -4px -4px 10px rgba(255,255,255,0.8);"
                  :disabled="enviando"
                  @click="seleccionarMetodo('sms')"
                >
                  <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background: linear-gradient(135deg, #16468E 0%, #0D2D6B 100%); box-shadow: 2px 2px 6px rgba(7,26,66,0.3);">
                    <component :is="SmartphoneIcon" class="w-4 h-4 text-white" />
                  </div>
                  <div class="text-left">
                    <p class="text-sm font-semibold text-gray-800">Mensaje de texto (SMS)</p>
                    <p class="text-xs text-gray-500">Código de 6 dígitos</p>
                  </div>
                  <component :is="ChevronRightIcon" class="w-4 h-4 text-gray-400 ml-auto" />
                </button>
              </div>
              <p v-if="errorClinica" class="text-xs text-red-500 mt-3 text-center">{{ errorClinica }}</p>
              <button class="mt-4 text-xs text-gray-500 hover:text-[#0D2D6B] flex items-center gap-1 transition-colors" @click="pasoClinoca = 1; errorClinica = ''">
                <component :is="ArrowLeftIcon" class="w-3 h-3" /> Volver
              </button>
            </div>

            <!-- Paso 3: Correo enviado -->
            <div v-if="pasoClinoca === 3" class="text-center py-2">
              <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-3" style="background: linear-gradient(135deg, #d1fae5 0%, #6ee7b7 100%); box-shadow: 0 8px 24px rgba(34,197,94,0.25);">
                <component :is="MailCheckIcon" class="w-7 h-7 text-green-600" />
              </div>
              <h2 class="text-base font-bold text-gray-800 mb-1">¡Revise su correo!</h2>
              <p class="text-sm text-gray-500 mb-5">Le enviamos un enlace de acceso válido por <strong>15 minutos</strong>.</p>
              <button class="text-xs text-[#0D2D6B] hover:underline" @click="pasoClinoca = 1; nit = ''; errorClinica = ''">
                Intentar con otro NIT
              </button>
            </div>

          </div>

          <!-- ── TAB: Personal interno ── -->
          <div v-if="tab === 'interno'" class="relative z-10">
            <div class="mb-3">
              <div class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition-all neu-input">
                <component :is="UserIcon" class="w-4 h-4 text-[#16468E] flex-shrink-0" />
                <input
                  v-model="form.username"
                  type="text"
                  placeholder="Usuario"
                  autocomplete="off"
                  class="flex-1 bg-transparent outline-none text-gray-800 text-sm placeholder:text-gray-400"
                  @keyup.enter="handleLogin"
                />
              </div>
            </div>
            <div class="mb-2">
              <div class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition-all neu-input">
                <component :is="LockIcon" class="w-4 h-4 text-[#16468E] flex-shrink-0" />
                <input
                  v-model="form.password"
                  :type="mostrarPassword ? 'text' : 'password'"
                  placeholder="Contraseña"
                  autocomplete="off"
                  class="flex-1 bg-transparent outline-none text-gray-800 text-sm placeholder:text-gray-400"
                  @keyup.enter="handleLogin"
                />
                <button type="button" class="text-gray-400 hover:text-gray-600" @click="mostrarPassword = !mostrarPassword">
                  <component :is="mostrarPassword ? EyeOffIcon : EyeIcon" class="w-4 h-4" />
                </button>
              </div>
            </div>
            <div class="flex justify-end mb-5">
              <router-link :to="{ name: 'forgot-password' }" class="text-xs text-[#0D2D6B] hover:underline">
                ¿Olvidó su contraseña?
              </router-link>
            </div>
            <p v-if="errorInterno" class="text-xs text-red-500 mb-3 text-center">{{ errorInterno }}</p>
            <button
              class="w-full py-2.5 rounded-xl text-white font-semibold text-sm transition-all hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-2"
              style="background: linear-gradient(135deg, #0D2D6B 0%, #16468E 100%); box-shadow: 4px 4px 10px rgba(163,177,198,0.5), -4px -4px 10px rgba(255,255,255,0.8);"
              :disabled="loadingInterno"
              @click="handleLogin"
            >
              <span v-if="loadingInterno" class="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin" />
              Ingresar
            </button>
          </div>

        </div><!-- /cuerpo -->
      </div>

      <p class="text-center text-xs text-white/30 mt-5 tracking-wide flex items-center justify-center gap-1">
        <component :is="LockIcon" class="w-3 h-3" />
        © {{ new Date().getFullYear() }} · Clínica Santa Bárbara · Todos los derechos reservados
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import {
  User as UserIcon,
  Lock as LockIcon,
  Building2 as BuildingIcon,
  Mail as MailIcon,
  Smartphone as SmartphoneIcon,
  ArrowLeft as ArrowLeftIcon,
  MailCheck as MailCheckIcon,
  ChevronRight as ChevronRightIcon,
  Eye as EyeIcon,
  EyeOff as EyeOffIcon,
  ShieldCheck as ShieldCheckIcon,
  CheckCircle as CheckCircleIcon,
  XCircle as XCircleIcon,
} from '@lucide/vue';
import { useAuthStore } from '@/stores/auth';
import { useClinicaAuthStore } from '@/stores/clinicaAuth';
import { normalizarNit } from '@/utils/nit';

const router = useRouter();
const auth = useAuthStore();
const clinicaAuthStore = useClinicaAuthStore();

const logoBlanco = '/images/logo-w.png';

// ── Tab ──────────────────────────────────────────────────────────────────────
const tab = ref<'clinica' | 'interno'>('clinica');

function cambiarTab(t: 'clinica' | 'interno') {
  tab.value = t;
  errorClinica.value = '';
  errorInterno.value = '';
  pasoClinoca.value = 1;
}

// ── Clínica externa ──────────────────────────────────────────────────────────
const pasoClinoca = ref<1 | 2 | 3>(1);
const nit = ref('');
const nitValido = ref(false);
const metodo = ref<'email' | 'sms' | null>(null);
const clinicaNombre = ref('');
const errorClinica = ref('');
const buscando = ref(false);
const enviando = ref(false);

const nitModel = computed({
  get: () => nit.value,
  set: (val) => {
    nit.value = val;
    nitValido.value = normalizarNit(val).length >= 5;
  },
});

async function buscarClinica() {
  if (!nit.value.trim()) { errorClinica.value = 'Ingrese el NIT o cédula'; return; }
  if (!nitValido.value) { errorClinica.value = 'Ingrese un NIT válido (mínimo 5 dígitos)'; return; }
  try {
    buscando.value = true;
    errorClinica.value = '';
    const resultado = await clinicaAuthStore.buscarClinica(normalizarNit(nit.value));
    clinicaNombre.value = resultado.nombre;
    pasoClinoca.value = 2;
  } catch (e: any) {
    errorClinica.value = e?.response?.data?.message ?? 'Institución no encontrada o no autorizada';
  } finally {
    buscando.value = false;
  }
}

async function seleccionarMetodo(m: 'email' | 'sms') {
  metodo.value = m;
  try {
    enviando.value = true;
    errorClinica.value = '';
    await clinicaAuthStore.solicitarAcceso(nit.value.trim(), m);
    if (m === 'email') {
      pasoClinoca.value = 3;
    } else {
      router.push({ name: 'login-externo-otp', query: { nit: nit.value.trim() } });
    }
  } catch (e: any) {
    errorClinica.value = e?.response?.data?.message ?? 'Error al enviar. Intente de nuevo.';
  } finally {
    enviando.value = false;
  }
}

// ── Personal interno ─────────────────────────────────────────────────────────
const form = reactive({ username: '', password: '' });
const loadingInterno = ref(false);
const errorInterno = ref('');
const mostrarPassword = ref(false);

async function handleLogin() {
  if (!form.username || !form.password) { errorInterno.value = 'Ingrese usuario y contraseña'; return; }
  try {
    loadingInterno.value = true;
    errorInterno.value = '';
    await auth.login({ username: form.username, password: form.password });
    router.push({ name: 'dashboard' });
  } catch (error: any) {
    if (axios.isAxiosError(error)) {
      const status = error.response?.status;
      if (status === 422) {
        errorInterno.value = 'Usuario o contraseña incorrectos';
      } else if (status === 429) {
        errorInterno.value = 'Demasiados intentos. Espere un momento e intente de nuevo.';
      } else {
        errorInterno.value = error.response?.data?.message || 'Error al intentar iniciar sesión';
      }
    } else {
      errorInterno.value = 'Error de conexión. Verifique que el servidor esté activo.';
    }
  } finally {
    loadingInterno.value = false;
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

.neu-input {
  background: #eef1f6;
  box-shadow: inset 3px 3px 6px rgba(163, 177, 198, 0.5), inset -3px -3px 6px rgba(255, 255, 255, 0.8);
}

.neu-input:focus-within {
  box-shadow: inset 4px 4px 8px rgba(163, 177, 198, 0.6), inset -4px -4px 8px rgba(255, 255, 255, 0.95), 0 0 0 1.5px rgba(13, 45, 107, 0.15);
}
</style>
