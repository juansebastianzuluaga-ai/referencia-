import { defineStore } from 'pinia';
import { ref } from 'vue';
import notify from '@/plugins/toast';
import http from '@/plugins/axios';

export const useSettingsStore = defineStore('settings', () => {
  const settings = ref<Record<string, any[]>>({});
  const loading = ref(false);
  const saving = ref(false);
  const loaded = ref(false);

  async function loadSettings(options: { force?: boolean } = {}) {
    if (loaded.value && !options.force) {
      return;
    }
    loading.value = true;
    try {
      const { data } = await http.get('/api/settings');
      settings.value = data.data || {};
      loaded.value = true;
    } catch (e: any) {
      settings.value = {};
      if (e?.response?.status === 403) {
        notify.error('No tiene permiso para ver la configuración.');
      } else {
        notify.error('Error cargando configuración.');
      }
    } finally {
      loading.value = false;
    }
  }

  async function saveSettings(payload: { key: string; value: any }[]) {
    saving.value = true;
    try {
      const { data } = await http.put('/api/settings', { settings: payload });
      settings.value = data.data || {};
      loaded.value = true;
      notify.success('Configuración guardada correctamente');
    } catch (e: any) {
      console.error(e);
      const status = e?.response?.status;
      if (status === 403) {
        notify.error('No tiene permiso para actualizar la configuración (403)');
      } else {
        notify.error('Error al guardar la configuración.');
      }
      throw e;
    } finally {
      saving.value = false;
    }
  }

  function getGroupSettings(group: string): any[] {
    return settings.value[group] || [];
  }

  function getSettingValue(group: string, key: string, defaultVal: any = null): any {
    const groupSettings = settings.value[group] || [];
    const setting = groupSettings.find((s: any) => s.key === key);
    return setting ? setting.value : defaultVal;
  }

  return {
    settings,
    loading,
    saving,
    loaded,
    loadSettings,
    saveSettings,
    getGroupSettings,
    getSettingValue,
  };
});
