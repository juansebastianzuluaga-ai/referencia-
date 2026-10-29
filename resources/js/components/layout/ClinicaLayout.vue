<template>
  <div class="flex flex-col h-screen overflow-hidden" :style="{ background: layout.isDarkMode ? '#0b0e17' : 'var(--rf-bg)' }">

    <!-- ── Topbar ─────────────────────────────────────────────────────────── -->
    <header
      class="shrink-0 relative z-50"
      :style="{
        background: layout.isDarkMode ? '#11151f' : 'linear-gradient(90deg, var(--rf-primary) 0%, var(--rf-primary-2) 100%)',
        borderBottom: layout.isDarkMode ? '1px solid rgba(255,255,255,0.06)' : 'none',
        boxShadow: layout.isDarkMode ? 'none' : '0 2px 14px rgba(13,45,107,0.28)',
      }"
    >
      <div class="flex items-center gap-3 h-14 px-4">
        <!-- Branding -->
        <router-link to="/clinica/dashboard" class="flex items-center gap-2.5 shrink-0 no-underline">
          <div class="w-9 h-9 rounded-full flex items-center justify-center overflow-hidden bg-white" style="box-shadow: 0 0 0 2px rgba(255,255,255,0.35);">
            <img :src="logoColor" alt="Santa Bárbara" class="w-7 h-7 object-contain" />
          </div>
          <div class="hidden sm:flex flex-col leading-tight">
            <strong class="text-[14px] font-semibold text-white" style="text-shadow: 0 1px 6px rgba(0,0,0,0.25);">Santa Bárbara</strong>
            <span class="text-[9px] font-light tracking-wide" style="color:rgba(255,255,255,0.65);">Portal de clínicas</span>
          </div>
        </router-link>

        <div class="topbar-divider hidden lg:block"></div>

        <!-- Navegación principal (en móvil queda junto al logo, solo íconos + texto compacto) -->
        <nav class="portal-nav flex items-center gap-1 flex-1 min-w-0" aria-label="Navegación del portal">
          <router-link
            v-for="item in mainItems"
            :key="item.to"
            :to="item.to"
            class="topbar-item no-underline"
            active-class="topbar-item-active"
          >
            <component :is="iconOf(item.icon)" class="w-4 h-4 shrink-0" />
            <span>{{ item.text }}</span>
          </router-link>
        </nav>

        <!-- Acciones -->
        <div class="flex items-center gap-1 shrink-0">
          <NotificationCenter v-if="clinicaAuth.isAuthenticated" :light="false" scope="externo" />

          <!-- Clínica -->
          <el-dropdown v-if="clinicaAuth.isAuthenticated" trigger="click" placement="bottom-end" @command="handleCommand">
            <button class="topbar-user">
              <div class="topbar-user-avatar">
                <span class="topbar-user-iniciales">{{ iniciales }}</span>
              </div>
              <div class="hidden md:flex flex-col leading-tight text-left">
                <span class="topbar-user-name">{{ clinicaAuth.clinica?.nombre }}</span>
                <span class="topbar-user-role">Clínica externa</span>
              </div>
              <ChevronDownIcon class="w-3.5 h-3.5 hidden md:block topbar-user-chevron" />
            </button>
            <template #dropdown>
              <el-dropdown-menu class="w-56">
                <div class="px-4 py-3 bg-gray-50 flex items-center gap-2.5">
                  <div class="w-9 h-9 rounded-full flex items-center justify-center shrink-0 text-white" style="background:linear-gradient(135deg, var(--rf-primary), var(--rf-primary-2)); font-size:12px; font-weight:800;">
                    {{ iniciales }}
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="text-[13px] font-semibold text-gray-900 truncate">{{ clinicaAuth.clinica?.nombre }}</div>
                    <div class="text-[11.5px] text-gray-500 truncate">NIT: {{ clinicaAuth.clinica?.nit }}</div>
                  </div>
                </div>
                <el-dropdown-item divided command="logout" :icon="LogOutIcon" class="!text-red-600 hover:!bg-red-50 hover:!text-red-700">Cerrar sesión</el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>

        </div>
      </div>
    </header>

    <!-- ── Contenido ──────────────────────────────────────────────────────── -->
    <main class="portal-main flex-1 flex flex-col min-h-0 overflow-hidden p-4" :style="{ background: layout.isDarkMode ? '#0b0e17' : 'var(--rf-bg)' }">
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" :key="route.path" />
        </transition>
      </router-view>
    </main>

  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useClinicaAuthStore } from '@/stores/clinicaAuth';
import { useClinicaLayoutStore } from '@/stores/clinicaLayout';
import NotificationCenter from '@/components/layout/NotificationCenter.vue';
import * as icons from '@lucide/vue';
import {
  ChevronDown as ChevronDownIcon,
  LogOut as LogOutIcon,
} from '@lucide/vue';
import { ElMessageBox } from 'element-plus';

interface NavItem {
  to: string;
  icon: string;
  text: string;
}

const mainItems: NavItem[] = [
  { to: '/clinica/dashboard', icon: 'Home', text: 'Inicio' },
  { to: '/clinica/solicitud', icon: 'FilePlus', text: 'Nueva solicitud' },
  { to: '/clinica/evolucion', icon: 'Activity', text: 'Evolución' },
];

const logoColor = '/images/logo-avatar.png';
const clinicaAuth = useClinicaAuthStore();
const layout = useClinicaLayoutStore();
const route = useRoute();

function iconOf(name: string) {
  return (icons as any)[name] || icons.Circle;
}

