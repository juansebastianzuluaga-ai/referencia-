<template>
  <div class="settings-page h-full flex flex-col gap-2 p-3 sm:p-4 overflow-hidden">

    <!-- ── Header ── -->
    <div class="settings-header shrink-0 animate-fade-in-up"
      style="animation-duration: 0.4s; animation-fill-mode: both;">
      <div class="settings-header-icon">
        <component :is="SettingsIcon" class="w-5 h-5" />
      </div>
      <div>
        <h1 class="settings-header-title">Configuración</h1>
        <p class="settings-header-sub">Ajustes generales del sistema</p>
      </div>
      <div class="settings-header-spacer"></div>
      <el-button
        v-if="activeTab !== 'api'"
        v-permission="'settings.update'"
        type="primary"
        size="small"
        :loading="settingsStore.saving"
        @click="save"
      >
        <component :is="SaveIcon" class="w-3.5 h-3.5 mr-1" />
        Guardar Cambios
      </el-button>
    </div>

    <!-- ── Stat cards ── -->
    <div class="settings-stats-bar shrink-0">
      <div v-for="card in statCards" :key="card.label" class="settings-stat-card" :style="{ '--stat-color': card.color }">
        <div class="settings-stat-icon" :style="{ background: card.iconBg, color: card.color }">
          <component :is="card.icon" class="w-4 h-4" />
        </div>
        <div class="settings-stat-body">
          <p class="settings-stat-label">{{ card.label }}</p>
          <p class="settings-stat-value" :style="{ color: card.color }">{{ card.value }}</p>
          <div class="settings-stat-bar-track">
            <div class="settings-stat-bar-fill" :style="{ width: card.percent + '%', background: card.color }"></div>
          </div>
        </div>
        <span class="settings-stat-delta" :style="{ background: card.iconBg, color: card.color }">{{ card.delta }}</span>
      </div>
    </div>

    <!-- ── Contenido ── -->
    <div class="flex-1 overflow-hidden settings-content-panel">
      <!-- Loading skeleton -->
      <div v-if="settingsStore.loading" class="settings-skeleton-wrap">
        <div class="settings-skeleton-sidebar">
          <div v-for="i in 4" :key="i" class="settings-skeleton-tab">
            <div class="shimmer-box" style="width:18px; height:18px; border-radius:4px;"></div>
            <div class="shimmer-bar" style="width:60px; height:11px;"></div>
          </div>
        </div>
        <div class="settings-skeleton-content">
          <div v-for="i in 3" :key="i" class="settings-skeleton-field">
            <div class="shimmer-bar" style="width:120px; height:11px;"></div>
            <div class="shimmer-box" style="width:100%; height:32px; border-radius:6px;"></div>
          </div>
        </div>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-4 gap-4 p-4 h-full overflow-hidden">
        <!-- Tabs verticales -->
        <div class="md:col-span-1 settings-tabs-panel">
          <TransitionGroup name="settings-tab">
            <div
              v-for="tab in tabs"
              :key="tab.key"
              class="settings-tab-item"
              :class="{ 'settings-tab-item-active': activeTab === tab.key }"
              @click="activeTab = tab.key"
            >
              <component :is="tab.icon" class="w-4 h-4 shrink-0" />
              <span>{{ tab.label }}</span>
            </div>
          </TransitionGroup>
        </div>

        <!-- Contenido del tab activo -->
        <div class="md:col-span-3 settings-tab-content">
          <Transition name="settings-fade" mode="out-in">
            <component :is="activeTabComponent" :ref="setTabRef" :key="activeTab" />
          </Transition>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, shallowRef } from 'vue';
import { Settings as SettingsIcon, Mail as MailIcon, Code as CodeIcon, Shield as ShieldIcon, Save as SaveIcon, Server as ServerIcon, Globe as GlobeIcon, Lock as LockIcon } from '@lucide/vue';
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

const statCards = computed(() => {
  const tabsCount = tabs.length;
  const isSaving = settingsStore.saving;
  const isLoading = settingsStore.loading;
  return [
    { label: 'Pestañas', value: tabsCount, icon: SettingsIcon, color: '#2563eb', iconBg: '#dbeafe', percent: 100, delta: 'Configurables' },
    { label: 'Correo', value: settingsStore.settings?.mail_host ? 'OK' : '—', icon: MailIcon, color: '#16a34a', iconBg: '#dcfce7', percent: settingsStore.settings?.mail_host ? 100 : 0, delta: 'SMTP' },
    { label: 'API', value: settingsStore.settings?.api_enabled ? 'ON' : 'OFF', icon: CodeIcon, color: settingsStore.settings?.api_enabled ? '#16a34a' : '#dc2626', iconBg: settingsStore.settings?.api_enabled ? '#dcfce7' : '#fee2e2', percent: settingsStore.settings?.api_enabled ? 100 : 0, delta: 'Estado' },
    { label: 'Seguridad', value: settingsStore.settings?.security_2fa_enabled ? '2FA' : '1FA', icon: LockIcon, color: '#7c3aed', iconBg: '#ede9fe', percent: settingsStore.settings?.security_2fa_enabled ? 100 : 50, delta: 'Auth' },
  ];
});

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

