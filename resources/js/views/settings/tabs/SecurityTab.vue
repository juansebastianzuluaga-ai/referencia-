<template>
  <div class="space-y-5 max-w-2xl">
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">Duración de sesión (minutos)</label>
      <el-input-number v-model="form.session_lifetime" :min="1" :max="10080" controls-position="right" class="w-full" />
      <p class="text-xs text-gray-400 mt-1">Tiempo de inactividad antes de que expire la sesión del usuario.</p>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">Longitud mínima de contraseña</label>
      <el-input-number v-model="form.password_min_length" :min="4" :max="32" controls-position="right" class="w-full" />
    </div>

    <div class="space-y-3 pt-2">
      <div class="flex items-center justify-between py-2 border-b border-gray-100">
        <div>
          <span class="text-sm font-medium text-gray-700">Requerir caracteres especiales</span>
          <p class="text-xs text-gray-400 mt-0.5">Las contraseñas deben incluir al menos un carácter especial (!@#$...)</p>
        </div>
        <el-switch v-model="form.password_require_special" />
      </div>

      <div class="flex items-center justify-between py-2 border-b border-gray-100">
        <div>
          <span class="text-sm font-medium text-gray-700">Requerir números</span>
          <p class="text-xs text-gray-400 mt-0.5">Las contraseñas deben incluir al menos un número</p>
        </div>
        <el-switch v-model="form.password_require_numbers" />
      </div>

      <div class="flex items-center justify-between py-2">
        <div>
          <span class="text-sm font-medium text-gray-700">Requerir mayúsculas</span>
          <p class="text-xs text-gray-400 mt-0.5">Las contraseñas deben incluir al menos una letra mayúscula</p>
        </div>
        <el-switch v-model="form.password_require_uppercase" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, watch } from 'vue';
import { useSettingsStore } from '@/stores/settings';

const settingsStore = useSettingsStore();

const form = reactive({
  session_lifetime: 120,
  password_min_length: 8,
  password_require_special: true,
  password_require_numbers: true,
  password_require_uppercase: true,
});

function loadForm() {
  form.session_lifetime = settingsStore.getSettingValue('security', 'session_lifetime', 120);
  form.password_min_length = settingsStore.getSettingValue('security', 'password_min_length', 8);
  form.password_require_special = settingsStore.getSettingValue('security', 'password_require_special', true);
  form.password_require_numbers = settingsStore.getSettingValue('security', 'password_require_numbers', true);
  form.password_require_uppercase = settingsStore.getSettingValue('security', 'password_require_uppercase', true);
}

loadForm();

watch(() => settingsStore.settings, loadForm, { deep: true });

defineExpose({ form, group: 'security' });
</script>
