<template>
  <div class="space-y-6">
    <div class="bg-gradient-to-r from-[#0D2D6B] to-[#16468E] rounded-xl p-6 text-white shadow-md">
      <h1 class="text-xl font-bold mb-1">Bienvenido, {{ clinic?.business_name }}</h1>
      <p class="text-white/80 text-sm">
        NIT: {{ clinic?.nit }} | Estado: <span class="font-medium">{{ clinic?.status_label }}</span>
      </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <ContentCard title="Estado de la cuenta" class="md:col-span-2">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
            <CheckCircle class="w-6 h-6 text-green-600" />
          </div>
          <div>
            <p class="text-sm text-gray-500">Cuenta</p>
            <p class="text-lg font-semibold text-gray-800">Activa</p>
          </div>
        </div>
        <p class="text-sm text-gray-600 mt-4">
          Su cuenta está habilitada para acceder al portal de clínicas externas. Desde aquí podrá consultar sus datos y, próximamente, acceder a más servicios.
        </p>
      </ContentCard>

      <ContentCard title="Último acceso">
        <p class="text-sm text-gray-600">
          {{ clinic?.last_login_at ? formatDate(clinic.last_login_at) : 'Primer acceso' }}
        </p>
      </ContentCard>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <ContentCard title="Datos de contacto">
        <ul class="space-y-2 text-sm text-gray-700">
          <li><strong>Correo:</strong> {{ clinic?.email }}</li>
          <li><strong>Teléfono:</strong> {{ clinic?.phone }}</li>
          <li v-if="clinic?.mobile"><strong>Celular:</strong> {{ clinic.mobile }}</li>
        </ul>
      </ContentCard>

      <ContentCard title="Ubicación">
        <ul class="space-y-2 text-sm text-gray-700">
          <li>{{ clinic?.address }}</li>
          <li>{{ clinic?.city }}, {{ clinic?.department }}</li>
        </ul>
      </ContentCard>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useExternalClinicAuthStore } from '@/stores/externalClinicAuth';
import ContentCard from '@/components/ui/ContentCard.vue';
import { CheckCircle } from '@lucide/vue';

const auth = useExternalClinicAuthStore();
const clinic = computed(() => auth.clinic);

function formatDate(value: string) {
  return new Date(value).toLocaleString('es-CO', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}
</script>
