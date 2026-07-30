<template>
  <div class="magic-page min-h-screen flex items-center justify-center px-4 py-8"
    style="background: radial-gradient(ellipse at 60% 0%, #16468E 0%, #0D2D6B 55%, #071a42 100%);">

    <!-- Glow decorativos de fondo -->
    <div class="fixed top-0 left-0 w-96 h-96 rounded-full opacity-5 pointer-events-none"
      style="background: radial-gradient(circle, #fff, transparent)" />
    <div class="fixed bottom-0 right-0 w-80 h-80 rounded-full opacity-5 pointer-events-none"
      style="background: radial-gradient(circle, #fff, transparent)" />
    <div class="fixed top-1/2 left-1/2 w-64 h-64 rounded-full opacity-3 pointer-events-none"
      style="background: radial-gradient(circle, #60a5fa, transparent)" />

    <div class="w-full max-w-md relative z-10">

      <!-- Card -->
      <div class="magic-card rounded-3xl overflow-hidden"
        style="box-shadow: 0 32px 64px rgba(0,0,0,0.4), 0 0 0 1px rgba(255,255,255,0.06);">

        <!-- Header con gradiente azul -->
        <div class="magic-header relative px-6 pt-7 pb-6 flex flex-col items-center overflow-hidden"
          style="background: linear-gradient(160deg, #16468E 0%, #0D2D6B 100%);">
          <div class="absolute -top-8 -right-8 w-32 h-32 rounded-full opacity-10"
            style="background: radial-gradient(circle, #fff, transparent)" />
          <div class="absolute -bottom-4 -left-4 w-24 h-24 rounded-full opacity-10"
            style="background: radial-gradient(circle, #fff, transparent)" />
          <div class="absolute top-2 right-10 w-3 h-3 rounded-full opacity-20" style="background:#60a5fa;"></div>
          <div class="absolute bottom-3 right-20 w-2 h-2 rounded-full opacity-15" style="background:#fff;"></div>
          <img :src="logoBlanco" alt="logo" class="h-12 object-contain relative z-10 drop-shadow-lg mb-2.5" />
          <h1 class="text-lg font-extrabold text-white tracking-wide relative z-10 mb-0.5">Sistema de Referencia</h1>
          <p class="text-blue-300/70 text-xs font-medium relative z-10">Clínica CAC Santa Bárbara</p>
        </div>

        <!-- Cuerpo -->
        <div class="bg-white px-8 py-8 text-center">

          <!-- Esperando confirmación del usuario -->
          <div v-if="estado === 'esperando'">
            <div class="magic-icon-circle w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-5"
              style="background: linear-gradient(135deg, #eef2f9 0%, #e0e8f5 100%); box-shadow: 0 8px 24px rgba(13,45,107,0.12);">
              <component :is="LinkIcon" class="w-9 h-9 text-[#0D2D6B]" />
            </div>
            <h1 class="text-xl font-bold text-gray-800 mb-2">Enlace de acceso</h1>
            <p class="text-sm text-gray-500 mb-6 leading-relaxed">Haga clic en el botón para ingresar al sistema de referencia.</p>
            <button
              class="magic-btn px-7 py-3 rounded-xl text-white font-bold text-sm transition-all flex items-center justify-center gap-2 mx-auto"
              style="background: linear-gradient(135deg, #0D2D6B 0%, #16468E 100%); box-shadow: 0 6px 20px rgba(13,45,107,0.30);"
              @click="acceder"
            >
              <component :is="LogInIcon" class="w-4 h-4" />
              Acceder al sistema
            </button>
          </div>

          <!-- Cargando -->
          <div v-else-if="estado === 'cargando'">
            <div class="w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-5 animate-pulse"
              style="background: linear-gradient(135deg, #eef2f9 0%, #e0e8f5 100%); box-shadow: 0 8px 24px rgba(13,45,107,0.12);">
              <component :is="LinkIcon" class="w-9 h-9 text-[#0D2D6B]" />
            </div>
            <h1 class="text-xl font-bold text-gray-800 mb-2">Verificando enlace...</h1>
            <p class="text-sm text-gray-500">Por favor espere un momento.</p>
            <div class="mt-6 flex justify-center">
              <div class="w-9 h-9 border-4 border-blue-100 border-t-[#0D2D6B] rounded-full animate-spin" />
            </div>
          </div>

          <!-- Éxito -->
          <div v-else-if="estado === 'exito'">
            <div class="w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-5"
              style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); box-shadow: 0 8px 24px rgba(22,163,74,0.15);">
              <component :is="CheckCircleIcon" class="w-9 h-9 text-green-600" />
            </div>
            <h1 class="text-xl font-bold text-gray-800 mb-2">¡Acceso verificado!</h1>
            <p class="text-sm text-gray-500 mb-1">Bienvenido, <strong class="text-gray-700">{{ nombreClinica }}</strong></p>
            <p class="text-sm text-gray-400">Redirigiendo al sistema...</p>
            <div class="mt-5 flex justify-center">
              <div class="w-2 h-2 rounded-full bg-green-400 animate-ping" />
            </div>
          </div>

          <!-- Error -->
          <div v-else-if="estado === 'error'">
            <div class="w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-5"
              style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); box-shadow: 0 8px 24px rgba(220,38,38,0.12);">
              <component :is="XCircleIcon" class="w-9 h-9 text-red-500" />
            </div>
            <h1 class="text-xl font-bold text-gray-800 mb-2">Enlace no válido</h1>
            <p class="text-sm text-gray-500 mb-6">{{ mensajeError }}</p>
            <router-link :to="{ name: 'login-externo' }">
              <button
                class="px-7 py-3 rounded-xl text-white font-bold text-sm transition-all flex items-center justify-center gap-2 mx-auto hover:opacity-90"
                style="background: linear-gradient(135deg, #0D2D6B 0%, #16468E 100%); box-shadow: 0 6px 20px rgba(13,45,107,0.30);"
              >
                <component :is="ArrowLeftIcon" class="w-4 h-4" />
                Volver al inicio
              </button>
            </router-link>
          </div>

          <p class="text-xs text-gray-300 mt-8 flex items-center justify-center gap-1">
            <span>© {{ new Date().getFullYear() }}</span>
            <span class="w-1 h-1 rounded-full bg-gray-400"></span>
            <span>Santa Bárbara</span>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.magic-card {
  transition: transform .4s cubic-bezier(.22,1,.36,1);
}
.magic-card:hover {
  transform: translateY(-4px);
}
.magic-btn {
  position: relative;
  overflow: hidden;
}
.magic-btn::after {
  content: '';
  position: absolute;
  top: 0; left: -100%;
  width: 100%; height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
  transition: left .6s ease;
}
.magic-btn:hover::after {
  left: 100%;
}
.magic-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 28px rgba(13,45,107,0.40) !important;
}
.magic-icon-circle {
  transition: transform .3s ease;
}
.magic-card:hover .magic-icon-circle {
  transform: scale(1.08);
}
</style>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import {
  Link as LinkIcon,
  CheckCircle as CheckCircleIcon,
  XCircle as XCircleIcon,
  ArrowLeft as ArrowLeftIcon,
  LogIn as LogInIcon,
} from '@lucide/vue';
import { useClinicaAuthStore } from '@/stores/clinicaAuth';

const router = useRouter();
const route = useRoute();
const clinicaAuthStore = useClinicaAuthStore();

const logoBlanco = '/images/logo-w.png';

const estado = ref<'esperando' | 'cargando' | 'exito' | 'error'>('esperando');
const nombreClinica = ref('');
const mensajeError = ref('El enlace ha expirado o ya fue utilizado. Solicite uno nuevo.');
const procesando = ref(false);

onMounted(() => {
  const token = route.params.token as string;
  if (!token) {
    estado.value = 'error';
  }
});

async function acceder() {
  const token = route.params.token as string;
  if (!token || procesando.value) {
    estado.value = 'error';
    return;
  }

  procesando.value = true;
  estado.value = 'cargando';
  try {
    const resultado = await clinicaAuthStore.verificarMagicLink(token);
    nombreClinica.value = resultado.nombre;
    estado.value = 'exito';
    setTimeout(() => {
      router.push({ name: 'clinica-dashboard' });
    }, 1500);
  } catch (e: any) {
    estado.value = 'error';
    procesando.value = false;
    mensajeError.value =
      e?.response?.data?.message ?? 'El enlace ha expirado o ya fue utilizado. Solicite uno nuevo.';
  }
}
</script>
