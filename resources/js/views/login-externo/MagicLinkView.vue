<template>
  <div class="h-screen flex items-center justify-center bg-gradient-to-b from-blue-50 to-white">
    <div class="w-full max-w-md mx-4">
      <div class="bg-white rounded-xl shadow-2xl overflow-hidden">
        <div class="p-8 text-center">

          <!-- Cargando -->
          <div v-if="estado === 'cargando'">
            <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center mx-auto mb-4 animate-pulse">
              <component :is="LinkIcon" class="w-8 h-8 text-blue-600" />
            </div>
            <h1 class="text-xl font-bold text-gray-800 mb-2">Verificando enlace...</h1>
            <p class="text-sm text-gray-500">Por favor espere un momento.</p>
            <div class="mt-6 flex justify-center">
              <div class="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin" />
            </div>
          </div>

          <!-- Éxito -->
          <div v-else-if="estado === 'exito'">
            <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
              <component :is="CheckCircleIcon" class="w-8 h-8 text-green-600" />
            </div>
            <h1 class="text-xl font-bold text-gray-800 mb-2">¡Acceso verificado!</h1>
            <p class="text-sm text-gray-500 mb-1">Bienvenido, <strong>{{ nombreClinica }}</strong></p>
            <p class="text-sm text-gray-400">Redirigiendo al sistema...</p>
          </div>

          <!-- Error -->
          <div v-else-if="estado === 'error'">
            <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
              <component :is="XCircleIcon" class="w-8 h-8 text-red-500" />
            </div>
            <h1 class="text-xl font-bold text-gray-800 mb-2">Enlace no válido</h1>
            <p class="text-sm text-gray-500 mb-6">{{ mensajeError }}</p>
            <router-link :to="{ name: 'login' }">
              <el-button type="primary" size="large">
                <component :is="ArrowLeftIcon" class="w-4 h-4 mr-2" />
                Volver al inicio
              </el-button>
            </router-link>
          </div>

          <p class="text-xs text-gray-400 mt-8">
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
} from '@lucide/vue';
import { useClinicaAuthStore } from '@/stores/clinicaAuth';

const router = useRouter();
const route = useRoute();
const clinicaAuthStore = useClinicaAuthStore();

const estado = ref<'cargando' | 'exito' | 'error'>('cargando');
const nombreClinica = ref('');
const mensajeError = ref('El enlace ha expirado o ya fue utilizado. Solicite uno nuevo.');

onMounted(async () => {
  const token = route.params.token as string;

  if (!token) {
    estado.value = 'error';
    return;
  }

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
});
</script>
