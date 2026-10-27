<template>
  <header
    class="h-[60px] flex items-center justify-between pr-4 pl-2 md:pl-3 shrink-0 z-40 transition-colors duration-300 relative header-shell"
    :class="{ 'header-shell-dark': layout.isDarkMode }"
  >
    <div class="flex items-center gap-2 flex-1 min-w-0">
      <!-- Hamburguesa móvil -->
      <button
        class="flex md:hidden header-icon-btn"
        :style="{ color: layout.isDarkMode ? '#cbd5e1' : '#475569' }"
        title="Mostrar/ocultar menu"
        @click="layout.toggleMobileMenu"
      >
        <MenuIcon class="w-4.5 h-4.5" />
      </button>

      <!-- Colapsar sidebar (desktop) -->
      <button
        class="hidden md:flex header-icon-btn"
        :style="{ color: layout.isDarkMode ? '#cbd5e1' : '#475569' }"
        title="Mostrar/ocultar sidebar"
        @click="layout.toggleSidebar"
      >
        <MenuIcon class="w-4.5 h-4.5" />
      </button>

      <!-- Logo móvil -->
      <div class="md:hidden flex items-center gap-2 shrink-0">
        <div class="w-8 h-8 rounded-full flex items-center justify-center overflow-hidden bg-white border border-slate-200">
          <img :src="logoAvatar" alt="Santa Bárbara" class="w-6 h-6 object-contain" />
        </div>
      </div>

      <!-- Sección actual: ícono + título + fecha -->
      <div class="header-title hidden sm:flex items-center gap-2.5 min-w-0">
        <div class="header-title-icon">
          <component :is="iconoPagina" class="w-4 h-4" />
        </div>
        <div class="min-w-0">
          <h2 class="header-title-text" :style="{ color: layout.isDarkMode ? '#e2e8f0' : '#14294f' }">{{ tituloPagina }}</h2>
          <p class="header-title-sub" :style="{ color: layout.isDarkMode ? '#64748b' : '#8ba3c9' }">{{ fechaHoy }}</p>
        </div>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <!-- Acciones agrupadas en píldora -->
      <div class="header-actions-pill" :class="{ 'header-actions-pill-dark': layout.isDarkMode }">
        <div class="header-action">
          <NotificationCenter :light="!layout.isDarkMode" />
        </div>
        <span class="header-actions-sep" :style="{ background: layout.isDarkMode ? 'rgba(255,255,255,0.1)' : '#e2e8f0' }"></span>
        <button
          class="flex header-icon-btn"
          :style="{ color: layout.isDarkMode ? '#cbd5e1' : '#475569' }"
          :title="layout.isDarkMode ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
          @click="layout.toggleDarkMode"
        >
          <component :is="layout.isDarkMode ? SunIcon : MoonIcon" class="w-[17px] h-[17px]" />
        </button>
      </div>

      <el-dropdown trigger="click" @command="handleCommand">
        <button class="header-user border-none cursor-pointer outline-none" :class="{ 'header-user-dark': layout.isDarkMode }">
          <div class="w-8 h-8 rounded-full flex items-center justify-center overflow-hidden shrink-0 header-user-avatar">
            <img :src="logoAvatarWhite" alt="Usuario" class="w-7 h-8 object-contain" />
          </div>
          <div class="hidden md:block text-left">
            <div class="text-[12.5px] font-semibold leading-tight whitespace-nowrap" :style="{ color: layout.isDarkMode ? '#e2e8f0' : '#1e293b' }">{{ auth.user?.full_name || 'Usuario' }}</div>
            <div class="text-[10.5px] leading-none mt-0.5" :style="{ color: layout.isDarkMode ? '#64748b' : '#94a3b8' }">{{ auth.user?.job_title || 'Personal' }}</div>
          </div>
          <ChevronDownIcon class="hidden md:block w-3.5 h-3.5 ml-0.5 header-user-caret" :style="{ color: layout.isDarkMode ? '#64748b' : '#94a3b8' }" />
        </button>

        <template #dropdown>
          <el-dropdown-menu class="w-56">
            <div class="px-4 py-3 bg-gray-50 flex items-center gap-2.5">
              <div class="w-9 h-9 rounded-full bg-white flex items-center justify-center overflow-hidden">
                <img :src="logoAvatar" alt="Usuario" class="w-8 h-8 object-contain" />
              </div>
              <div class="flex-1 min-w-0">
                <div class="text-[13px] font-semibold text-gray-900 truncate">{{ auth.user?.full_name }}</div>
                <div class="text-[11.5px] text-gray-500 truncate">{{ auth.user?.user_name }}</div>
              </div>
            </div>
            <el-dropdown-item divided command="profile" :icon="UserIcon">Mi perfil</el-dropdown-item>
            <el-dropdown-item command="password" :icon="KeyIcon">Cambiar contrasena</el-dropdown-item>
            <el-dropdown-item divided command="logout" :icon="LogOutIcon" class="!text-red-600 hover:!bg-red-50 hover:!text-red-700">Cerrar sesion</el-dropdown-item>
          </el-dropdown-menu>
        </template>
      </el-dropdown>
    </div>
  </header>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { Component } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useLayoutStore } from '@/stores/layout';