const iniciales = computed(() => {
  const nombre = clinicaAuth.clinica?.nombre ?? '';
  return nombre.split(' ').slice(0, 2).map((w: string) => w[0]).join('').toUpperCase();
});

async function logout() {
  try {
    await ElMessageBox.confirm('¿Cerrar sesión?', 'Confirmar', {
      type: 'warning',
      confirmButtonText: 'Sí, cerrar',
      cancelButtonText: 'Cancelar',
    });
    await clinicaAuth.logout();
  } catch { /* cancelado */ }
}

function handleCommand(cmd: string) {
  if (cmd === 'logout') logout();
}
</script>

<style scoped>
header {
  position: relative;
}
header::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.55), transparent);
  pointer-events: none;
}
header::after {
  content: '';
  position: absolute;
  bottom: -1px; left: 0; right: 0;
  height: 2px;
  background: linear-gradient(90deg, #38bdf8, #2563eb, #16468e);
  background-size: 220% 100%;
  animation: topbar-accent 8s linear infinite;
  opacity: 0.9;
  pointer-events: none;
}
@keyframes topbar-accent {
  from { background-position: 0% 0; }
  to { background-position: 220% 0; }
}

.fade-enter-active, .fade-leave-active { transition: opacity 0.15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.topbar-divider {
  width: 1px;
  height: 26px;
  background: linear-gradient(180deg, transparent, rgba(255,255,255,0.3), transparent);
  flex-shrink: 0;
}

.topbar-item {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 7px 12px;
  border-radius: 10px;
  border: none;
  background: transparent;
  font-size: 12.5px;
  font-weight: 600;
  color: rgba(255,255,255,0.78);
  cursor: pointer;
  white-space: nowrap;
  transition: background .15s ease, color .15s ease, transform .15s ease;
}
.topbar-item :deep(svg) { opacity: .75; }
.topbar-item:hover {
  background: rgba(255,255,255,0.14);
  color: #fff;
  transform: translateY(-1px);
}
.topbar-item:hover :deep(svg) { opacity: 1; }
.topbar-item-active {
  background: #ffffff !important;
  color: var(--rf-primary) !important;
  box-shadow: 0 6px 16px rgba(15,23,42,0.22), inset 0 -2px 0 rgba(37,99,235,0.15);
}
.topbar-item-active :deep(svg) { opacity: 1; color: var(--rf-primary); }
.dark .topbar-item-active {
  background: linear-gradient(135deg, var(--rf-primary), var(--rf-primary-2)) !important;
  color: #fff !important;
}

.topbar-action-btn {
  width: 34px;
  height: 34px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid transparent;
  background: transparent;
  color: rgba(255,255,255,0.7);
  cursor: pointer;
  transition: background .15s ease, color .15s ease, border-color .15s ease;
}
.topbar-action-btn:hover {
  background: rgba(255,255,255,0.12);
  border-color: rgba(255,255,255,0.18);
  color: #fff;
}

/* Perfil destacado: píldora blanca que resalta sobre el navy */
.topbar-user {
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 4px 12px 4px 5px;
  margin-left: 6px;
  border: 1px solid rgba(255,255,255,0.9);
  border-radius: 999px;
  background: rgba(255,255,255,0.96);
  box-shadow: 0 4px 14px rgba(10, 25, 60, .28);
  cursor: pointer;
  transition: background .15s ease, border-color .15s ease, box-shadow .15s ease, transform .15s ease;
}
.topbar-user:hover {
  background: #ffffff;
  border-color: #ffffff;
  box-shadow: 0 6px 18px rgba(10, 25, 60, .38);
  transform: translateY(-1px);
}

@media (max-width: 640px) {
  .portal-main { padding: 8px !important; }
  header > div:first-child { gap: 6px; padding-left: 10px; padding-right: 10px; }
  .portal-nav { justify-content: space-evenly; gap: 2px; }
  .portal-nav .topbar-item {
    gap: 5px;
    padding: 7px 8px;
    font-size: 11px;
    border-radius: 9px;
  }
  .portal-nav .topbar-item :deep(svg) { width: 15px; height: 15px; }
}

@media (max-width: 400px) {
  /* Pantallas muy angostas: las opciones inactivas quedan solo con ícono */
  .portal-nav .topbar-item:not(.topbar-item-active) span { display: none; }
  .portal-nav .topbar-item:not(.topbar-item-active) { padding: 7px 10px; }
}
.dark .topbar-user {
  background: rgba(255,255,255,0.1);
  border-color: rgba(255,255,255,0.22);
  box-shadow: none;
}
.dark .topbar-user:hover {
  background: rgba(255,255,255,0.18);
  border-color: rgba(255,255,255,0.32);
}
.topbar-user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--rf-primary), var(--rf-primary-2, #2563eb));
  box-shadow: inset 0 0 0 2px rgba(255,255,255,0.35), 0 2px 6px rgba(13,45,107,.35);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  flex-shrink: 0;
}
.dark .topbar-user-avatar {
  background: linear-gradient(135deg, #2563eb, #16468e);
}
.topbar-user-iniciales {
  font-size: 11px;
  font-weight: 800;
  color: #fff;
  letter-spacing: 0.03em;
}
.topbar-user-chevron { color: #64748b; }
.dark .topbar-user-chevron { color: rgba(255,255,255,0.55); }
.topbar-user-name {
  font-size: 11.5px;
  font-weight: 800;
  color: var(--rf-primary, #16468e);
  max-width: 160px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.dark .topbar-user-name { color: #fff; }
.topbar-user-role {
  font-size: 9.5px;
  font-weight: 500;
  color: #64748b;
}
.dark .topbar-user-role { color: rgba(255,255,255,0.55); }
</style>
