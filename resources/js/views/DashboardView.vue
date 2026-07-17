<template>
  <ContentCard title="Panel Principal" subtitle="Resumen general del sistema HIS">
    <template #actions>
      <el-button type="primary" :icon="PlusIcon">
        Nuevo Ingreso
      </el-button>
    </template>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <StatCard 
        value="124" 
        label="Pacientes Activos" 
        icon="Users" 
        color="blue" 
        delta="12 hoy" 
        delta-type="up" 
      />
      <StatCard 
        value="45" 
        label="Altas Programadas" 
        icon="Activity" 
        color="green" 
        delta="5 vs ayer" 
        delta-type="up" 
      />
      <StatCard 
        value="8" 
        label="Camas Disponibles" 
        icon="BedDouble" 
        color="amber" 
        delta="2 menos" 
        delta-type="down" 
      />
      <StatCard 
        value="3" 
        label="Alertas Críticas" 
        icon="AlertTriangle" 
        color="red" 
        delta="Sin cambio" 
        delta-type="neutral" 
      />
    </div>

    <!-- Puedes agregar más secciones del dashboard aquí -->
    <div class="p-8 text-center text-gray-400 border border-dashed border-gray-300 rounded-lg">
      Gráficos y reportes rápidos irán en esta sección.
    </div>

  </ContentCard>
</template>

<script setup lang="ts">
import { Plus as PlusIcon } from '@lucide/vue';
import ContentCard from '@/components/ui/ContentCard.vue';
import StatCard from '@/components/ui/StatCard.vue';
</script>
