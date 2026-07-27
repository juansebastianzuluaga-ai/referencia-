<template>
  <div class="min-h-screen flex items-center justify-center px-4 py-8"
    style="background: radial-gradient(ellipse at 60% 0%, #16468E 0%, #0D2D6B 55%, #071a42 100%);">

    <!-- Glow decorativos de fondo -->
    <div class="fixed top-0 left-0 w-96 h-96 rounded-full opacity-5 pointer-events-none"
      style="background: radial-gradient(circle, #fff, transparent)" />
    <div class="fixed bottom-0 right-0 w-80 h-80 rounded-full opacity-5 pointer-events-none"
      style="background: radial-gradient(circle, #fff, transparent)" />

    <div class="w-full max-w-md relative z-10">

      <!-- Card -->
      <div class="rounded-2xl overflow-hidden"
        style="box-shadow: 0 32px 64px rgba(0,0,0,0.4), 0 0 0 1px rgba(255,255,255,0.06);">

        <!-- Header con gradiente azul -->
        <div class="relative px-6 pt-6 pb-5 flex flex-col items-center overflow-hidden"
          style="background: linear-gradient(160deg, #16468E 0%, #0D2D6B 100%);">
          <div class="absolute -top-8 -right-8 w-32 h-32 rounded-full opacity-10"
            style="background: radial-gradient(circle, #fff, transparent)" />
          <div class="absolute -bottom-4 -left-4 w-24 h-24 rounded-full opacity-10"
            style="background: radial-gradient(circle, #fff, transparent)" />
          <img :src="logoBlanco" alt="logo" class="h-10 object-contain relative z-10 drop-shadow-lg mb-2" />
        </div>

        <!-- Cuerpo -->
        <div class="bg-white px-8 py-8 text-center">

          <!-- Esperando confirmación del usuario -->
          <div v-if="estado === 'esperando'">
            <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4"
              style="background: linear-gradient(135deg, #eef2f9 0%, #e0e8f5 100%);">
              <component :is="LinkIcon" class="w-8 h-8 text-[#0D2D6B]" />
            </div>
            <h1 class="text-xl font-bold text-gray-800 mb-2">Enlace de acceso</h1>
            <p class="text-sm text-gray-500 mb-6">Haga clic en el botón para ingresar al sistema de referencia.</p>
            <button
              class="px-6 py-2.5 rounded-xl text-white font-bold text-sm transition-all flex items-center justify-center gap-2 mx-auto hover:opacity-90"
              style="background: linear-gradient(135deg, #0D2D6B 0%, #16468E 100%); box-shadow: 0 4px 16px rgba(13,45,107,0.25);"
              @click="acceder"
            >
              <component :is="LogInIcon" class="w-4 h-4" />
              Acceder al sistema
            </button>
          </div>

          <!-- Cargando -->
          <div v-else-if="estado === 'cargando'">
            <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 animate-pulse"
              style="background: linear-gradient(135deg, #eef2f9 0%, #e0e8f5 100%);">
              <component :is="LinkIcon" class="w-8 h-8 text-[#0D2D6B]" />
            </div>
            <h1 class="text-xl font-bold text-gray-800 mb-2">Verificando enlace...</h1>
            <p class="text-sm text-gray-500">Por favor espere un momento.</p>
            <div class="mt-6 flex justify-center">
              <div class="w-8 h-8 border-4 border-blue-100 border-t-[#0D2D6B] rounded-full animate-spin" />
            </div>
          </div>

          <!-- Éxito -->
          <div v-else-if="estado === 'exito'">
            <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4"
              style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);">
              <component :is="CheckCircleIcon" class="w-8 h-8 text-green-600" />
            </div>
            <h1 class="text-xl font-bold text-gray-800 mb-2">¡Acceso verificado!</h1>
            <p class="text-sm text-gray-500 mb-1">Bienvenido, <strong class="text-gray-700">{{ nombreClinica }}</strong></p>
            <p class="text-sm text-gray-400">Redirigiendo al sistema...</p>
          </div>

          <!-- Error -->
          <div v-else-if="estado === 'error'">
            <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4"
              style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);">
              <component :is="XCircleIcon" class="w-8 h-8 text-red-500" />
            </div>
            <h1 class="text-xl font-bold text-gray-800 mb-2">Enlace no válido</h1>
            <p class="text-sm text-gray-500 mb-6">{{ mensajeError }}</p>
            <router-link :to="{ name: 'login-externo' }">
              <button
                class="px-6 py-2.5 rounded-xl text-white font-bold text-sm transition-all flex items-center justify-center gap-2 mx-auto"
                style="background: linear-gradient(135deg, #0D2D6B 0%, #16468E 100%); box-shadow: 0 4px 16px rgba(13,45,107,0.25);"
              >
                <component :is="ArrowLeftIcon" class="w-4 h-4" />
                Volver al inicio
              </button>
            </router-link>
          </div>

          <p class="text-xs text-gray-300 mt-8">
            © {{ new Date().getFullYear() }} Santa Bárbara
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

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

onMounted(() => {
  const token = route.params.token as string;
  if (!token) {
    estado.value = 'error';
  }
});

async function acceder() {
  const token = route.params.token as string;
  if (!token) {
    estado.value = 'error';
    return;
  }

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
    mensajeError.value =
      e?.response?.data?.message ?? 'El enlace ha expirado o ya fue utilizado. Solicite uno nuevo.';
  }
}
</script>