import { useAuthStore } from '@/stores/auth';
import NotificationCenter from './NotificationCenter.vue';
import {
  ChevronDown as ChevronDownIcon,
  ClipboardList as ClipboardListIcon,
  History as HistoryIcon,
  Hospital as HospitalIcon,
  Key as KeyIcon,
  LayoutDashboard as LayoutDashboardIcon,
  LogOut as LogOutIcon,
  Menu as MenuIcon,
  Moon as MoonIcon,
  Settings as SettingsIcon,
  ShieldCheck as ShieldCheckIcon,
  Sun as SunIcon,
  User as UserIcon,
  Users as UsersIcon,
} from '@lucide/vue';

const layout = useLayoutStore();
const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

// Public images served from /public — binding dinámico para que Vite no
// intente resolverlas como imports en build.
const logoAvatar = '/images/logo-avatar.png';
const logoAvatarWhite = '/images/logo-avatar-w.png';

// Título e ícono que se muestran en el header según la sección activa.
const SECCIONES: [RegExp, string, Component][] = [
  [/^\/dashboard/, 'Panel principal', LayoutDashboardIcon],
  [/^\/solicitudes-referencia/, 'Solicitudes de referencia', ClipboardListIcon],
  [/^\/clinicas/, 'Clínicas registradas', HospitalIcon],
  [/^\/historico/, 'Historial', HistoryIcon],
  [/^\/usuarios/, 'Usuarios', UsersIcon],
  [/^\/roles/, 'Roles y permisos', ShieldCheckIcon],
  [/^\/configuracion/, 'Configuración', SettingsIcon],
  [/^\/perfil/, 'Mi perfil', UserIcon],
  [/^\/reportes/, 'Reportes', LayoutDashboardIcon],
];

const seccionActual = computed(() => SECCIONES.find(([re]) => re.test(route.path)));
const tituloPagina = computed(() => seccionActual.value?.[1] ?? 'Inicio');
const iconoPagina = computed(() => seccionActual.value?.[2] ?? LayoutDashboardIcon);

