<template>
  <div class="space-y-5 max-w-2xl">
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">Nombre de la aplicación</label>
      <el-input v-model="form.app_name" placeholder="Nombre de la aplicación" />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">Zona horaria</label>
      <el-select v-model="form.app_timezone" placeholder="Seleccionar zona horaria" class="w-full" filterable>
        <el-option v-for="tz in timezones" :key="tz" :label="tz" :value="tz" />
      </el-select>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">Idioma</label>
      <el-select v-model="form.app_locale" placeholder="Seleccionar idioma" class="w-full">
        <el-option label="Español" value="es" />
        <el-option label="English" value="en" />
        <el-option label="Português" value="pt" />
      </el-select>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, watch } from 'vue';
import { useSettingsStore } from '@/stores/settings';

const settingsStore = useSettingsStore();

const timezones = [
  'America/Bogota',
  'America/New_York',
  'America/Chicago',
  'America/Denver',
  'America/Los_Angeles',
  'America/Mexico_City',
  'America/Buenos_Aires',
  'America/Santiago',
  'America/Lima',
  'America/Caracas',
  'Europe/Madrid',
  'UTC',
];

const form = reactive({
  app_name: '',
  app_timezone: '',
  app_locale: '',
});

function loadForm() {
  form.app_name = settingsStore.getSettingValue('general', 'app_name', '');
  form.app_timezone = settingsStore.getSettingValue('general', 'app_timezone', 'America/Bogota');
  form.app_locale = settingsStore.getSettingValue('general', 'app_locale', 'es');
}

loadForm();

watch(() => settingsStore.settings, loadForm, { deep: true });

defineExpose({ form, group: 'general' });
</script>
