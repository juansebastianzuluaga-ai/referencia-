<template>
  <div class="h-screen overflow-y-auto flex items-center justify-center bg-gradient-to-b from-blue-50 to-white py-12">
    <div class="w-full max-w-lg mx-4 my-auto">
      <div class="bg-white rounded-xl shadow-2xl overflow-hidden p-8 text-center">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
          <CheckCircle class="w-10 h-10 text-green-600" />
        </div>

        <h1 class="text-2xl font-bold text-gray-800 mb-2">¡Solicitud enviada!</h1>
        <p class="text-gray-600 mb-6">
          Hemos recibido su registro. Nuestro equipo revisará los datos y le notificaremos por correo electrónico el resultado en las próximas 24-48 horas.
        </p>

        <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
          <p class="text-sm text-gray-700 mb-1"><strong>NIT:</strong> {{ nit }}</p>
          <p class="text-sm text-gray-700 mb-1"><strong>Correo:</strong> {{ email }}</p>
          <p class="text-sm text-gray-700"><strong>Teléfono:</strong> {{ phone }}</p>
        </div>

        <p class="text-sm text-gray-500 mb-6">
          Mientras tanto, puede cerrar esta página. Le enviaremos un correo cuando su cuenta sea activada.
        </p>

        <el-button type="primary" class="w-full" @click="goHome">
          Volver al inicio
        </el-button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router';
import { CheckCircle } from '@lucide/vue';

const route = useRoute();
const router = useRouter();

const nit = route.query.nit as string || '';
const email = route.query.email as string || '';
const phone = route.query.phone as string || '';

function goHome() {
  router.push({ name: 'external-clinic-login' });
}
</script>
