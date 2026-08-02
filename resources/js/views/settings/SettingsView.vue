<template>
  <ContentCard title="Configuración" subtitle="Ajustes generales del sistema"
    class="animate-fade-in-up"
    style="animation-duration: 0.4s; animation-fill-mode: both;">
    <template #actions>
      <el-button
        v-if="activeTab !== 'api'"
        v-permission="'settings.update'"
        type="primary"
        :loading="settingsStore.saving"
        @click="save"
      >
        Guardar Cambios
      </el-button>
    </template>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6" v-loading="settingsStore.loading">
      <!-- Tabs verticales -->
      <div class="md:col-span-1">
        <div class="border border-gray-200 rounded-lg overflow-hidden">
          <div
            v-for="tab in tabs"
            :key="tab.key"
            class="p-3 cursor-pointer transition-colors flex items-center gap-2.5 border-b border-gray-100 last:border-b-0"
            :class="activeTab === tab.key
              ? 'bg-blue-50 border-l-4 border-l-blue-600 text-blue-700 font-medium'
              : 'hover:bg-gray-50 text-gray-600'"
            @click="activeTab = tab.key"
          >
            <component :is="tab.icon" class="w-4 h-4 shrink-0" />
            <span class="text-sm">{{ tab.label }}</span>
          </div>
        </div>
      </div>

      <!-- Contenido del tab activo -->
      <div class="md:col-span-3">
        <component :is="activeTabComponent" :ref="setTabRef" />
      </div>
    </div>
  </ContentCard>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, shallowRef } from 'vue';
import { Settings as SettingsIcon, Mail as MailIcon, Code as CodeIcon, Shield as ShieldIcon } from '@lucide/vue';
import ContentCard from '@/components/ui/ContentCard.vue';
import GeneralTab from './tabs/GeneralTab.vue';
import MailTab from './tabs/MailTab.vue';
import ApiTab from './tabs/ApiTab.vue';
import SecurityTab from './tabs/SecurityTab.vue';
import { useSettingsStore } from '@/stores/settings';

const settingsStore = useSettingsStore();

const tabs = [
  { key: 'general', label: 'General', icon: SettingsIcon, component: GeneralTab },
  { key: 'mail', label: 'Correo', icon: MailIcon, component: MailTab },
  { key: 'api', label: 'API', icon: CodeIcon, component: ApiTab },
  { key: 'security', label: 'Seguridad', icon: ShieldIcon, component: SecurityTab },
];

const activeTab = ref('general');
const activeTabComponent = computed(() => tabs.find(t => t.key === activeTab.value)?.component ?? GeneralTab);

const tabRefs = shallowRef<Record<string, any>>({});

function setTabRef(el: any) {
  if (el) {
    tabRefs.value[activeTab.value] = el;
  }
}

async function save() {
  const currentTab = tabRefs.value[activeTab.value];
  if (!currentTab || !currentTab.form) return;

  const formData = currentTab.form;

  const payload = Object.entries(formData).map(([key, value]) => ({
    key,
    value,
  }));

  try {
    await settingsStore.saveSettings(payload);
  } catch {
    // error handled in store
  }
}

onMounted(async () => {
  await settingsStore.loadSettings({ force: true });
});
</script>
