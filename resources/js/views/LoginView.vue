<template>
  <div class="h-screen flex items-center justify-center px-4" style="background: radial-gradient(ellipse at 60% 0%, #16468E 0%, #0D2D6B 55%, #071a42 100%);">
    <div class="w-full max-w-sm">

      <!-- Card -->
      <div class="rounded-3xl overflow-hidden" style="box-shadow: 0 32px 64px rgba(0,0,0,0.4), 0 0 0 1px rgba(255,255,255,0.06);">

        <!-- Header azul -->
        <div class="relative px-6 pt-7 pb-6 flex flex-col items-center text-center overflow-hidden"
          style="background: linear-gradient(160deg, #16468E 0%, #0D2D6B 100%);">
          <!-- Círculos decorativos -->
          <div class="absolute -top-6 -right-6 w-28 h-28 rounded-full opacity-10" style="background: radial-gradient(circle, #fff, transparent)" />
          <div class="absolute -bottom-4 -left-4 w-20 h-20 rounded-full opacity-10" style="background: radial-gradient(circle, #fff, transparent)" />

          <img :src="logoBlanco" alt="logo" class="h-11 object-contain mb-3 relative z-10 drop-shadow-lg" />
          <h1 class="text-xl font-extrabold text-white tracking-wide relative z-10">Sistema de Referencia</h1>
          <div class="flex items-center gap-2 mt-3 relative z-10">
            <div class="w-6 h-px bg-blue-300/40" />
            <div class="w-1.5 h-1.5 rounded-full bg-blue-300/60" />
            <div class="w-6 h-px bg-blue-300/40" />
          </div>
        </div>

        <!-- Cuerpo blanco -->
        <div class="bg-white px-6 pt-5 pb-7">

          <!-- Tabs -->
          <div class="flex mb-5 bg-gray-50 rounded-2xl p-1 gap-1">
            <button
              class="flex-1 py-2 text-xs font-bold rounded-xl transition-all duration-200"
              :class="tab === 'clinica'
                ? 'bg-white text-[#0D2D6B] shadow-sm ring-1 ring-gray-200'
                : 'text-gray-400 hover:text-gray-600'"
              @click="cambiarTab('clinica')"
            >
              🏥 Clínica externa
            </button>
            <button
              class="flex-1 py-2 text-xs font-bold rounded-xl transition-all duration-200"
              :class="tab === 'interno'
                ? 'bg-white text-[#0D2D6B] shadow-sm ring-1 ring-gray-200'
                : 'text-gray-400 hover:text-gray-600'"
              @click="cambiarTab('interno')"
            >
              🔐 Personal interno
            </button>
          </div>

          <!-- ── TAB: Clínica externa ── -->
          <div v-if="tab === 'clinica'">

            <!-- Paso 1: NIT -->
            <div v-if="pasoClinoca === 1">
              <p class="text-xs text-gray-400 mb-3 text-center">
                Ingrese el <strong>NIT</strong> o <strong>cédula</strong> de su institución
              </p>
              <div class="mb-3">
                <div class="flex items-center gap-3 border-2 border-gray-200 rounded-xl px-3 py-2.5 focus-within:border-[#0D2D6B] transition-colors bg-blue-50/40">
                  <component :is="BuildingIcon" class="w-5 h-5 text-gray-400 flex-shrink-0" />
                  <input
                    v-model="nit"
                    type="text"
                    placeholder="Ej: 900123456-7"
                    autocomplete="off"
                    class="flex-1 bg-transparent outline-none text-gray-800 text-sm placeholder:text-gray-400"
                    :disabled="buscando"
                    @keyup.enter="buscarClinica"
                  />
                </div>
              </div>
              <p v-if="errorClinica" class="text-xs text-red-500 mb-3 text-center">{{ errorClinica }}</p>
              <button
                class="w-full py-2.5 rounded-xl bg-[#0D2D6B] text-white font-semibold text-sm hover:bg-[#16468E] transition-colors disabled:opacity-60 flex items-center justify-center gap-2"
                :disabled="buscando"
                @click="buscarClinica"
              >
                <span v-if="buscando" class="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin" />
                Continuar
              </button>
              <div class="mt-4 text-center border-t border-gray-100 pt-3">
                <p class="text-xs text-gray-400 mb-1">¿Su institución no está registrada?</p>
                <router-link :to="{ name: 'registro-clinica' }" class="text-xs font-semibold text-[#0D2D6B] hover:underline">
                  Solicitar registro de clínica →
                </router-link>
              </div>
            </div>

            <!-- Paso 2: Elegir método -->
            <div v-if="pasoClinoca === 2">
              <div class="bg-blue-50 border border-blue-100 rounded-2xl p-3 mb-5 flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-[#0D2D6B] flex items-center justify-center flex-shrink-0">
                  <component :is="BuildingIcon" class="w-4 h-4 text-white" />
                </div>
                <div>
                  <p class="text-sm font-semibold text-[#0D2D6B]">{{ clinicaNombre }}</p>
                  <p class="text-xs text-blue-400">NIT: {{ nit }}</p>
                </div>
              </div>
              <p class="text-sm text-gray-500 mb-4 text-center">¿Cómo desea recibir su código?</p>
              <div class="space-y-3">
                <button
                  class="w-full flex items-center gap-3 p-3.5 border-2 rounded-2xl transition-all hover:border-[#0D2D6B] hover:bg-blue-50/50"
                  :class="metodo === 'email' ? 'border-[#0D2D6B] bg-blue-50/50' : 'border-gray-200'"
                  :disabled="enviando"
                  @click="seleccionarMetodo('email')"
                >
                  <div class="w-9 h-9 rounded-xl bg-blue-100 flex items-center justify-center flex-shrink-0">
                    <component :is="MailIcon" class="w-4 h-4 text-[#0D2D6B]" />
                  </div>
                  <div class="text-left">
                    <p class="text-sm font-semibold text-gray-800">Correo electrónico</p>
                    <p class="text-xs text-gray-400">Enlace mágico a su correo</p>
                  </div>
                  <component :is="ChevronRightIcon" class="w-4 h-4 text-gray-300 ml-auto" />
                </button>
                <button
                  class="w-full flex items-center gap-3 p-3.5 border-2 rounded-2xl transition-all hover:border-[#0D2D6B] hover:bg-blue-50/50"
                  :class="metodo === 'sms' ? 'border-[#0D2D6B] bg-blue-50/50' : 'border-gray-200'"
                  :disabled="enviando"
                  @click="seleccionarMetodo('sms')"
                >
                  <div class="w-9 h-9 rounded-xl bg-green-100 flex items-center justify-center flex-shrink-0">
                    <component :is="SmartphoneIcon" class="w-4 h-4 text-green-600" />
                  </div>
                  <div class="text-left">
                    <p class="text-sm font-semibold text-gray-800">Mensaje de texto (SMS)</p>
                    <p class="text-xs text-gray-400">Código de 6 dígitos</p>
                  </div>
                  <component :is="ChevronRightIcon" class="w-4 h-4 text-gray-300 ml-auto" />
                </button>
              </div>
              <p v-if="errorClinica" class="text-xs text-red-500 mt-3 text-center">{{ errorClinica }}</p>
              <button class="mt-4 text-xs text-gray-400 hover:text-gray-600 flex items-center gap-1" @click="pasoClinoca = 1; errorClinica = ''">
                <component :is="ArrowLeftIcon" class="w-3 h-3" /> Volver
              </button>
            </div>

            <!-- Paso 3: Correo enviado -->
            <div v-if="pasoClinoca === 3" class="text-center py-2">
              <div class="w-14 h-14 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-3">
                <component :is="MailCheckIcon" class="w-7 h-7 text-green-600" />
              </div>
              <h2 class="text-base font-bold text-gray-800 mb-1">¡Revise su correo!</h2>
              <p class="text-sm text-gray-400 mb-5">Le enviamos un enlace de acceso válido por <strong>15 minutos</strong>.</p>
              <button class="text-xs text-[#0D2D6B] hover:underline" @click="pasoClinoca = 1; nit = ''; errorClinica = ''">
                Intentar con otro NIT
              </button>
            </div>

          </div>

          <!-- ── TAB: Personal interno ── -->
          <div v-if="tab === 'interno'">
            <div class="mb-3">
              <div class="flex items-center gap-3 border-2 border-gray-200 rounded-xl px-3 py-2.5 focus-within:border-[#0D2D6B] transition-colors bg-blue-50/40">
                <component :is="UserIcon" class="w-4 h-4 text-gray-400 flex-shrink-0" />
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
              <div class="flex items-center gap-3 border-2 border-gray-200 rounded-xl px-3 py-2.5 focus-within:border-[#0D2D6B] transition-colors bg-blue-50/40">
                <component :is="LockIcon" class="w-4 h-4 text-gray-400 flex-shrink-0" />
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
              class="w-full py-2.5 rounded-xl bg-[#0D2D6B] text-white font-semibold text-sm hover:bg-[#16468E] transition-colors disabled:opacity-60 flex items-center justify-center gap-2"
              :disabled="loadingInterno"
              @click="handleLogin"
            >
              <span v-if="loadingInterno" class="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin" />
              Ingresar
            </button>
          </div>

        </div><!-- /cuerpo blanco -->
      </div>

      <p class="text-center text-xs text-white/30 mt-5 tracking-wide">
        © {{ new Date().getFullYear() }} · Clínica Santa Bárbara · Todos los derechos reservados
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';
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
} from '@lucide/vue';
import { useAuthStore } from '@/stores/auth';
import { useClinicaAuthStore } from '@/stores/clinicaAuth';

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
const metodo = ref<'email' | 'sms' | null>(null);
const clinicaNombre = ref('');
const errorClinica = ref('');
const buscando = ref(false);
const enviando = ref(false);

async function buscarClinica() {
  if (!nit.value.trim()) { errorClinica.value = 'Ingrese el NIT o cédula'; return; }
  try {
    buscando.value = true;
    errorClinica.value = '';
    const resultado = await clinicaAuthStore.buscarClinica(nit.value.trim());
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