const fechaHoy = computed(() =>
  new Date().toLocaleDateString('es-CO', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
);

function handleCommand(command: string): void {
  if (command === 'logout') {
    void auth.logout();
  } else if (command === 'profile') {
    void router.push({ name: 'profile', query: { tab: 'profile' } });
  } else if (command === 'password') {
    void router.push({ name: 'profile', query: { tab: 'password' } });
  }
}
</script>

<style scoped>
/* Solo estilos visuales — el display lo controlan las clases de Tailwind
   (flex/hidden md:flex) para que la variante responsive funcione. */

/* Fondo con degradado suave y línea de acento inferior */
.header-shell {
  background: linear-gradient(180deg, #f7faff 0%, #eef4fd 100%);
  border-bottom: 1px solid #dde8f6;
}
.header-shell::after {
  content: '';
  position: absolute;
  left: 0; right: 0; bottom: -1px;
  height: 2px;
  background: linear-gradient(90deg, #16468e 0%, #3b82f6 40%, rgba(59,130,246,0) 80%);
  pointer-events: none;
}
.header-shell-dark {
  background: linear-gradient(180deg, #131a29 0%, #0f1522 100%);
  border-bottom-color: rgba(255,255,255,0.07);
}
.header-shell-dark::after {
  background: linear-gradient(90deg, #3b82f6 0%, #6366f1 40%, rgba(99,102,241,0) 80%);
}

.header-icon-btn {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  align-items: center;
  justify-content: center;
  border: none;
  background: transparent;
  cursor: pointer;
  transition: background .15s ease, color .15s ease, transform .15s ease;
}
.header-icon-btn:hover {
  background: #f1f5f9;
}
.header-icon-btn:active {
  transform: scale(0.92);
}
.dark .header-icon-btn:hover {
  background: rgba(255,255,255,0.06);
}

/* Título de sección con ícono */
.header-title { margin-left: 8px; }
.header-title-icon {
  width: 34px; height: 34px;
  border-radius: 10px;
  display: grid; place-items: center;
  background: linear-gradient(135deg, #16468e, #3b82f6);
  color: #fff;
  flex-shrink: 0;
  box-shadow: 0 4px 10px rgba(22,70,142,.28);
}
.header-title-text {
  margin: 0;
  font-size: 14.5px;
  font-weight: 750;
  letter-spacing: .01em;
  line-height: 1.15;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.header-title-sub {
  margin: 1px 0 0;
  font-size: 10.5px;
  font-weight: 500;
  text-transform: capitalize;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Píldora de acciones (notificaciones + tema) */
.header-actions-pill {
  display: flex;
  align-items: center;
  gap: 2px;
  padding: 3px;
  border-radius: 999px;
  border: 1px solid #e2eaf6;
  background: rgba(255,255,255,.75);
  box-shadow: 0 1px 3px rgba(15,40,90,.05);
}
.header-actions-pill-dark {
  border-color: rgba(255,255,255,0.1);
  background: rgba(255,255,255,0.04);
  box-shadow: none;
}
.header-actions-sep {
  width: 1px;
  height: 18px;
  margin: 0 2px;
}

.header-action {
  border-radius: 10px;
  transition: background .15s ease;
}
.header-action:hover {
  background: #f1f5f9;
}
.dark .header-action:hover {
  background: rgba(255,255,255,0.06);
}

/* Chip de usuario en forma de píldora */
.header-user {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 4px 10px 4px 4px;
  margin-left: 4px;
  border-radius: 999px;
  border: 1px solid #e8edf4;
  background: #fbfcfe;
  box-shadow: 0 1px 3px rgba(15, 40, 90, 0.05);
  transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
}
.header-user:hover {
  border-color: #c7dbf7;
  background: #f4f8ff;
  box-shadow: 0 3px 10px rgba(22, 70, 142, 0.1);
}
.header-user-dark {
  border-color: rgba(255,255,255,0.1);
  background: rgba(255,255,255,0.04);
  box-shadow: none;
}
.header-user-dark:hover {
  border-color: rgba(255,255,255,0.18);
  background: rgba(255,255,255,0.07);
}
.header-user-avatar {
  background: linear-gradient(135deg, var(--rf-primary), var(--rf-primary-2));
  box-shadow: 0 0 0 2px rgba(59,130,246,.18);
}
.header-user-caret {
  transition: transform .2s ease;
}
.header-user:hover .header-user-caret {
  transform: translateY(1px);
}
</style>
</file-view>