<style scoped>
.settings-page {
  background: linear-gradient(160deg, #eef4fc 0%, #e3edf8 40%, #f0f5fa 100%);
}

/* ── Header ── */
.settings-header {
  display: flex;
  align-items: center;
  gap: .65rem;
}
.settings-header-icon {
  width: 2.2rem; height: 2.2rem;
  border-radius: 10px;
  background: linear-gradient(135deg, #0D2D6B, #16468E);
  color: #fff;
  display: grid; place-items: center;
  flex-shrink: 0;
}
.settings-header-title {
  font-size: 1rem;
  font-weight: 800;
  color: #0D2D6B;
  line-height: 1.2;
}
.settings-header-sub {
  font-size: 11px;
  color: #64748b;
}
.settings-header-spacer { flex: 1; }

/* ── Stat cards ── */
.settings-stats-bar {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: .5rem;
}
.settings-stat-card {
  display: flex;
  align-items: center;
  gap: .6rem;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: .65rem .8rem;
  position: relative;
  overflow: hidden;
  transition: transform .2s ease, box-shadow .2s ease;
}
.settings-stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(13,45,107,.08);
}
.settings-stat-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: var(--stat-color);
  opacity: .8;
}
.settings-stat-icon {
  width: 2.2rem; height: 2.2rem;
  border-radius: 10px;
  display: grid; place-items: center;
  flex-shrink: 0;
}
.settings-stat-body { flex: 1; min-width: 0; }
.settings-stat-label {
  font-size: 10px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: .03em;
  margin: 0;
}
.settings-stat-value {
  font-size: 1.3rem;
  font-weight: 800;
  line-height: 1.1;
  margin: 0;
}
.settings-stat-bar-track {
  height: 3px;
  border-radius: 2px;
  background: #f1f5f9;
  margin-top: .25rem;
  overflow: hidden;
}
.settings-stat-bar-fill {
  height: 100%;
  border-radius: 2px;
  transition: width .4s ease;
}
.settings-stat-delta {
  font-size: 9px;
  font-weight: 700;
  padding: 2px 6px;
  border-radius: 6px;
  white-space: nowrap;
  flex-shrink: 0;
}

/* ── Content panel ── */
.settings-content-panel {
  background: rgba(255,255,255,0.92);
  border: 1px solid rgba(212,222,234,0.65);
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(22,70,142,.07);
  backdrop-filter: blur(10px);
}

/* ── Skeleton ── */
.settings-skeleton-wrap {
  display: grid;
  grid-template-columns: 1fr 3fr;
  gap: 1rem;
  padding: 1rem;
}
.settings-skeleton-sidebar {
  display: flex;
  flex-direction: column;
  gap: .5rem;
}
.settings-skeleton-tab {
  display: flex;
  align-items: center;
  gap: .5rem;
  padding: .6rem .8rem;
  border-radius: 8px;
  background: #f8fafc;
}
.settings-skeleton-content {
  display: flex;
  flex-direction: column;
  gap: .8rem;
}
.settings-skeleton-field {
  display: flex;
  flex-direction: column;
  gap: .3rem;
}
.shimmer-box, .shimmer-bar {
  background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.4s infinite;
}
@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* ── Tabs panel ── */
.settings-tabs-panel {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
}
.settings-tab-item {
  display: flex;
  align-items: center;
  gap: .5rem;
  padding: .6rem .8rem;
  cursor: pointer;
  transition: all .15s ease;
  border-bottom: 1px solid #f1f5f9;
  font-size: 13px;
  color: #64748b;
}
.settings-tab-item:last-child { border-bottom: none; }
.settings-tab-item:hover {
  background: #eff6ff;
  color: #2563eb;
}
.settings-tab-item-active {
  background: #dbeafe;
  border-left: 3px solid #2563eb;
  padding-left: calc(.8rem - 3px);
  color: #2563eb;
  font-weight: 600;
}

/* ── Tab content ── */
.settings-tab-content {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow-y: auto;
  padding: 1rem;
}

/* ── Transitions ── */
.settings-tab-enter-active, .settings-tab-leave-active {
  transition: all .3s ease;
}
.settings-tab-enter-from {
  opacity: 0;
  transform: translateX(-8px);
}
.settings-fade-enter-active {
  transition: all .25s ease;
}
.settings-fade-leave-active {
  transition: all .15s ease;
}
.settings-fade-enter-from {
  opacity: 0;
  transform: translateY(8px);
}
.settings-fade-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

@media (max-width: 768px) {
  .settings-stats-bar { grid-template-columns: repeat(2, 1fr); }
  .settings-skeleton-wrap { grid-template-columns: 1fr; }
}
</style>
